<?php

namespace Tests\Console\Commands;

use Generator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\LazyCollection;
use Laragear\Meta\Console\Commands\WithEnvironmentFile;
use Tests\TestCase;
use function app;
use function func_get_args;
use const PHP_EOL;

class WithEnvironmentFileTest extends TestCase
{
    protected object $command;

    protected function setUp(): void
    {
        parent::setUp();

        File::expects('lines')
            ->with($this->app->basePath('.env'))
            ->andReturn(
                LazyCollection::make(static function (): Generator {
                    foreach (['FOO=BAR', 'BAZ=QUZ', 'QUX='] as $item) {
                        yield $item;
                    }
                })
            );

        $this->command = new class
        {
            use WithEnvironmentFile;

            public function runGetEnvKey(): mixed
            {
                return $this->getEnvKey(...func_get_args());
            }

            public function setPutEnvKey(): mixed
            {
                return $this->putEnvKey(...func_get_args());
            }

            public function getLaravel()
            {
                return app();
            }
        };
    }

    public function test_get_env_key(): void
    {
        static::assertSame('BAR', $this->command->runGetEnvKey('FOO'));
        static::assertSame('', $this->command->runGetEnvKey('QUX'));
        static::assertFalse($this->command->runGetEnvKey('TEST'));
    }

    public function test_puts_env_key(): void
    {
        File::expects('put')
            ->with($this->app->basePath('.env'), 'FOO=BAR'.PHP_EOL.'BAZ=QUZ'.PHP_EOL.'QUX='.PHP_EOL.'TEST=VALUE')
            ->andReturnTrue();

        static::assertTrue($this->command->setPutEnvKey('TEST', 'VALUE'));
    }

    public function test_doesnt_replaces_env_key(): void
    {
        File::expects('put')->never();

        static::assertFalse($this->command->setPutEnvKey('FOO', 'COUGAR'));
    }

    public function test_replaces_env_key_forcefully(): void
    {
        File::expects('put')
            ->with($this->app->basePath('.env'), 'FOO=COUGAR'.PHP_EOL.'BAZ=QUZ'.PHP_EOL.'QUX=')
            ->andReturnTrue();

        static::assertTrue($this->command->setPutEnvKey('FOO', 'COUGAR', true));
    }
}
