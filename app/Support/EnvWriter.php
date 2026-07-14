<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class EnvWriter
{
    /**
     * @param  array<string, string|int|null>  $values
     */
    public function set(array $values, ?string $path = null): void
    {
        $path ??= app()->environmentFilePath();

        $contents = file_get_contents($path);

        throw_if($contents === false, RuntimeException::class, sprintf('Unable to read environment file at %s.', $path));

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->format($value);
            $pattern = '/^#?\s*'.preg_quote($key, '/').'\s*=\s*.*$/m';

            $contents = preg_match($pattern, $contents)
                ? (preg_replace($pattern, $line, $contents, 1) ?? $contents)
                : rtrim($contents, "\n")."\n".$line."\n";
        }

        throw_if(file_put_contents($path, $contents) === false, RuntimeException::class, sprintf('Unable to write environment file at %s.', $path));
    }

    private function format(string|int|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = (string) $value;

        if (preg_match('/^[A-Za-z0-9_.\-:\/]+$/', $value)) {
            return $value;
        }

        return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $value)."'";
    }
}
