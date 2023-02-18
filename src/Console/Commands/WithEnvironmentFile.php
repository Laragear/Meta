<?php

namespace Laragear\Meta\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use const PHP_EOL;

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
        // Ensure the key to retrieve from the environment file is uppercase.
        $key = Str::upper($key);

        $file = $this->getLaravel()->basePath($file);

        // Find each line from the file and return the first that has the key.
        $key = File::lines($file)->first(static function (string $line) use ($key): bool {
            return Str::startsWith($line, "$key=");
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
     * @return bool Return false if the environment file was not written.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function putEnvKey(string $key, string $value, bool $force = false, string $file = '.env'): bool
    {
        // Ensure the environment key is all uppercase.
        $key = Str::upper($key);

        $file = $this->getLaravel()->basePath($file);

        // Get all lines from the file, as we need to keep them further down the line.
        $lines = File::lines($file)->collect();

        // Find the line index where the environment key exists.
        $keyIndex = $lines->search(static function (string $line) use ($key): bool {
            return Str::startsWith($line, "$key=");
        });

        // If it doesn't exist, the index is a new line at the end. Bail if it's not forced.
        if ($keyIndex === false) {
            $keyIndex = $lines->count();
        } elseif (! $force) {
            return false;
        }

        // Implode the lines into a string and put it as the new environment file.
        return File::put($file, $lines->put($keyIndex, "$key=$value")->implode(PHP_EOL));
    }
}
