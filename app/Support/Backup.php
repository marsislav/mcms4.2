<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use ZipArchive;

/** Pure-PHP backups (no mysqldump / shell needed — works on shared hosting). */
class Backup
{
    public static function dir(): string
    {
        $d = storage_path('app/backups');
        if (! is_dir($d)) {
            @mkdir($d, 0750, true);
        }
        if (! is_file($d.'/.htaccess')) {
            @file_put_contents($d.'/.htaccess', "Require all denied\nDeny from all\n");
        }

        return $d;
    }

    public static function validName(string $name): bool
    {
        return (bool) preg_match('/^(db|uploads)-\d{8}-\d{6}-[a-f0-9]{6}\.(sql\.gz|zip)$/', $name);
    }

    public static function path(string $name): ?string
    {
        if (! self::validName($name)) {
            return null;
        }
        $p = self::dir().'/'.$name;

        return is_file($p) ? $p : null;
    }

    /** @return array<int, array{name:string,size:int,time:int}> */
    public static function list(): array
    {
        $out = [];
        foreach (glob(self::dir().'/*') ?: [] as $f) {
            if (is_file($f) && self::validName(basename($f))) {
                $out[] = ['name' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)];
            }
        }
        usort($out, fn ($a, $b) => $b['time'] <=> $a['time']);

        return $out;
    }

    public static function database(): string
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('Built-in backup supports only MySQL/MariaDB.');
        }
        @set_time_limit(300);

        $name = 'db-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sql.gz';
        $path = self::dir().'/'.$name;
        $gz = gzopen($path, 'wb9');
        if (! $gz) {
            throw new RuntimeException('Could not create the backup file.');
        }

        $pdo = DB::connection()->getPdo();
        gzwrite($gz, "-- mCMS database backup ".date('c')."\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(\PDO::FETCH_NUM);
        foreach ($tables as [$table]) {
            $q = '`'.str_replace('`', '``', $table).'`';
            $create = $pdo->query("SHOW CREATE TABLE $q")->fetch(\PDO::FETCH_NUM)[1];
            gzwrite($gz, "DROP TABLE IF EXISTS $q;\n$create;\n\n");

            $stmt = $pdo->query("SELECT * FROM $q", \PDO::FETCH_NUM);
            $batch = [];
            while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                $vals = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row);
                $batch[] = '('.implode(',', $vals).')';
                if (count($batch) >= 200) {
                    gzwrite($gz, "INSERT INTO $q VALUES\n".implode(",\n", $batch).";\n");
                    $batch = [];
                }
            }
            if ($batch) {
                gzwrite($gz, "INSERT INTO $q VALUES\n".implode(",\n", $batch).";\n");
            }
            gzwrite($gz, "\n");
        }

        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
        gzclose($gz);
        @chmod($path, 0640);
        self::prune();

        return $name;
    }

    public static function uploads(): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive is not available on this hosting.');
        }
        @set_time_limit(300);

        $name = 'uploads-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.zip';
        $path = self::dir().'/'.$name;
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Could not create the ZIP archive.');
        }

        $base = realpath(public_path('uploads'));
        if ($base) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if ($file->isFile() && $file->getFilename() !== '.htaccess') {
                    $zip->addFile($file->getPathname(), substr($file->getPathname(), strlen($base) + 1));
                }
            }
        }
        $zip->close();
        @chmod($path, 0640);
        self::prune();

        return $name;
    }

    public static function delete(string $name): bool
    {
        $p = self::path($name);

        return $p ? @unlink($p) : false;
    }

    /** Keep the 10 newest backups of each kind. */
    private static function prune(): void
    {
        $kinds = ['db' => [], 'uploads' => []];
        foreach (self::list() as $b) {
            $kinds[str_starts_with($b['name'], 'db-') ? 'db' : 'uploads'][] = $b['name'];
        }
        foreach ($kinds as $names) {
            foreach (array_slice($names, 10) as $old) {
                self::delete($old);
            }
        }
    }
}
