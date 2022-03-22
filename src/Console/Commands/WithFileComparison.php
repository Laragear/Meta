<?php

namespace Laragear\Meta\Console\Commands;

/**
 * @internal
 */
trait WithFileComparison
{
    /**
     * Check the file exists and is equal to another.
     *
     * @param  string  $comparable
     * @param  string  $path
     * @return bool
     */
    protected function fileEqualTo(string $comparable, string $path): bool
    {
        /** @var \Illuminate\Filesystem\Filesystem $filesystem */
        $filesystem = $this->getLaravel()->make('files');

        return $filesystem->exists($comparable)
            && $filesystem->exists($path)
            && $filesystem->hash($comparable) === $filesystem->hash($path);
    }

    /**
     * Check the file exists and is not equal to another.
     *
     * @param  string  $comparable
     * @param  string  $path
     * @return bool
     */
    protected function fileNotEqualTo(string $comparable, string $path): bool
    {
        /** @var \Illuminate\Filesystem\Filesystem $filesystem */
        $filesystem = $this->getLaravel()->make('files');

        return $filesystem->exists($comparable)
            && $filesystem->exists($path)
            && $filesystem->hash($comparable) !== $filesystem->hash($path);
    }
}
