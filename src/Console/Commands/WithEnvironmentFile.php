<?php

namespace Laragear\Meta\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use const PHP_EOL;

/**
 * @internal
 */
trait WithEnvironmentFile
{
    /**
     * Returns a LazyCollection of key->values from the environment file.
     *
     * @param  string  $file
     * @return \Illuminate\Support\LazyCollection<string, string>
     */
    protected function envFile(string $file = '.env'): LazyCollection
    {
        return File::lines($this->getLaravel()->basePath($file))
            ->mapWithKeys(static function (string $line): array {
                return [Str::before($line, '=') => Str::after($line, '=')];
            });
    }

    /**
     * Checks a key exists in the environment file and is not null.
     *
     * @param  string  $key
     * @param  string  $file
     * @return bool
     */
    protected function hasEnvKey(string $key, string $file = '.env'): bool
    {
        return $this->envFile($file)->has(Str::upper($key));
    }

    /**
     * Checks if a key is missing in the environment file and is not null.
     *
     * @param  string  $key
     * @param  string  $file
     * @return bool
     */
    protected function missingEnvKey(string $key, string $file = '.env'): bool
    {
        return ! $this->hasEnvKey($key, $file);
    }

    /**
     * Puts a string value in the environment file.
     *
     * @param  string  $key
     * @param  string  $value
     * @param  bool  $force  If false, no new value will replace the original.
     * @param  string  $file
     * @return bool
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function putEnvKey(string $key, string $value, bool $force = false, string $file = '.env'): bool
    {
        $key = Str::upper($key);

        $lines = $this->envFile($file);

        // Bail out if we're not forcing the inclusion, or the key already exists.
        if (! $force && $lines->has($key)) {
            return false;
        }

        $string = $lines->collect()->put($key, $value)->implode(static function (string $value, string $key): string {
            return "$key=$value" . PHP_EOL;
        });

        return (bool) File::put($string, $this->getLaravel()->basePath($file));
    }
}
