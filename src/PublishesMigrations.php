<?php

namespace Laragear\Meta;

use function now;
use function preg_replace;

trait PublishesMigrations
{
    /**
     * Publishes migrations from the given path.
     *
     * @param  array|string  $paths
     * @param  string  $groups
     * @return void
     */
    protected function publishesMigrations(array|string $paths, string $groups = 'migrations'): void
    {
        $prefix = now()->format('Y_m_d_His');

        $files = [];

        foreach ($this->app->make('files')->files($paths) as $file) {
            $filename = preg_replace('/^[\d|_]+/', '', $file->getFilename());

            $files[$file->getRealPath()] = $this->app->databasePath("migrations/{$prefix}_$filename");
        }

        $this->publishes($files, $groups);
    }
}
