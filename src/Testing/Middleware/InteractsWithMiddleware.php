<?php

namespace Laragear\Meta\Testing\Middleware;

use Illuminate\Http\Response;
use function implode;

/**
 * @internal
 */
trait InteractsWithMiddleware
{
    /**
     * Create a new pending test for a middleware.
     *
     * @param  string  $middleware
     * @param  string  ...$parameters
     * @return \Laragear\Meta\Testing\Middleware\PendingTestMiddleware
     */
    protected function middleware(string $middleware, string ...$parameters): PendingTestMiddleware
    {
        if ($parameters) {
            $middleware .= ':'.implode(',', $parameters);
        }

        return new PendingTestMiddleware($this, $this->app->make('router'), $middleware, static function (): Response {
            return new Response();
        });
    }
}
