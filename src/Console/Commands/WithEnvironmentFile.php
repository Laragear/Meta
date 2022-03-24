<?php

namespace Laragear\Meta\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use const PHP_EOL;

/**
 * @internal
 */
trait WithEnvironmentFile
{
    /**
     * Returns the literal string value of a given environment key.
     *
     * @param  string  $key
     * @param  string  $file
     * @return string|false It will return false if the environment key doesn't exist.
     */
    protected function getEnvKey(string $key, string $file = '.env'): string|false
    {
        $key = Str::upper($key);

        $file = $this->getLaravel()->basePath($file);

        $key = File::lines($file)->first(static function (string $line) use ($key): bool {
            return $key === Str::before($line, '=');
        });

        return $key ? Str::after($key, '=') : false;
    }

    /**
     * Puts a string value in the environment file.
     *
     * @param  string  $key
     * @param  string  $value
     * @param  bool  $force  If false, no new value will replace the original.
     * @param  string  $file
     * @return bool
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function putEnvKey(string $key, string $value, bool $force = false, string $file = '.env'): bool
    {
        $key = Str::upper($key);

        $file = $this->getLaravel()->basePath($file);

        $lines = File::lines($file)->collect();

        $keyIndex = $lines->search(static function (string $line) use ($key): bool {
            return $key === Str::before($line, '=');
        });

        if ($keyIndex === false) {
            $keyIndex = $lines->keys()->last() + 1;
        } elseif (! $force) {
            return false;
        }

        return File::put($file, $lines->put($keyIndex, "$key=$value")->implode(PHP_EOL));
    }
}
