<?php

namespace Laragear\Meta;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Routing\Router;
use function is_callable;
use function is_string;
use Laragear\Meta\Http\Middleware\MiddlewareDeclaration;

trait BootHelpers
{
    /**
     * Extends a manager-like service.
     *
     * @param  string  $service
     * @param  string|array  $driver
     * @param  callable|string|null  $callback
     * @return void
     */
    protected function withDriver(string $service, string|array $driver, callable|string $callback = null): void
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
        callable|string $message = null,
        bool $implicit = false
    ): void {
        $this->callAfterResolving(
            'validator',
            static function (Factory $validator, Application $app) use ($message, $callback, $implicit, $rule): void {
                $message = is_callable($message) ? $message($validator, $app) : $message;

                $implicit
                    ? $validator->extendImplicit($rule, $callback, $message)
                    : $validator->extend($rule, $callback, $message);
            }
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
            $this->app->make(Router::class), $this->app->make(KernelContract::class), $class
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
     * @return void
     *
     * @see https://laravelpackage.com/06-artisan-commands.html#scheduling-a-command-in-the-service-provider
     */
    protected function withSchedule(callable $callback): void
    {
        if ($this->app->runningInConsole()) {
            $this->callAfterResolving(Schedule::class, static fn (Schedule $schedule): mixed => $callback($schedule));
        }
    }
}
