<?php

namespace Laragear\Meta\Testing\Http;

use JetBrains\PhpStorm\Pure;

/**
 * @internal
 */
trait InteractsWithMiddleware
{
    /**
     * Run a middleware using an empty Request, and returns it.
     *
     * @param  string  $middleware
     * @param  string  ...$parameters
     * @return mixed
     */
    #[Pure]
    protected function testMiddleware(string $middleware, string ...$parameters): PendingTestRequest
    {
        return new PendingTestRequest($this, $this->app, $middleware, $parameters);
    }
}
