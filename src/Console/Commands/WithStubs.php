<?php

namespace Laragear\Meta\Console\Commands;

use function array_keys;
use function array_values;
use Illuminate\Support\Str;

/**
 * @deprecated Use `\Illuminate\Console\GeneratorCommand` instance.
 * @see \Illuminate\Console\GeneratorCommand
 */
trait WithStubs
{
    /**
     * Publishes the stub into the destination, replacing certain strings.
     *
     * @param  string  $stub
     * @param  string  $destination
     * @param  array<string, string>  $replace  An array of $search => $replacement
     * @param  bool  $force  If true, the stub will be rewritten.
     * @return bool
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function publishStub(string $stub, string $destination, array $replace = [], bool $force = false): bool
    {
        /** @var \Illuminate\Filesystem\Filesystem $filesystem */
        $filesystem = $this->getLaravel()->make('files');

        if ($force !== $filesystem->exists($destination)) {
            return false;
        }

        $content = $filesystem->get($stub);

        if ($replace) {
            $content = Str::replace(array_keys($replace), array_values($replace), $content);
        }

        return $filesystem->put($destination, $content);
    }
}
