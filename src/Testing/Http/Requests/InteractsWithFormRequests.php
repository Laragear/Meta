<?php

namespace Laragear\Meta\Testing\Http\Requests;

use function array_merge;

trait InteractsWithFormRequests
{
    /**
     * Assert a pending form request with data.
     *
     * @param  class-string|string  $formRequest
     * @param  array  $input
     * @param  array  $request
     * @return \Laragear\Meta\Testing\Http\Requests\PendingTestFormRequest
     */
    public function formRequest(string $formRequest, array $input, array $request = []): PendingTestFormRequest
    {
        $request = array_merge([
            'uri' => 'http://localhost/test/request',
            'method' => 'POST',
            'parameters' => $input,
        ], $request);

        $instance = $formRequest::create(...$request);

        $instance->setUserResolver($this->app->make('auth')->userResolver());
        $instance->setRedirector($this->app->make('redirect'));
        $instance->setContainer($this->app);

        return new PendingTestFormRequest($this, $instance);
    }
}
