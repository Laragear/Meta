<?php

namespace Tests\Console\Commands;

use Laragear\Meta\Console\Commands\WithProductionConfirmation;
use RuntimeException;
use Tests\TestCase;

use function app;
use function func_get_args;

class WithProductionConfirmationTest extends TestCase
{
    protected object $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new class
        {
            use WithProductionConfirmation;

            public bool $confirm = true;
            public ?string $asked = null;

            public function passConfirmOnProduction()
            {
                return $this->confirmOnProduction(...func_get_args());
            }

            public function passBlockOnProduction()
            {
                return $this->blockOnProduction(...func_get_args());
            }

            public function confirm(string $ask): bool
            {
                $this->asked = $ask;

                return $this->confirm;
            }

            public function getLaravel()
            {
                return app();
            }

            public function getName()
            {
                return 'test:command';
            }
        };
    }

    protected function defineProduction($app): void
    {
        $app->env = 'production';
    }

    /** @define-env defineProduction */
    public function test_asks_on_production(): void
    {
        static::assertTrue($this->command->passConfirmOnProduction());
        static::assertSame('The app is on production environment. Proceed?', $this->command->asked);

        $this->command->confirm = false;

        static::assertFalse($this->command->passConfirmOnProduction());

        static::assertFalse($this->command->passConfirmOnProduction('foo'));
        static::assertSame('foo', $this->command->asked);
    }

    public function test_doesnt_ask_on_non_production(): void
    {
        static::assertTrue($this->command->passConfirmOnProduction());
        static::assertNull($this->command->asked);
    }

    /** @define-env defineProduction */
    public function test_block_on_production(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot run test:command command in production environment.');

        $this->command->passBlockOnProduction();
    }

    /** @define-env defineProduction */
    public function test_block_on_production_with_message(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Testing test:command.');

        $this->command->passBlockOnProduction('Testing %s.');
    }

    public function test_doesnt_blocks_on_production(): void
    {
        static::assertNull($this->command->passBlockOnProduction());
    }
}
