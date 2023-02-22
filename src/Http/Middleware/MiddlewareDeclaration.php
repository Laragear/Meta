<?php

namespace Laragear\Meta\Http\Middleware;

use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Routing\Router;

/**
 * @template TValue
 */
class MiddlewareDeclaration
{
    /**
     * Create a new Middleware declaration instance.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @param  \Illuminate\Foundation\Http\Kernel  $kernel
     * @param  class-string|string  $middleware
     */
    public function __construct(
        protected Router $router,
        protected KernelContract $kernel,
        protected string $middleware
    ) {
        //
    }

    /**
     * Aliases the middleware with a friendly name.
     *
     * @param  string  $alias
     * @return $this
     */
    public function as(string $alias): static
    {
        $this->router->aliasMiddleware($alias, $this->middleware);

        return $this;
    }

    /**
     * Registers the middleware in a middleware group.
     *
     * @param  string  $group
     * @return $this
     */
    public function inGroup(string $group): static
    {
        $this->kernel->appendMiddlewareToGroup($group, $this->middleware);

        return $this;
    }

    /**
     * Runs the middleware globally on all routes.
     *
     * @return $this
     */
    public function globally(): static
    {
        $this->kernel->pushMiddleware($this->middleware);

        return $this;
    }

    /**
     * Sets the global middleware priority as first.
     *
     * @return void
     */
    public function first(): void
    {
        $this->kernel->prependToMiddlewarePriority($this->middleware);
    }

    /**
     * Sets the global middleware priority as last.
     *
     * @return void
     */
    public function last(): void
    {
        $this->kernel->appendToMiddlewarePriority($this->middleware);
    }

    /**
     * Makes the middleware a shared instance.
     *
     * @param  (callable(\Illuminate\Contracts\Foundation\Application):TValue)|null  $callback
     * @return $this
     */
    public function shared(callable $callback = null): static
    {
        $this->kernel->getApplication()->singleton($this->middleware, $callback);

        return $this;
    }
}
