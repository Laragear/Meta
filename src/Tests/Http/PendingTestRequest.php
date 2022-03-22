<?php

namespace Laragear\Meta\Tests\Http;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Response;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Orchestra\Testbench\TestCase;
use function array_merge;
use function implode;
use function response;

/**
 * @internal
 */
class PendingTestRequest
{
    /**
     * Create a new pending request.
     *
     * @param  \Orchestra\Testbench\TestCase  $testCase
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  string  $middleware
     * @param  array  $parameters
     * @param  array  $data
     * @param  array  $cookies
     * @param  array  $files
     * @param  array  $additionalMiddleware
     * @param  \Closure|null  $controller
     */
    public function __construct(
        protected TestCase $testCase,
        protected Application $app,
        protected string $middleware,
        protected array $parameters = [],
        protected array $data = [],
        protected array $cookies = [],
        protected array $files = [],
        protected array $additionalMiddleware = [],
        protected ?Closure $controller = null,
    )
    {
        $this->controller = static function (): Response {
            return response();
        };
    }

    /**
     * Adds data to the test Request.
     *
     * @param  array  $data
     * @return $this
     */
    public function withData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Adds the 'web' middleware group to the test Request.
     *
     * @return $this
     */
    public function inWebGroup(): static
    {
        return $this->withMiddleware('web');
    }

    /**
     * Adds additional middleware to the test Request.
     *
     * @param  string  ...$middleware
     * @return $this
     */
    public function withMiddleware(string ...$middleware): static
    {
        $this->additionalMiddleware = $middleware;

        return $this;
    }

    /**
     * Uses a custom controller Closure to respond to the test Request.
     *
     * @param  \Closure  $controller
     * @return $this
     */
    public function receiving(Closure $controller): static
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * Adds multiple cookies to the test Request.
     *
     * @param  array<string, string>  $cookies
     * @return $this
     */
    public function withCookies(array $cookies): static
    {
        $this->cookies = $cookies;

        return $this;
    }

    /**
     * Adds a cookie to the test Request.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function withCookie(string $key, mixed $value): static
    {
        return $this->withCookies(array_merge($this->files, [$key => $value]));
    }

    /**
     * Adds multiple fake files to the test Request.
     *
     * @param  array<string, \Illuminate\Http\Testing\File>  $files
     * @return $this
     */
    public function withFiles(array $files): static
    {
        $this->files = $files;

        return $this;
    }

    /**
     * Adds a fake file to the test Request.
     *
     * @param  string  $key
     * @param  \Illuminate\Http\Testing\File  $file
     * @return $this
     */
    public function withFile(string $key, File $file): static
    {
        return $this->withFiles(array_merge($this->files, [$key => $file]));
    }

    /**
     * Sends a GET Request and receive a test Response.
     *
     * @return \Illuminate\Testing\TestResponse
     */
    public function get(): TestResponse
    {
        return $this->run('GET');
    }

    /**
     * Sends a HEAD Request and receive a test Response.
     *
     * @return \Illuminate\Testing\TestResponse
     */
    public function head(): TestResponse
    {
        return $this->run('HEAD');
    }

    /**
     * Sends a POST Request and receive a test Response.
     *
     * @return \Illuminate\Testing\TestResponse
     */
    public function post(): TestResponse
    {
        return $this->run('POST');
    }

    /**
     * Sends a PUT Request and receive a test Response.
     *
     * @return \Illuminate\Testing\TestResponse
     */
    public function put(): TestResponse
    {
        return $this->run('PUT');
    }

    /**
     * Sends a PATCH Request and receive a test Response.
     *
     * @return \Illuminate\Testing\TestResponse
     */
    public function patch(): TestResponse
    {
        return $this->run('PATCH');
    }

    /**
     * Sends a DELETE Request and receive a test Response.
     *
     * @return \Illuminate\Testing\TestResponse
     */
    public function delete(): TestResponse
    {
        return $this->run('DELETE');
    }

    /**
     * Run the test Request and return a test Response.
     *
     * @param  string  $method
     * @return \Illuminate\Testing\TestResponse
     */
    protected function run(string $method): TestResponse
    {
        if ($this->parameters) {
            $this->middleware .= ':'. implode(',', $this->parameters);
        }

        $route = 'test_'.$method.'_'.Str::random();

        $this->app->make('router')
            ->{$method}($route, $this->controller)
            ->middleware(array_merge($this->additionalMiddleware, [$this->middleware]));

        return $this->testCase->call($method, $route, $this->data, $this->cookies, $this->files);
    }
}
