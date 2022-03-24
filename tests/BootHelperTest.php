<?php

namespace Tests;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Manager;
use Illuminate\Support\ServiceProvider;
use Laragear\Meta\BootHelpers;

class BootHelperTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [TestServiceProvider::class];
    }

    public function test_with_driver(): void
    {
        static::assertSame('bar', $this->app->make('test-manager-foo')->driver('foo'));
    }

    public function test_with_driver_array(): void
    {
        static::assertSame('bar', $this->app->make('test-manager-bar')->driver('foo'));
        static::assertSame('quz', $this->app->make('test-manager-bar')->driver('baz'));
    }

    public function test_with_validation_rule(): void
    {
        static::assertArrayHasKey('foo', $this->app->make('validator')->make([], [])->extensions);

        /** @var \Illuminate\Contracts\Validation\Validator $validator */
        $validator = $this->app->make('validator')->make([
            'pass' => 'test_foo',
        ], [
            'pass' => 'foo',
        ]);

        static::assertFalse($validator->fails());

        /** @var \Illuminate\Contracts\Validation\Validator $validator */
        $validator = $this->app->make('validator')->make([
            'pass' => 'invalid',
        ], [
            'pass' => 'foo',
        ]);

        static::assertTrue($validator->fails());
        static::assertSame('test-foo-message', $validator->getMessageBag()->first());

        /** @var \Illuminate\Contracts\Validation\Validator $validator */
        $validator = $this->app->make('validator')->make([
            'pass' => '',
        ], [
            'pass' => 'foo',
        ]);

        static::assertFalse($validator->fails());
    }

    public function test_with_validation_rule_implicit(): void
    {
        static::assertArrayHasKey('bar', $this->app->make('validator')->make([], [])->extensions);

        /** @var \Illuminate\Contracts\Validation\Validator $validator */
        $validator = $this->app->make('validator')->make([
            'pass' => '',
        ], [
            'pass' => 'bar',
        ]);

        static::assertTrue($validator->fails());
        static::assertSame('test-bar-message', $validator->getMessageBag()->first());
    }

    public function test_with_validation_rule_with_message_callback(): void
    {
        /** @var \Illuminate\Contracts\Validation\Validator $validator */
        $validator = $this->app->make('validator')->make([
            'pass' => '',
        ], [
            'pass' => 'bar',
        ]);

        static::assertTrue($validator->fails());
        static::assertSame('test-bar-message', $validator->getMessageBag()->first());
    }

    public function test_with_middleware(): void
    {
        static::assertEmpty($this->app->make('router')->getMiddleware());
    }

    public function test_with_listener(): void
    {
        /** @var \Illuminate\Events\Dispatcher $events */
        $events = $this->app->make('events');

        static::assertTrue($events->hasListeners('test-event'));
        static::assertCount(1, $events->getListeners('test-event'));
    }

    public function test_with_subscriber(): void
    {
        /** @var \Illuminate\Events\Dispatcher $events */
        $events = $this->app->make('events');

        static::assertTrue($events->hasListeners('test-event-foo'));
        static::assertTrue($events->hasListeners('test-event-bar'));
    }

    public function test_with_schedule(): void
    {
        /** @var \Illuminate\Console\Scheduling\Schedule $schedule */
        $schedule = $this->app->make(Schedule::class);

        static::assertCount(1, $schedule->events());
        static::assertStringContainsString('inspire', $schedule->events()[0]->command);
    }
}

class TestServiceProvider extends ServiceProvider
{
    use BootHelpers;

    public function register(): void
    {
        $this->app->singleton('test-manager-foo', function ($app) {
            return new class($app) extends Manager
            {
                public function getDefaultDriver(): string
                {
                    return 'test';
                }
            };
        });

        $this->app->singleton('test-manager-bar', function ($app) {
            return new class($app) extends Manager
            {
                public function getDefaultDriver(): string
                {
                    return 'test';
                }
            };
        });
    }

    public function boot(): void
    {
        $this->withDriver('test-manager-foo', 'foo', fn () => 'bar');
        $this->withDriver('test-manager-bar', [
            'foo' => fn () => 'bar',
            'baz' => fn () => 'quz',
        ]);

        $this->withValidationRule('foo', fn ($key, $value) => $value === 'test_foo', 'test-foo-message');
        $this->withValidationRule('bar', fn ($key, $value) => $value === 'test_bar', 'test-bar-message', true);
        $this->withValidationRule('baz', fn ($key, $value) => $value === 'test_baz', fn() => 'test-baz-message', true);

        $this->withMiddleware(\Tests\Stubs\TestMiddleware::class);

        $this->withListener('test-event', \Tests\Stubs\TestEventListener::class);

        $this->withSubscriber(TestSubscriber::class);

        $this->withSchedule(function (Schedule $schedule): void {
            $schedule->command('inspire')->everyFifteenMinutes();
        });
    }
}

class TestSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen('test-event-foo', \Tests\Stubs\TestEventFooListener::class);
        $events->listen('test-event-bar', \Tests\Stubs\TestEventBarListener::class);
    }
}
