<?php

namespace App\Support;

/**
 * Laravel log reader for the admin panel. Pure parsing (parse()) is separated from file access so it can be tested.
 * Only files inside storage/logs matching *.log are ever opened.
 */
class ErrorLog
{
    public const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    /** @return array<int,array{name:string,size:int,mtime:int}> newest first */
    public static function files(): array
    {
        $out = [];
        foreach (glob(storage_path('logs').'/*.log') ?: [] as $f) {
            $out[] = ['name' => basename($f), 'size' => (int) filesize($f), 'mtime' => (int) filemtime($f)];
        }
        usort($out, fn ($a, $b) => $b['mtime'] <=> $a['mtime']);

        return $out;
    }

    /** Safe absolute path of a log file name or null. */
    public static function path(string $name): ?string
    {
        if (! preg_match('/^[A-Za-z0-9._\-]+\.log$/', $name)) {
            return null;
        }
        $p = storage_path('logs/'.$name);

        return is_file($p) ? $p : null;
    }

    /** @return array{0:array,1:bool} [entries newest first, truncated?] */
    public static function read(string $name, int $maxBytes): array
    {
        $path = self::path($name);
        if (! $path) {
            return [[], false];
        }
        $size = filesize($path);
        $truncated = $size > $maxBytes;
        $fh = fopen($path, 'rb');
        if ($truncated) {
            fseek($fh, $size - $maxBytes);
            fgets($fh); // drop the partial first line
        }
        $text = (string) stream_get_contents($fh);
        fclose($fh);

        return [array_reverse(self::parse($text)), $truncated];
    }

    /** Splits a log into entries: [date, env, level, message (first line), detail (rest, e.g. stack trace)]. */
    public static function parse(string $text): array
    {
        $entries = [];
        $parts = preg_split('/^(?=\[\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}[^\]]*\] )/m', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($parts as $chunk) {
            if (! preg_match('/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})[^\]]*\] ([A-Za-z0-9_\-]+)\.([A-Za-z]+): (.*)$/s', $chunk, $m)) {
                continue;
            }
            $lines = explode("\n", rtrim($m[4]), 2);
            $entries[] = [
                'date' => $m[1], 'env' => $m[2], 'level' => strtolower($m[3]),
                'message' => mb_substr($lines[0], 0, 600), 'detail' => isset($lines[1]) ? mb_substr($lines[1], 0, 20000) : '',
            ];
        }

        return $entries;
    }
}
