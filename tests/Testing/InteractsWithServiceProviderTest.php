<?php

namespace Tests\Testing;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laragear\Meta\BootHelpers;
use Laragear\Meta\Testing\InteractsWithServiceProvider;
use PHPUnit\Framework\AssertionFailedError;
use Tests\TestCase;

class InteractsWithServiceProviderTest extends TestCase
{
    use InteractsWithServiceProvider;

    public function test_assert_services(): void
    {
        $this->app->instance('foo', 'bar');

        $this->assertServices('foo');
    }

    public function test_assert_services_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'foo' was not registered in the Service Container.");

        $this->assertServices('foo');
    }

    public function test_assert_singletons(): void
    {
        $this->app->instance('foo', 'bar');

        $this->assertSingletons('foo');
    }

    public function test_assert_singletons_fails_if_not_singleton(): void
    {
        $this->app->bind('foo', fn () => 'bar');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'foo' is registered as a shared instance in the Service Container.");

        $this->assertSingletons('foo');
    }

    public function test_assert_singletons_fails_if_not_registered(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'foo' was not registered in the Service Container.");

        $this->assertSingletons('foo');
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

    public function test_assert_translations(): void
    {
        $this->app->register(new class($this->app) extends ServiceProvider
        {
            public function boot(): void
            {
                $this->loadTranslationsFrom('foo', 'bar');
            }
        });

        $this->assertTranslations('foo', 'bar');
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

        $this->assertTranslations('foo', 'quz');
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

        $this->assertTranslations('quz', 'bar');
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

        $this->assertViews('foo', 'bar');
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

        $this->assertViews('foo', 'baz');
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

        $this->assertViews('baz', 'bar');
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

        $this->assertBladeComponent('foo-bar', 'quz');
        $this->assertBladeComponent('foo-cougar', 'cougar');
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

        $this->assertBladeComponent('foo-baz', 'quz');
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

        $this->assertBladeComponent('foo-bar', 'cougar');
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

        $this->assertBladeDirectives('foo');
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

        $this->assertBladeDirectives('bar');
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

        $this->assertValidationRules('foo');
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

        $this->assertValidationRules('bar');
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

        $this->assertMiddlewareAlias('foo', 'bar');
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

        $this->assertMiddlewareAlias('baz', 'bar');
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

        $this->assertMiddlewareAlias('foo', 'baz');
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

        $this->assertGlobalMiddleware('foo');
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

        $this->assertGlobalMiddleware('bar');
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

        $this->assertMiddlewareInGroup('web', 'foo');
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

        $this->assertMiddlewareInGroup('invalid', 'foo');
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

        $this->assertMiddlewareInGroup('web', 'bar');
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

        $this->assertScheduledTask(\Tests\Job::class);
        $this->assertScheduledTask('test:job');
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

        $this->assertScheduledTask(\Tests\Invalid::class);
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

        $this->assertScheduledTask('test:invalid');
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

        $this->assertMacro(Builder::class, 'foo');
        $this->assertMacro(BaseBuilder::class, 'baz');
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

        $this->assertMacro(Builder::class, 'bar');
    }
}
