<?php

namespace App\Support;

use InvalidArgumentException;

/** Safely updates keys in the .env file (single-quoted values, no interpolation). */
class EnvWriter
{
    public static function set(string $file, array $pairs): void
    {
        $content = is_file($file) ? (string) file_get_contents($file) : '';

        foreach ($pairs as $key => $value) {
            if (! preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                throw new InvalidArgumentException('Invalid env key');
            }
            if ($value === null) {
                $content = preg_replace('/^'.preg_quote($key, '/').'=.*\R?/m', '', $content);
                continue;
            }
            $value = (string) $value;
            if (preg_match('/[\r\n\0]/', $value)) {
                throw new InvalidArgumentException('Invalid env value');
            }

            $line = $key.'='.self::format($value);
            $count = 0;
            $content = preg_replace('/^'.preg_quote($key, '/').'=.*$/m', str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $content, 1, $count);
            if ($count === 0) {
                $content = rtrim($content)."\n".$line."\n";
            }
        }

        if (file_put_contents($file, $content, LOCK_EX) === false) {
            throw new \RuntimeException('Cannot write .env');
        }
        @chmod($file, 0600);
    }

    private static function format(string $value): string
    {
        if ($value !== '' && preg_match('/^[A-Za-z0-9_.:\/\-@]+$/', $value)) {
            return $value;
        }

        return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $value)."'";
    }
}
