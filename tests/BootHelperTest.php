<?php

namespace Tests;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Manager;
use Illuminate\Support\ServiceProvider;
use Laragear\Meta\Attributes\RegisterRule;
use Laragear\Meta\BootHelpers;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use Orchestra\Testbench\Http\Kernel;

use function method_exists;
use function realpath;

class BootHelperTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [TestServiceProvider::class];
    }

    protected function setUp(): void
    {
        TestServiceProvider::flushCachedValidationRules();

        parent::setUp();
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

    public function test_registers_validation_rules_from_class(): void
    {
        $factory = $this->app->make('validator');
        $extensions = $factory->make([], [])->extensions;

        static::assertArrayHasKey('good', $extensions);
        static::assertArrayHasKey('good_implicit', $extensions);
        static::assertArrayHasKey('good_key', $extensions);
        static::assertArrayNotHasKey('do_not_register_protected', $extensions);
        static::assertArrayNotHasKey('do_not_register_private', $extensions);
        static::assertArrayNotHasKey('do_not_register_protected_static', $extensions);

        $validator = $factory->make(['pass' => 'passes'], ['pass' => 'good']);

        static::assertFalse($validator->fails());
        static::assertEmpty($validator->getMessageBag()->first());

        $validator = $factory->make(['pass' => ''], ['pass' => 'good']);

        static::assertFalse($validator->fails());
        static::assertEmpty($validator->getMessageBag()->first());

        $validator = $factory->make(['pass' => ''], ['pass' => 'good_implicit']);

        static::assertTrue($validator->fails());
        static::assertSame('testprefix::validation.validate_good_implicit', $validator->getMessageBag()->first());

        $validator = $factory->make(['pass' => 'nope'], ['pass' => 'good_key']);

        static::assertTrue($validator->fails());
        static::assertSame('testprefix::validation.test-translation-key', $validator->getMessageBag()->first());
    }

    public function test_with_blade_directives(): void
    {
        static::assertArrayHasKey('test', $this->app->make('blade.compiler')->getCustomDirectives());
    }

    public function test_with_blade_components(): void
    {
        static::assertArrayHasKey(
            'test-components-prefix', $this->app->make('blade.compiler')->getClassComponentNamespaces()
        );
    }

    public function test_with_middleware_does_not_edit_middleware_in_router(): void
    {
        static::assertSame(
            $this->app->make(Kernel::class)->getRouteMiddleware(),
            $this->app->make('router')->getMiddleware(),
        );
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

    public function test_with_gate(): void
    {
        /** @var \Illuminate\Auth\Access\Gate $gate */
        $gate = $this->app->make(Gate::class);

        static::assertArrayHasKey('foo', $gate->abilities());
    }

    public function test_with_policy(): void
    {
        /** @var \Illuminate\Auth\Access\Gate $gate */
        $gate = $this->app->make(Gate::class);

        static::assertArrayHasKey(User::class, $gate->policies());
    }

    public function test_with_schedule(): void
    {
        /** @var \Illuminate\Console\Scheduling\Schedule $schedule */
        $schedule = $this->app->make(Schedule::class);

        static::assertCount(1, $schedule->events());
        static::assertStringContainsString('inspire', $schedule->events()[0]->command);
    }

    protected function stopTime(): void
    {
        Carbon::setTestNow(Carbon::create(2012));
    }

    /**
     * @define-env stopTime
     */
    #[DefineEnvironment('stopTime')]
    public function test_with_publishable_migrations(): void
    {
        $files = ServiceProvider::$publishes[TestServiceProvider::class];

        if (method_exists(ServiceProvider::class, 'publishesMigrations')) {
            static::assertSame([$this->app->databasePath('migrations')], $files[__DIR__.'/../stubs/migrations']);
        } else {
            static::assertSame([
                realpath(__DIR__.'/../stubs/migrations/0000_00_00_000000_create_table_foo.php') => $this->app->databasePath('migrations/2012_01_01_000001_create_table_foo.php'),
            ], $files);
        }
    }

    public function test_flushes_cached_validation_rules(): void
    {
        static::assertEmpty(TestServiceProvider::cachedValidationRules());

        $this->app->make('validator');

        static::assertNotEmpty(TestServiceProvider::cachedValidationRules());

        TestServiceProvider::flushCachedValidationRules();

        static::assertEmpty(TestServiceProvider::cachedValidationRules());
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
        $this->withValidationRule('baz', fn ($key, $value) => $value === 'test_baz', fn () => 'test-baz-message', true);

        $this->withValidationRulesFrom(TestValidationClass::class, 'testprefix');

        $this->withMiddleware(\Tests\Stubs\TestMiddleware::class);

        $this->withListener('test-event', \Tests\Stubs\TestEventListener::class);

        $this->withSubscriber(TestSubscriber::class);

        $this->withGate('foo', fn () => 'bar');
        $this->withPolicy(User::class, TestPolicy::class);

        $this->withSchedule(function (Schedule $schedule): void {
            $schedule->command('inspire')->everyFifteenMinutes();
        });

        $this->withPublishableMigrations(__DIR__.'/../stubs/migrations');

        $this->withValidationRulesFrom(TestValidationClass::class, 'testprefix');

        $this->withBladeDirectives(['test' => fn () => 'true']);
        $this->withBladeComponents(__DIR__.'/../stubs/components', 'test-components-prefix');
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

class TestValidationClass
{
    public function foo()
    {
        return true;
    }

    #[RegisterRule('do_not_register_protected')]
    protected function bar()
    {
        return true;
    }

    #[RegisterRule('do_not_register_private')]
    private function quz()
    {
        return true;
    }

    public static function baz()
    {
        return true;
    }

    #[RegisterRule('do_not_register_protected_static')]
    protected static function validateStaticProtected()
    {
        return true;
    }

    #[RegisterRule('do_not_register_protected_static')]
    private static function validateStaticPrivate()
    {
        return true;
    }

    #[RegisterRule('good')]
    public static function validateGood(string $attribute, mixed $value)
    {
        return $value === 'passes';
    }

    #[RegisterRule('good_implicit', implicit: true)]
    public static function validateGoodImplicit(string $attribute, mixed $value)
    {
        return $value === 'passes';
    }

    #[RegisterRule('good_key', translationKey: 'test-translation-key')]
    public static function validateGoodTranslated(string $attribute, mixed $value)
    {
        return $value === 'passes';
    }
}
