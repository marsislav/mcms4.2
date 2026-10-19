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
            throw new RuntimeException(__('Качването е неуспешно.'));
        }

        $max = config('mcms.max_upload_mb') * 1024 * 1024;
        if ($file->getSize() > $max) {
            throw new RuntimeException(__('Файлът е твърде голям (макс. :max MB).', ['max' => config('mcms.max_upload_mb')]));
        }

        $real = $file->getRealPath();
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($real);
        if (! isset(self::TYPES[$mime])) {
            throw new RuntimeException(__('Неразрешен тип файл. Позволени: JPG, PNG, WebP, GIF, PDF.'));
        }
        $ext = self::TYPES[$mime];

        // JPEG / PNG / static GIF -> WebP (smaller files); original format is kept if conversion is off or not possible
        $outMime = self::webpTarget($mime, $real) ? 'image/webp' : $mime;
        if ($outMime !== $mime) {
            $ext = 'webp';
        }

        $sub = date('Y/m');
        $dir = public_path('uploads/'.$sub);
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException(__('Директорията за качване не е записваема.'));
        }

        $name = bin2hex(random_bytes(12));
        $rel = "$sub/$name.$ext";
        $abs = "$dir/$name.$ext";
        $thumbRel = null;
        $w = $h = null;

        if (str_starts_with($mime, 'image/')) {
            $info = @getimagesize($real);
            if (! $info || $info[0] < 1 || $info[1] < 1) {
                throw new RuntimeException(__('Невалидно изображение.'));
            }
            [$w, $h] = $info;
            if ($w * $h > 40_000_000) {
                throw new RuntimeException(__('Изображението е с твърде голяма резолюция.'));
            }

            if ($mime === 'image/gif' && $outMime === 'image/gif') { // animated GIFs stay untouched
                self::passThrough($real, $abs);
            } else {
                [$w, $h] = self::reencode($real, $abs, $mime, $outMime);
                $thumbRel = self::thumbnail($abs, $outMime, "$sub/{$name}_t.$ext", $dir);
                $mime = $outMime;
            }
        } else { // pdf
            if (! str_starts_with((string) file_get_contents($real, false, null, 0, 5), '%PDF-')) {
                throw new RuntimeException(__('Невалиден PDF файл.'));
            }
            if (! @move_uploaded_file($real, $abs) && ! @copy($real, $abs)) {
                throw new RuntimeException(__('Файлът не може да бъде записан.'));
            }
        }

        @chmod($abs, 0644);

        return Media::create([
            'path' => $rel,
            'thumb_path' => $thumbRel,
            'original_name' => mb_substr(self::displayName($file->getClientOriginalName(), $ext), 0, 180),
            'mime' => $mime,
            'size' => (int) filesize($abs),
            'width' => $w,
            'height' => $h,
            'user_id' => $userId,
        ]);
    }

    /** Cleaned original name; when the image was converted to WebP the extension follows. */
    private static function displayName(string $name, string $ext): string
    {
        $name = preg_replace('/[^\p{L}\p{N}._\- ]/u', '', $name) ?: 'file';
        if ($ext === 'webp' && preg_match('/\.(jpe?g|png|gif)$/i', $name)) {
            $name = preg_replace('/\.[^.]+$/', '.webp', $name);
        }

        return $name;
    }

    public static function webpEnabled(): bool
    {
        return function_exists('imagewebp') && function_exists('imagecreatefromwebp') && setting('webp_convert', '1') !== '0';
    }

    public static function webpQuality(): int
    {
        return max(40, min(100, (int) setting('webp_quality', 82) ?: 82));
    }

    /** Should this upload be converted to WebP? (animated GIFs and WebP/PDF are left alone) */
    private static function webpTarget(string $mime, string $path): bool
    {
        if (! self::webpEnabled() || ! in_array($mime, ['image/jpeg', 'image/png', 'image/gif'], true)) {
            return false;
        }
        if ($mime === 'image/gif') {
            // more than one image frame = animation
            return preg_match_all('/\x21\xF9\x04.{4}\x00[\x2C\x21]/s', (string) file_get_contents($path), $m) <= 1;
        }

        return true;
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
            throw new RuntimeException(__('Файлът съдържа забранено съдържание.'));
        }
        if (! @copy($from, $to)) {
            throw new RuntimeException(__('Файлът не може да бъде записан.'));
        }
    }

    /** @return array{0:int,1:int} */
    private static function reencode(string $from, string $to, string $mime, ?string $outMime = null): array
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException(__('PHP GD разширението не е налично.'));
        }
        self::ensureMemory();

        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($from),
            'image/png' => @imagecreatefrompng($from),
            'image/gif' => @imagecreatefromgif($from),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($from) : false,
            default => false,
        };
        if (! $img) {
            throw new RuntimeException(__('Изображението не може да бъде обработено.'));
        }
        $outMime ??= $mime;
        if (! imageistruecolor($img)) { // palette PNG/GIF: WebP needs truecolor
            imagepalettetotruecolor($img);
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
            $img = self::resample($img, (int) round($w * $scale), (int) round($h * $scale), $outMime);
            $w = imagesx($img);
            $h = imagesy($img);
        }

        // PNG/GIF sources -> lossless WebP (keeps sharp edges and transparency); JPEG -> lossy at the configured quality
        $lossless = $outMime === 'image/webp' && in_array($mime, ['image/png', 'image/gif'], true) && defined('IMG_WEBP_LOSSLESS');
        self::write($img, $to, $outMime, $lossless ? IMG_WEBP_LOSSLESS : self::webpQuality());

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
        self::write($img, $to, $mime, $mime === 'image/webp' ? min(self::webpQuality(), 80) : 82);
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
            throw new RuntimeException(__('Изображението не може да бъде записано.'));
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
