<?php

namespace Laragear\Meta\Testing\Http\Middleware;

use Closure;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Orchestra\Testbench\TestCase;
use function array_merge;
use function array_push;
use function array_unique;
use function in_array;

/**
 * @internal
 * @mixin \Orchestra\Testbench\TestCase
 */
class PendingTestMiddleware
{
    use ForwardsCalls;

    /**
     * The methods that should return a test response.
     *
     * @var array
     */
    protected const PASS_THROUGH = [
        'get',
        'getJson',
        'post',
        'postJson',
        'put',
        'putJson',
        'patch',
        'patchJson',
        'delete',
        'deleteJson',
        'options',
        'optionsJson',
        'json',
        'call',
    ];

    /**
     * Additional middleware for the route.
     *
     * @var array
     */
    protected array $additionalMiddleware = [];

    /**
     * Create a new Pending Test Middleware.
     *
     * @param  \Orchestra\Testbench\TestCase  $testCase
     * @param  \Illuminate\Routing\Router  $router
     * @param  string  $middleware
     * @param  \Closure  $controller
     */
    public function __construct(
        protected TestCase $testCase,
        protected Router $router,
        protected string $middleware,
        protected Closure $controller
    ) {
        //
    }

    /**
     * Use a callback as a controller for the auto-generated route.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function using(callable $callback): static
    {
        $this->controller = Closure::fromCallable($callback);

        return $this;
    }

    /**
     * Sets the auto-generated route to use the 'web' middleware group.
     *
     * @return $this
     */
    public function inWebGroup(): static
    {
        $this->additionalMiddleware[] = 'web';

        return $this;
    }

    /**
     * Adds additional middleware to the auto-generated route.
     *
     * @param  string  ...$middleware
     * @return $this
     */
    public function withRouteMiddleware(string ...$middleware): static
    {
        array_push($this->additionalMiddleware, ...$middleware);

        return $this;
    }

    /**
     * Handle dynamic calls to the Test Case.
     *
     * @param  string  $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (in_array($name, static::PASS_THROUGH, true)) {
            $route = $name === 'call' || $name === 'json'
                ? $arguments[1]
                : $arguments[0];

            $route = Str::beforeLast($route, '?');

            $this->router
                ->addRoute(Router::$verbs, $route, $this->controller)
                ->middleware(array_unique(array_merge($this->additionalMiddleware, [$this->middleware])));

            return $this->forwardCallTo($this->testCase, $name, $arguments);
        }

        return $this->forwardDecoratedCallTo($this->testCase, $name, $arguments);
    }
}
