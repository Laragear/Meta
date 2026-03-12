<?php

namespace Laragear\Meta;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Laragear\Meta\Attributes\RegisterRule;
use Laragear\Meta\Http\Middleware\MiddlewareDeclaration;
use ReflectionClass;

use function array_fill;
use function array_fill_keys;
use function count;
use function is_callable;
use function is_string;

trait BootHelpers
{
    /**
     * Cached closures for registered validation rules.
     *
     * @var array<class-string, \Closure>
     */
    protected static array $cachedValidationRules = [];

    /**
     * Extends a manager-like service.
     *
     * @param  string  $service
     * @param  string|array  $driver
     * @param  callable|string|null  $callback
     * @return void
     */
    protected function withDriver(string $service, string|array $driver, callable|string|null $callback = null): void
    {
        if (is_string($driver)) {
            $driver = [$driver => $callback];
        }

        $this->callAfterResolving($service, static function (object $service) use ($driver): void {
            foreach ($driver as $name => $callback) {
                $service->extend($name, $callback);
            }
        });
    }

    /**
     * Registers one or many validation rules.
     *
     * @param  string  $rule
     * @param  callable|string  $callback
     * @param  callable|string|null  $message
     * @param  bool  $implicit
     * @return void
     */
    protected function withValidationRule(
        string $rule,
        callable|string $callback,
        callable|string|null $message = null,
        bool $implicit = false,
    ): void {
        $this->callAfterResolving(
            'validator',
            static function (Factory $validator, Application $app) use ($message, $callback, $implicit, $rule): void {
                $message = is_callable($message) ? $message($validator, $app) : $message;

                $implicit
                    ? $validator->extendImplicit($rule, $callback, $message)
                    : $validator->extend($rule, $callback, $message);
            },
        );
    }

    /**
     * Registers all Validation Rules found in a directory with a message from a translation prefix.
     *
     * @param  class-string|class-string[]  $classes
     * @param  string  $keyPrefix  If you register a translation key as "my-package", the validation
     *                             rules will use "my-package::validation.{rule}".
     */
    protected function withValidationRulesFrom(string|array $classes, string $keyPrefix): void
    {
        $this->callAfterResolving(
            'validator',
            static function (Factory $validator) use ($classes, $keyPrefix): void {
                foreach ((array) $classes as $class) {
                    if (! isset(static::$cachedValidationRules[$class])) {
                        static::$cachedValidationRules[$class] = [];

                        foreach ((new ReflectionClass($class))->getMethods() as $method) {
                            /** @var \Laragear\Meta\Attributes\RegisterRule|null $attribute */
                            if (
                                $method->isPublic() && $method->isStatic() &&
                                $attribute = Attr::of($method)->first(RegisterRule::class)
                            ) {
                                static::$cachedValidationRules[$class][$attribute->name] = [
                                    $method->getClosure(),
                                    "$keyPrefix::validation.".(
                                        $attribute->translationKey ?: Str::snake($method->getName())
                                    ),
                                    $attribute->implicit,
                                ];
                            }
                        }
                    }

                    foreach (static::$cachedValidationRules[$class] as $name => [$callback, $message, $implicit]) {
                        $implicit
                            ? $validator->extendImplicit($name, $callback, $message)
                            : $validator->extend($name, $callback, $message);
                    }
                }
            },
        );
    }

    /**
     * Returns a middleware declaration.
     *
     * @template  TValue
     *
     * @param  TValue|class-string|string  $class
     * @return \Laragear\Meta\Http\Middleware\MiddlewareDeclaration<TValue>
     */
    protected function withMiddleware(string $class): MiddlewareDeclaration
    {
        return new MiddlewareDeclaration(
            $this->app->make(Router::class), $this->app->make(KernelContract::class), $class,
        );
    }

    /**
     * Registers listeners to run once the given event fires.
     *
     * @param  string  $event
     * @param  string  $listener
     * @return void
     */
    protected function withListener(string $event, string $listener): void
    {
        $this->callAfterResolving('events', static function (Dispatcher $dispatcher) use ($event, $listener): void {
            $dispatcher->listen($event, $listener);
        });
    }

    /**
     * Registers a subscriber to run for each of its multiple events.
     *
     * @param  string  $subscriber
     * @return void
     */
    protected function withSubscriber(string $subscriber): void
    {
        $this->callAfterResolving('events', static function (Dispatcher $dispatcher) use ($subscriber): void {
            $dispatcher->subscribe($subscriber);
        });
    }

    /**
     * Adds a gate into the authorization Gate.
     *
     * @param  string  $name
     * @param  callable|string  $callback
     * @return void
     */
    protected function withGate(string $name, callable|string $callback): void
    {
        $this->callAfterResolving(Gate::class, static fn (Gate $gate): Gate => $gate->define($name, $callback));
    }

    /**
     * Adds a Policy for the given model.
     *
     * @param  class-string|string  $model
     * @param  string  $policy
     * @return void
     */
    protected function withPolicy(string $model, string $policy): void
    {
        $this->callAfterResolving(Gate::class, static fn (Gate $gate): Gate => $gate->policy($model, $policy));
    }

    /**
     * Schedule a Job or Command using a callback.
     *
     * @param  callable(\Illuminate\Console\Scheduling\Schedule):mixed  $callback
     *
     * @see https://www.laravelpackage.com/06-artisan-commands/#scheduling-a-command-in-the-service-provider
     */
    protected function withSchedule(callable $callback): void
    {
        if ($this->app->runningInConsole()) {
            $this->callAfterResolving(Schedule::class, static fn (Schedule $schedule): mixed => $callback($schedule));
        }
    }

    /**
     * Publish migrations into the application database migrations path.
     *
     * @param  string[]|string  $directories
     * @param  string[]|string  $groups
     */
    protected function withPublishableMigrations(array|string $directories, array|string $groups = 'migrations'): void
    {
        if ($this->app->runningInConsole()) {
            $directories = (array) $directories;

            $this->publishesMigrations(array_fill_keys(
                $directories, array_fill(0, count($directories), $this->app->databasePath('migrations')),
            ), $groups);
        }
    }

    /**
     * Registers a simple Blade directive.
     *
     * @param  array<string, callable>|string  $name
     * @param  ($name is string ? callable : null)  $handler
     */
    protected function withBladeDirectives(string|array $name, ?callable $handler = null): void
    {
        $name = $handler ? [$name => $handler] : $name;

        $this->callAfterResolving(
            BladeCompiler::class,
            static function (BladeCompiler $blade) use ($name): void {
                foreach ($name as $key => $handler) {
                    $blade->directive($key, $handler);
                }
            },
        );
    }

    /**
     * Registers a directory of Blade components under a prefix.
     */
    protected function withBladeComponents(string $path, string $prefix): void
    {
        $this->callAfterResolving(
            BladeCompiler::class,
            static function (BladeCompiler $blade) use ($path, $prefix): void {
                $blade->componentNamespace($path, $prefix);
            },
        );
    }

    /**
     * Returns the cached validation rules.
     */
    public static function cachedValidationRules(): array
    {
        return static::$cachedValidationRules;
    }

    /**
     * Flushes cached validation rules retrieved by reflection.
     */
    public static function flushCachedValidationRules(): void
    {
        static::$cachedValidationRules = [];
    }
}
