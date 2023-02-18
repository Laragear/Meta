<?php

namespace Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Laragear\Meta\PublishesMigrations;
use Laragear\MetaTesting\InteractsWithServiceProvider;
use function now;
use Symfony\Component\Finder\SplFileInfo;

class PublishesMigrationsTest extends TestCase
{
    use InteractsWithServiceProvider;

    public function test_publishes_migrations(): void
    {
        File::expects('files')->with('foo/bar')
            ->andReturn([
                new SplFileInfo('9999_99_99_999999_create_foo_table.php', 'foo/bar', 'relative'),
                new SplFileInfo('create_foo_table.php', 'foo/bar', 'relative'),
            ]);

        $this->travelTo(now()->startOfSecond());

        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use PublishesMigrations;

            public function boot(): void
            {
                $this->publishesMigrations('foo/bar');
            }
        });

        $this->assertPublishes(
            $this->app->databasePath('migrations/'.now()->format('Y_m_d_His').'_create_foo_table.php'), 'migrations'
        );

        $this->assertPublishes(
            $this->app->databasePath('migrations/'.now()->format('Y_m_d_His').'_create_foo_table.php'), 'migrations'
        );
    }
}
