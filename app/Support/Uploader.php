<?php

namespace App\Support;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Hardened upload pipeline:
 *  - real MIME sniffing (finfo) against an allow-list, client MIME/extension ignored
 *  - random file names with an extension derived from the sniffed type
 *  - raster images are fully re-encoded with GD (strips EXIF/payloads, fixes orientation)
 *  - decompression-bomb guard, size limit, PHP-tag scan for pass-through formats
 */
class Uploader
{
    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
    ];

    public static function store(UploadedFile $file, ?int $userId = null): Media
    {
        if (! $file->isValid()) {
            throw new RuntimeException('Upload failed.');
        }

        $max = config('mcms.max_upload_mb') * 1024 * 1024;
        if ($file->getSize() > $max) {
            throw new RuntimeException('File is too large (max. '.config('mcms.max_upload_mb').' MB).');
        }

        $real = $file->getRealPath();
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($real);
        if (! isset(self::TYPES[$mime])) {
            throw new RuntimeException('File type not allowed. Allowed: JPG, PNG, WebP, GIF, PDF.');
        }
        $ext = self::TYPES[$mime];

        $sub = date('Y/m');
        $dir = public_path('uploads/'.$sub);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('The upload directory is not writable.');
        }

        $name = bin2hex(random_bytes(12));
        $rel = "$sub/$name.$ext";
        $abs = "$dir/$name.$ext";
        $thumbRel = null;
        $w = $h = null;

        if (str_starts_with($mime, 'image/')) {
            $info = @getimagesize($real);
            if (! $info || $info[0] < 1 || $info[1] < 1) {
                throw new RuntimeException('Invalid image.');
            }
            [$w, $h] = $info;
            if ($w * $h > 40_000_000) {
                throw new RuntimeException('The image resolution is too high.');
            }

            if ($mime === 'image/gif') {
                self::passThrough($real, $abs);
            } else {
                [$w, $h] = self::reencode($real, $abs, $mime);
                $thumbRel = self::thumbnail($abs, $mime, "$sub/{$name}_t.$ext", $dir);
            }
        } else { // pdf
            if (! str_starts_with((string) file_get_contents($real, false, null, 0, 5), '%PDF-')) {
                throw new RuntimeException('Invalid PDF file.');
            }
            if (! @move_uploaded_file($real, $abs) && ! @copy($real, $abs)) {
                throw new RuntimeException('The file could not be saved.');
            }
        }

        @chmod($abs, 0644);

        return Media::create([
            'path' => $rel,
            'thumb_path' => $thumbRel,
            'original_name' => mb_substr(preg_replace('/[^\p{L}\p{N}._\- ]/u', '', $file->getClientOriginalName()) ?: 'file', 0, 180),
            'mime' => $mime,
            'size' => (int) filesize($abs),
            'width' => $w,
            'height' => $h,
            'user_id' => $userId,
        ]);
    }

    public static function delete(Media $media): void
    {
        $base = realpath(public_path('uploads'));
        foreach ([$media->path, $media->thumb_path] as $rel) {
            if (! $rel) {
                continue;
            }
            $abs = realpath(public_path('uploads/'.$rel));
            if ($abs && $base && str_starts_with($abs, $base.DIRECTORY_SEPARATOR) && is_file($abs)) {
                @unlink($abs);
            }
        }
        $media->delete();
    }

    private static function passThrough(string $from, string $to): void
    {
        if (preg_match('/<\?(php|=)|<script/i', (string) file_get_contents($from))) {
            throw new RuntimeException('The file contains forbidden content.');
        }
        if (! @copy($from, $to)) {
            throw new RuntimeException('The file could not be saved.');
        }
    }

    /** @return array{0:int,1:int} */
    private static function reencode(string $from, string $to, string $mime): array
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('The PHP GD extension is not available.');
        }
        self::ensureMemory();

        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($from),
            'image/png' => @imagecreatefrompng($from),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($from) : false,
            default => false,
        };
        if (! $img) {
            throw new RuntimeException('The image could not be processed.');
        }

        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($from);
            $rot = match ((int) ($exif['Orientation'] ?? 1)) {
                3 => 180, 6 => -90, 8 => 90, default => 0,
            };
            if ($rot) {
                $rotated = imagerotate($img, $rot, 0);
                if ($rotated) {
                    $img = $rotated;
                }
            }
        }

        $maxPx = (int) config('mcms.image_max_px');
        $w = imagesx($img);
        $h = imagesy($img);
        if (max($w, $h) > $maxPx) {
            $scale = $maxPx / max($w, $h);
            $img = self::resample($img, (int) round($w * $scale), (int) round($h * $scale), $mime);
            $w = imagesx($img);
            $h = imagesy($img);
        }

        self::write($img, $to, $mime);

        return [$w, $h];
    }

    private static function thumbnail(string $abs, string $mime, string $rel, string $dir): ?string
    {
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($abs),
            'image/png' => @imagecreatefrompng($abs),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($abs) : false,
            default => false,
        };
        if (! $img) {
            return null;
        }
        $tw = (int) config('mcms.thumb_px');
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w > $tw) {
            $img = self::resample($img, $tw, (int) max(1, round($h * $tw / $w)), $mime);
        }
        $to = public_path('uploads/'.$rel);
        self::write($img, $to, $mime, 82);
        @chmod($to, 0644);

        return $rel;
    }

    private static function resample($src, int $nw, int $nh, string $mime)
    {
        $dst = imagecreatetruecolor($nw, $nh);
        if ($mime !== 'image/jpeg') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, imagesx($src), imagesy($src));

        return $dst;
    }

    private static function write($img, string $to, string $mime, int $quality = 86): void
    {
        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($img, $to, $quality),
            'image/png' => (function () use ($img, $to) {
                imagesavealpha($img, true);

                return imagepng($img, $to, 6);
            })(),
            'image/webp' => imagewebp($img, $to, $quality),
            default => false,
        };
        if (! $ok) {
            throw new RuntimeException('The image could not be saved.');
        }
    }

    private static function ensureMemory(): void
    {
        $limit = ini_get('memory_limit');
        if ($limit !== '-1' && (int) $limit < 256 && ! str_contains((string) $limit, 'G')) {
            @ini_set('memory_limit', '256M');
        }
    }
}
