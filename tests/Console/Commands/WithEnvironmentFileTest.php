<?php

namespace Tests\Console\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\LazyCollection;
use Laragear\Meta\Console\Commands\WithEnvironmentFile;
use Tests\TestCase;
use function app;
use const PHP_EOL;

class WithEnvironmentFileTest extends TestCase
{
    protected object $command;

    protected function setUp(): void
    {
        parent::setUp();

        File::shouldReceive()->lines($this->app->basePath('.env'))
            ->andReturn(
                LazyCollection::make(function () {
                    foreach (['FOO=BAR', 'BAZ=QUZ'] as $item) {
                        yield $item;
                    }
                })
            );

        $this->command = new class {
            use WithEnvironmentFile;

            public function contents()
            {
                return $this->envFile();
            }

            public function getHasEnvKey(string $key)
            {
                return $this->hasEnvKey($key);
            }

            public function getMissingEnvKey(string $key)
            {
                return $this->missingEnvKey($key);
            }

            public function setPutEnvKey(string $key, string $value, bool $force = false): bool
            {
                return $this->putEnvKey($key, $value, $force);

            }
            public function getLaravel()
            {
                return app();
            }
        };
    }

    public function test_command_retrieve_files(): void
    {
        static::assertSame(['FOO' => 'BAR', 'BAZ' => 'QUZ'], $this->command->contents()->toArray());
    }

    public function test_has_env_key(): void
    {
        static::assertTrue($this->command->getHasEnvKey('FOO'));
        static::assertFalse($this->command->getHasEnvKey('BAR'));
    }

    public function test_missing_env_key(): void
    {
        static::assertFalse($this->command->getMissingEnvKey('FOO'));
        static::assertTrue($this->command->getMissingEnvKey('BAR'));
    }

    public function test_puts_env_key(): void
    {
        File::expects('put')->with(
            'FOO=BAR' . PHP_EOL . 'BAZ=QUZ' . PHP_EOL . 'QUX=COUGAR' . PHP_EOL,
            $this->app->basePath('.env')
        )
            ->andReturnTrue();

        static::assertTrue($this->command->setPutEnvKey('QUX', 'COUGAR'));
    }

    public function test_doesnt_replaces_env_key(): void
    {
        File::expects('put')->never();

        static::assertFalse($this->command->setPutEnvKey('FOO', 'COUGAR'));
    }

    public function test_replaces_env_key_forcefully(): void
    {
        File::expects('put')->with(
            'FOO=COUGAR' . PHP_EOL . 'BAZ=QUZ' . PHP_EOL,
            $this->app->basePath('.env')
        )
            ->andReturnTrue();

        static::assertTrue($this->command->setPutEnvKey('FOO', 'COUGAR', true));
    }
}
