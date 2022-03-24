<?php

namespace Laragear\Meta\Testing;

use DateTimeInterface;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Manager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * @internal
 */
trait InteractsWithServiceProvider
{
    /**
     * Assert that a service manager contains a given driver.
     *
     * @param  string  $service
     * @param  string  $driver
     * @param  string|null  $class
     * @return void
     */
    protected function assertHasDriver(string $service, string $driver, string $class = null): void
    {
        $instance = $this->app->make($service);

        static::assertInstanceOf(Manager::class, $instance, "The '$service' is not a Manager instance.");
        static::assertContains($driver, $instance->getDrivers(), "The '$service' doesn't have the driver '$driver'.");

        if ($class) {
            static::assertInstanceOf(
                $class, $instance->driver($driver), "The the driver '$driver' is not an instance of '$class'."
            );
        }
    }

    /**
     * Assert the services are registered in the Service Container.
     *
     * @param  string  ...$services
     * @return void
     */
    public function assertServices(string ...$services): void
    {
        foreach ($services as $service) {
            static::assertTrue(
                $this->app->bound($service),
                "The '$service' was not registered in the Service Container."
            );
        }
    }

    /**
     * Assert a service is registered as a shared instance.
     *
     * @param  string  ...$services
     * @return void
     */
    protected function assertHasSingletons(string ...$services): void
    {
        $this->assertServices(...$services);

        foreach ($services as $service) {
            static::assertTrue(
                $this->app->isShared($service),
                "The '$service' is registered as a shared instance in the Service Container."
            );
        }
    }

    /**
     * Assert that the config file is merged into the application using the given key.
     *
     * @param  string  $file
     * @param  string|null  $configKey
     * @return void
     */
    protected function assertConfigMerged(string $file, string $configKey = null): void
    {
        $configKey ??= Str::of($file)->beforeLast('.php')->afterLast('/')->toString();

        static::assertTrue(
            $this->app->make('config')->has($configKey),
            "The configuration file was not merged as '$configKey'."
        );

        static::assertSame(
            $this->app->make('files')->getRequire($file),
            $this->app->make('config')->get($configKey),
            "The configuration file in '$file' is not the same for '$configKey'."
        );
    }

    /**
     * Asserts that the given files are set to be published.
     *
     * @param  string  $file
     * @param  string  $tag
     * @return void
     */
    protected function assertPublishes(string $file, string $tag): void
    {
        static::assertArrayHasKey($tag, ServiceProvider::$publishGroups, "The '$tag' is not a publishable tag.");

        static::assertContains(
            $file, ServiceProvider::$publishGroups[$tag], "The '$file' is not publishable in the '$tag' tag."
        );
    }

    /**
     * Assert the translation namespace is registered.
     *
     * @param  string  $path
     * @param  string  $namespace
     * @return void
     */
    protected function assertTranslations(string $path, string $namespace): void
    {
        $namespaces = $this->app->make('translator')->getLoader()->namespaces();

        static::assertArrayHasKey($namespace, $namespaces, "The '$namespace' translations were not registered.");
        static::assertSame($path, $namespaces[$namespace], "The '$namespace' does not correspond to the path '$path'.");
    }

    /**
     * Assert the view namespace is registered.
     *
     * @param  string  $path
     * @param  string  $namespace
     * @return void
     */
    protected function assertViews(string $path, string $namespace): void
    {
        $namespaces = $this->app->make('view')->getFinder()->getHints();

        static::assertArrayHasKey($namespace, $namespaces, "The '$namespace' views were not registered.");
        static::assertContains($path, $namespaces[$namespace], "The '$namespace' does not correspond to the path '$path'.");
    }

    /**
     * Assert the blade components are registered.
     *
     * @param  string  $alias
     * @param  string  $component
     * @return void
     */
    protected function assertBladeComponent(string $alias, string $component): void
    {
        $aliases = $this->app->make('blade.compiler')->getClassComponentAliases();

        static::assertArrayHasKey($alias, $aliases, "The '$alias' is not registered as component.");
        static::assertSame($component, $aliases[$alias], "The '$component' component is not registered as '$alias'.");
    }

    /**
     * Assert the blade directives are registered.
     *
     * @param  string  ...$directives
     * @return void
     */
    protected function assertBladeDirectives(string ...$directives): void
    {
        $list = $this->app->make('blade.compiler')->getCustomDirectives();

        foreach ($directives as $directive) {
            static::assertArrayHasKey($directive, $list, "The '$directive' was not registered as a blade directive.");
        }
    }

    /**
     * Assert the validation rules are registered.
     *
     * @param  string  ...$rules
     * @return void
     */
    protected function assertValidationRules(string ...$rules): void
    {
        $extensions = $this->app->make('validator')->make([], [])->extensions;

        foreach ($rules as $rule) {
            static::assertArrayHasKey($rule, $extensions, "The '$rule' rule was not registered in the validator.");
        }
    }

    /**
     * Assert the middleware are aliased.
     *
     * @param  string  $alias
     * @param  string  $middleware
     * @return void
     */
    protected function assertMiddlewareAlias(string $alias, string $middleware): void
    {
        $registered = $this->app->make('router')->getMiddleware();

        static::assertArrayHasKey($alias, $registered, "The '$alias' alias was not registered as middleware.");
        static::assertSame($middleware, $registered[$alias], "The '$middleware' was not aliased as '$alias' middleware.");
    }

    /**
     * Assert the middleware is registered globally.
     *
     * @param  string  ...$middleware
     * @return void
     */
    protected function assertGlobalMiddleware(string ...$middleware): void
    {
        $kernel = $this->app->make(Kernel::class);

        foreach ($middleware as $class) {
            static::assertTrue($kernel->hasMiddleware($class), "The '$class' middleware was not registered as global.");
        }
    }

    /**
     * Assert the middleware is registered in a middleware group.
     *
     * @param  string  $group
     * @param  string  $middleware
     * @return void
     */
    protected function assertMiddlewareInGroup(string $group, string $middleware): void
    {
        $list = $this->app->make(Kernel::class)->getMiddlewareGroups();

        static::assertArrayHasKey($group, $list, "The middleware group '$group' is not defined by default.");
        static::assertContains($middleware, $list[$group], "The middleware '$middleware' is not part of the '$group' group.");
    }

    /**
     * Asserts a task is scheduled.
     *
     * @param  string  $task
     * @return void
     */
    protected function assertScheduledTask(string $task): void
    {
        $contains = Collection::make($this->app->make(Schedule::class)->events())
            ->contains(static function (Event $event) use ($task): bool {
                return Str::of($event->command)->after('artisan')->contains($task)
                    || $event->description === $task;
            });

        static::assertTrue($contains, "The '$task' is has not been scheduled.");
    }

    /**
     * Assert that a scheduled task will run at the given date.
     *
     * @param  string  $task
     * @param  \DateTimeInterface  $date
     * @return void
     */
    protected function assertScheduledTaskRunsAt(string $task, DateTimeInterface $date): void
    {
        $this->assertScheduledTask($task);

        $contains = $this->travelTo($date, function () use ($task): bool {
            return $this->app->make(Schedule::class)->dueEvents($this->app)
                ->contains(static function (Event $event) use ($task): bool {
                    return Str::of($event->command)->after('artisan')->contains($task)
                        || $event->description === $task;
                });
        });

        static::assertTrue($contains, "The '$task' is not scheduled to run at '$date'.");
    }

    /**
     * Assert the given class has registered the given macros.
     *
     * @param  string|class-string  $macroable
     * @param  string  ...$macros
     * @return void
     */
    protected function assertMacro(string $macroable, string ...$macros): void
    {
        $call = $macroable === Builder::class ? 'hasGlobalMacro' : 'hasMacro';

        foreach ($macros as $macro) {
            static::assertTrue($macroable::{$call}($macro), "The macro '$macro' for \\$macroable::class is missing.");
        }
    }
}
