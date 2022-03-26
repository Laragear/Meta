<?php

namespace Tests\Testing;

use Illuminate\Auth\SessionGuard;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Fluent;
use Illuminate\Support\Manager;
use Illuminate\Support\ServiceProvider;
use Laragear\Meta\BootHelpers;
use Laragear\Meta\PublishesMigrations;
use Laragear\Meta\Testing\InteractsWithServiceProvider;
use Mockery;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase;

class InteractsWithServiceProviderTest extends TestCase
{
    use InteractsWithServiceProvider;

    public function test_assert_has_driver(): void
    {
        $this->app->instance('manager', new class($this->app) extends Manager
        {
            public function getDefaultDriver()
            {
                return 'foo';
            }
        });

        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use BootHelpers;

            public function boot(): void
            {
                $this->app->make('config')->set('auth.guards.foo', ['driver' => 'session', 'provider' => 'users']);

                $this->withDriver('auth', 'foo', fn () => 'bar');
                $this->withDriver('manager', 'baz', fn () => 'quz');
            }
        });

        $this->assertHasDriver('auth', 'foo');
        $this->assertHasDriver('manager', 'baz');
    }

    public function test_assert_has_driver_with_class(): void
    {
        $this->app->instance('manager', new class($this->app) extends Manager
        {
            public function getDefaultDriver()
            {
                return 'foo';
            }
        });

        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use BootHelpers;

            public function boot(): void
            {
                $this->app->make('config')->set('auth.guards.foo', ['driver' => 'session', 'provider' => 'users']);

                $this->withDriver('auth', 'foo', fn () => 'bar');
                $this->withDriver('manager', 'baz', fn () => new Fluent());
            }
        });

        $this->assertHasDriver('auth', 'foo', SessionGuard::class);
        $this->assertHasDriver('manager', 'baz', Fluent::class);
    }

    public function test_assert_has_driver_fails_if_driver_doesnt_exists(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'auth' service doesn't have the driver 'foo'.");

        $this->assertHasDriver('auth', 'foo');
    }

    public function test_assert_has_driver_fails_if_driver_is_not_instance_of_class(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The the driver 'web' is not an instance of 'Illuminate\Support\Fluent'.");

        $this->assertHasDriver('auth', 'web', Fluent::class);
    }

    public function test_assert_services(): void
    {
        $this->app->instance('foo', 'bar');

        $this->assertHasServices('foo');
    }

    public function test_assert_services_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'foo' was not registered in the Service Container.");

        $this->assertHasServices('foo');
    }

    public function test_assert_singletons(): void
    {
        $this->app->instance('foo', 'bar');

        $this->assertHasSingletons('foo');
        $this->assertHasShared('foo');
    }

    public function test_assert_singletons_fails_if_not_singleton(): void
    {
        $this->app->bind('foo', fn () => 'bar');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'foo' is registered as a shared instance in the Service Container.");

        $this->assertHasSingletons('foo');
    }

    public function test_assert_singletons_fails_if_not_registered(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'foo' was not registered in the Service Container.");

        $this->assertHasSingletons('foo');
    }

    public function test_assert_merged_config(): void
    {
        File::expects('getRequire')->with('foo/bar.php')->andReturn(['foo' => 'bar']);

        $this->app->make('config')->set('bar', ['foo' => 'bar']);

        $this->assertConfigMerged('foo/bar.php');
    }

    public function test_assert_merged_config_without_guess(): void
    {
        File::expects('getRequire')->with('foo/bar.php')->andReturn(['foo' => 'bar']);

        $this->app->make('config')->set('baz', ['foo' => 'bar']);

        $this->assertConfigMerged('foo/bar.php', 'baz');
    }

    public function test_assert_merged_config_fails_if_doesnt_exists(): void
    {
        File::expects('getRequire')->never();

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The configuration file was not merged as 'bar'.");

        $this->assertConfigMerged('foo/bar.php');
    }

    public function test_assert_merged_config_fails_if_not_same(): void
    {
        File::expects('getRequire')->with('foo/bar.php')->andReturn(['foo' => 'bar']);

        $this->app->make('config')->set('bar', ['baz' => 'quz']);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The configuration file in 'foo/bar.php' is not the same for 'bar'.");

        $this->assertConfigMerged('foo/bar.php');
    }

    public function test_assert_publishes(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                $this->publishes(['foo' => 'bar'], 'quz');
            }
        });

        $this->assertPublishes('bar', 'quz');
    }

    public function test_assert_publishes_fails_if_tag_doesnt_exists(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                $this->publishes(['foo' => 'bar'], 'quz');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'invalid' is not a publishable tag.");

        $this->assertPublishes('bar', 'invalid');
    }

    public function test_assert_publishes_fails_if_file_doesnt_exists_in_tag(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                $this->publishes(['foo' => 'bar'], 'quz');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'baz' is not publishable in the 'quz' tag.");

        $this->assertPublishes('baz', 'quz');
    }

    public function test_assert_publishes_migration(): void
    {
        $foo = Mockery::mock(SplFileInfo::class);
        $foo->expects('getRealPath')->andReturn('/package/vendor/migrations/create_foo_table.php');
        $foo->expects('getFilename')->twice()->andReturn('create_foo_table.php');

        $bar = Mockery::mock(SplFileInfo::class);
        $bar->expects('getRealPath')->andReturn('/package/vendor/migrations/2020_01_01_173055_create_bar_table.php');
        $bar->expects('getFilename')->twice()->andReturn('2020_01_01_173055_create_bar_table.php');

        File::expects('files')->twice()->with('/package/vendor/migrations')->andReturn([$foo, $bar]);

        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use PublishesMigrations;

            public function boot(): void
            {
                $this->publishesMigrations('/package/vendor/migrations');
            }
        });

        $this->assertPublishesMigrations('/package/vendor/migrations');
    }

    public function test_assert_publishes_migration_with_different_tag(): void
    {
        $foo = Mockery::mock(SplFileInfo::class);
        $foo->expects('getRealPath')->andReturn('/package/vendor/migrations/create_foo_table.php');
        $foo->expects('getFilename')->twice()->andReturn('create_foo_table.php');

        File::expects('files')->twice()->with('/package/vendor/migrations')->andReturn([$foo]);

        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use PublishesMigrations;

            public function boot(): void
            {
                $this->publishesMigrations('/package/vendor/migrations', 'cougar');
            }
        });

        $this->assertPublishesMigrations('/package/vendor/migrations', 'cougar');
    }

    public function test_assert_published_migration_fails(): void
    {
        $foo = Mockery::mock(SplFileInfo::class);
        $foo->expects('getRealPath')->andReturn('/package/vendor/migrations/create_foo_table.php');
        $foo->expects('getFilename')->andReturn('create_foo_table.php');

        File::expects('files')->once()->with('/package/vendor/migrations')->andReturn([$foo]);
        File::expects('files')->once()->with('/invalid')->andReturn([]);

        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use PublishesMigrations;

            public function boot(): void
            {
                $this->publishesMigrations('/package/vendor/migrations');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The '/invalid' has no migration files.");

        $this->assertPublishesMigrations('/invalid');
    }

    public function test_assert_publishes_migration_fails_if_tag_not_same(): void
    {
        File::partialMock()->expects('files')->once()->with('/package/vendor/migrations')->andReturn([]);

        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use PublishesMigrations;

            public function boot(): void
            {
                $this->publishesMigrations('/package/vendor/migrations');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'invalid-tag' is not a publishable tag");

        $this->assertPublishesMigrations('/package/vendor/migrations', 'invalid-tag');
    }

    public function test_assert_translations(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                $this->loadTranslationsFrom('foo', 'bar');
            }
        });

        $this->assertHasTranslations('foo', 'bar');
    }

    public function test_assert_translations_fails_if_namespace_doesnt_exists(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                $this->loadTranslationsFrom('foo', 'bar');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'quz' translations were not registered.");

        $this->assertHasTranslations('foo', 'quz');
    }

    public function test_assert_translations_fails_if_file_doesnt_exists_in_namespace(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                $this->loadTranslationsFrom('foo', 'bar');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'bar' does not correspond to the path 'quz'.");

        $this->assertHasTranslations('quz', 'bar');
    }

    public function test_assert_views(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                $this->loadViewsFrom('foo', 'bar');
            }
        });

        $this->assertHasViews('foo', 'bar');
    }

    public function test_assert_views_fails_if_namespace_doesnt_exists(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                $this->loadViewsFrom('foo', 'bar');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'baz' views were not registered.");

        $this->assertHasViews('foo', 'baz');
    }

    public function test_assert_views_fails_if_file_doesnt_exist_in_namespace(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                $this->loadViewsFrom('foo', 'bar');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'bar' does not correspond to the path 'baz'.");

        $this->assertHasViews('baz', 'bar');
    }

    public function test_assert_blade_component(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                $this->loadViewComponentsAs('foo', [
                    'bar' => 'quz',
                    'cougar',
                ]);
            }
        });

        $this->assertHasBladeComponent('foo-bar', 'quz');
        $this->assertHasBladeComponent('foo-cougar', 'cougar');
    }

    public function test_assert_blade_component_fails_if_alias_doesnt_exists(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                $this->loadViewComponentsAs('foo', [
                    'bar' => 'quz',
                    'cougar',
                ]);
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'foo-baz' is not registered as component.");

        $this->assertHasBladeComponent('foo-baz', 'quz');
    }

    public function test_assert_blade_component_fails_if_component_doesnt_exists_in_alias(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                $this->loadViewComponentsAs('foo', [
                    'bar' => 'quz',
                    'cougar',
                ]);
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'cougar' component is not registered as 'foo-bar'.");

        $this->assertHasBladeComponent('foo-bar', 'cougar');
    }

    public function test_assert_blade_directive(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                Blade::directive('foo', static fn () => 'bar');
            }
        });

        $this->assertHasBladeDirectives('foo');
    }

    public function test_assert_blade_directives_fail_if_doesnt_exist(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                Blade::directive('foo', static fn () => 'bar');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'bar' was not registered as a blade directive.");

        $this->assertHasBladeDirectives('bar');
    }

    public function test_assert_validation_rules(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                Validator::extend('foo', 'bar');
            }
        });

        $this->assertHasValidationRules('foo');
    }

    public function test_assert_validation_rules_fail_if_doesnt_exist(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                Validator::extend('foo', 'bar');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'bar' rule was not registered in the validator.");

        $this->assertHasValidationRules('bar');
    }

    public function test_assert_middleware_aliases(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(Router $router): void
            {
                $router->aliasMiddleware('foo', 'bar');
            }
        });

        $this->assertHasMiddlewareAlias('foo', 'bar');
    }

    public function test_assert_middleware_aliases_fails_if_doesnt_exist(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(Router $router): void
            {
                $router->aliasMiddleware('foo', 'bar');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'baz' alias was not registered as middleware.");

        $this->assertHasMiddlewareAlias('baz', 'bar');
    }

    public function test_assert_middleware_aliases_fails_if_middleware_alias_doesnt_exist(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(Router $router): void
            {
                $router->aliasMiddleware('foo', 'bar');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'baz' was not aliased as 'foo' middleware.");

        $this->assertHasMiddlewareAlias('foo', 'baz');
    }

    public function test_assert_global_middleware(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(Kernel $http): void
            {
                $http->pushMiddleware('foo');
            }
        });

        $this->assertHasGlobalMiddleware('foo');
    }

    public function test_assert_global_middleware_fails_if_doesnt_exist(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(Kernel $http): void
            {
                $http->pushMiddleware('foo');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'bar' middleware was not registered as global.");

        $this->assertHasGlobalMiddleware('bar');
    }

    public function test_assert_middleware_in_group(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(Kernel $http): void
            {
                $http->appendMiddlewareToGroup('web', 'foo');
            }
        });

        $this->assertHasMiddlewareInGroup('web', 'foo');
    }

    public function test_assert_middleware_in_group_fails_if_group_doesnt_exist(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(Kernel $http): void
            {
                $http->appendMiddlewareToGroup('web', 'foo');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The middleware group 'invalid' is not defined by default.");

        $this->assertHasMiddlewareInGroup('invalid', 'foo');
    }

    public function test_assert_middleware_in_group_fails_if_middleware_doesnt_exist_in_group(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(Kernel $http): void
            {
                $http->appendMiddlewareToGroup('web', 'foo');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The middleware 'bar' is not part of the 'web' group.");

        $this->assertHasMiddlewareInGroup('web', 'bar');
    }

    public function test_assert_gate_has_ability(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider {
            use BootHelpers;

            public function boot(): void
            {
                $this->withGate('foo', fn(?object $user, string $foo) => $foo === 'bar');
            }
        });

        $this->assertGateHasAbility('foo');
    }

    public function test_assert_gate_has_ability_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'foo' is not registered as a gate.");

        $this->assertGateHasAbility('foo');
    }

    public function test_gate_has_policy(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider {
            use BootHelpers;

            public function boot(): void
            {
                $this->withPolicy(User::class, TestPolicy::class);
            }
        });

        $this->assertGateHasPolicy(User::class);
    }

    public function test_gate_has_policy_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The policy for 'Illuminate\Foundation\Auth\User' does not exist.");

        $this->assertGateHasPolicy(User::class);
    }

    public function test_gate_has_policy_with_ability(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider {
            use BootHelpers;

            public function boot(): void
            {
                $this->withPolicy(User::class, TestPolicy::class);
            }
        });

        $this->assertGateHasPolicy(User::class, 'allowed');
    }

    public function test_gate_has_policy_with_ability_fails(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider {
            use BootHelpers;

            public function boot(): void
            {
                $this->withPolicy(User::class, TestPolicy::class);
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'invalid' ability is not declared in the 'Tests\Testing\TestPolicy' policy for 'Illuminate\Foundation\Auth\User'.");

        $this->assertGateHasPolicy(User::class, 'invalid');
    }

    public function test_assert_scheduled(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use BootHelpers;

            public function boot(): void
            {
                $this->withSchedule(static function (Schedule $schedule): void {
                    $schedule->job(\Tests\Job::class)->sundays()->at('09:00');
                    $schedule->command('test:job')->sundays()->at('09:00');
                });
            }
        });

        $this->assertHasScheduledTask(\Tests\Job::class);
        $this->assertHasScheduledTask('test:job');
    }

    public function test_assert_scheduled_job_fails_if_not_found(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use BootHelpers;

            public function boot(): void
            {
                $this->withSchedule(static function (Schedule $schedule): void {
                    $schedule->job(\Tests\Job::class)->sundays()->at('09:00');
                    $schedule->command('test:job')->sundays()->at('09:00');
                });
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'Tests\Invalid' is has not been scheduled");

        $this->assertHasScheduledTask(\Tests\Invalid::class);
    }

    public function test_assert_scheduled_command_fails_if_not_found(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use BootHelpers;

            public function boot(): void
            {
                $this->withSchedule(static function (Schedule $schedule): void {
                    $schedule->job(\Tests\Job::class)->sundays()->at('09:00');
                    $schedule->command('test:job')->sundays()->at('09:00');
                });
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'test:invalid' is has not been scheduled");

        $this->assertHasScheduledTask('test:invalid');
    }

    public function test_assert_scheduled_task_at_date(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use BootHelpers;

            public function boot(): void
            {
                $this->withSchedule(static function (Schedule $schedule): void {
                    $schedule->job(\Tests\Job::class)->sundays()->at('09:00');
                    $schedule->command('test:job')->sundays()->at('09:00');
                });
            }
        });

        $this->assertScheduledTaskRunsAt(\Tests\Job::class, Carbon::now()->weekday(Carbon::SUNDAY)->setTime(9, 0));
        $this->assertScheduledTaskRunsAt('test:job', Carbon::now()->weekday(Carbon::SUNDAY)->setTime(9, 0));
    }

    public function test_assert_scheduled_job_at_date_fails_if_doesnt_exist(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use BootHelpers;

            public function boot(): void
            {
                $this->withSchedule(static function (Schedule $schedule): void {
                    $schedule->job(\Tests\Job::class)->sundays()->at('09:00');
                    $schedule->command('test:job')->sundays()->at('09:00');
                });
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'Tests\Invalid' is has not been scheduled");

        $this->assertScheduledTaskRunsAt(\Tests\Invalid::class, Carbon::now()->weekday(Carbon::SUNDAY)->setTime(9, 0));
    }

    public function test_assert_scheduled_command_at_date_fails_if_doesnt_exist(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use BootHelpers;

            public function boot(): void
            {
                $this->withSchedule(static function (Schedule $schedule): void {
                    $schedule->job(\Tests\Job::class)->sundays()->at('09:00');
                    $schedule->command('test:job')->sundays()->at('09:00');
                });
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'test:invalid' is has not been scheduled");

        $this->assertScheduledTaskRunsAt('test:invalid', Carbon::now()->weekday(Carbon::SUNDAY)->setTime(9, 0));
    }

    public function test_assert_scheduled_job_at_date_fails_if_doesnt_run_at_date(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use BootHelpers;

            public function boot(): void
            {
                $this->withSchedule(static function (Schedule $schedule): void {
                    $schedule->job(\Tests\Job::class)->sundays()->at('09:00');
                    $schedule->command('test:job')->sundays()->at('09:00');
                });
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'Tests\Job' is not scheduled to run at '2019-12-29 10:00:00'.");

        $this->assertScheduledTaskRunsAt(\Tests\Job::class, Carbon::create(2019, 12, 29, 10));
    }

    public function test_assert_scheduled_command_at_date_fails_if_doesnt_run_at_date(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use BootHelpers;

            public function boot(): void
            {
                $this->withSchedule(static function (Schedule $schedule): void {
                    $schedule->job(\Tests\Job::class)->sundays()->at('09:00');
                    $schedule->command('test:job')->sundays()->at('09:00');
                });
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'test:job' is not scheduled to run at '2019-12-29 10:00:00'.");

        $this->assertScheduledTaskRunsAt('test:job', Carbon::create(2019, 12, 29, 10));
    }

    public function test_assert_scheduled_task_at_date_between(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use BootHelpers;

            public function boot(): void
            {
                $this->withSchedule(static function (Schedule $schedule): void {
                    $schedule->job(\Tests\Job::class)->between('09:00', '10:00')->everyFifteenMinutes();
                });
            }
        });

        $this->assertScheduledTaskRunsAt(\Tests\Job::class, Carbon::create(2019, 12, 29, 9, 30));
    }

    public function test_assert_scheduled_task_at_date_between_fails_if_time_not_between(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            use BootHelpers;

            public function boot(): void
            {
                $this->withSchedule(static function (Schedule $schedule): void {
                    $schedule->job(\Tests\Job::class)->between('09:00', '10:00')->everyFifteenMinutes();
                });
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'Tests\Job' is not scheduled to run at '2019-12-29 09:20:00'.");

        $this->assertScheduledTaskRunsAt(\Tests\Job::class, Carbon::create(2019, 12, 29, 9, 20));
    }

    public function test_assert_macro(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                Builder::macro('foo', static fn (): string => 'bar');
                BaseBuilder::macro('baz', static fn (): string => 'quz');
            }
        });

        $this->assertHasMacro(Builder::class, 'foo');
        $this->assertHasMacro(BaseBuilder::class, 'baz');
    }

    public function test_assert_macro_fails_if_macro_doesnt_exist(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                Builder::macro('foo', static fn (): string => 'bar');
            }
        });

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The macro 'bar' for \\Illuminate\\Database\\Eloquent\\Builder::class is missing.");

        $this->assertHasMacro(Builder::class, 'bar');
    }
}

class TestPolicy
{
    public function allowed()
    {
        return true;
    }

    public function denied()
    {
        return false;
    }
}
