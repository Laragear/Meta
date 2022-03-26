<?php

namespace Tests\Testing\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laragear\Meta\Testing\Http\Middleware\InteractsWithMiddleware;
use Orchestra\Testbench\TestCase;

class InteractsWithMiddlewareTest extends TestCase
{
    use InteractsWithMiddleware;

    public function test_creates_universal_route(): void
    {
        $this->middleware(TestMiddleware::class)->get('test/see/method')->assertOk()->assertSee('GET');
        $this->middleware(TestMiddleware::class)->post('test/see/method')->assertOk()->assertSee('POST');
        $this->middleware(TestMiddleware::class)->patch('test/see/method')->assertOk()->assertSee('PATCH');
        $this->middleware(TestMiddleware::class)->put('test/see/method')->assertOk()->assertSee('PUT');
        $this->middleware(TestMiddleware::class)->delete('test/see/method')->assertOk()->assertSee('DELETE');
        $this->middleware(TestMiddleware::class)->options('test/see/method')->assertOk()->assertSee('OPTIONS');

        $this->middleware(TestMiddleware::class)->get('test/see/method')->assertOk()->assertSee('GET');
        $this->middleware(TestMiddleware::class)->postJson('test/see/method')->assertOk()->assertSee('POST');
        $this->middleware(TestMiddleware::class)->patchJson('test/see/method')->assertOk()->assertSee('PATCH');
        $this->middleware(TestMiddleware::class)->putJson('test/see/method')->assertOk()->assertSee('PUT');
        $this->middleware(TestMiddleware::class)->deleteJson('test/see/method')->assertOk()->assertSee('DELETE');
        $this->middleware(TestMiddleware::class)->optionsJson('test/see/method')->assertOk()->assertSee('OPTIONS');

        $this->middleware(TestMiddleware::class)->call('GET', 'test/see/method')->assertOk()->assertSee('GET');
        $this->middleware(TestMiddleware::class)->json('GET', 'test/see/method')->assertOk()->assertSee('GET');
    }

    public function test_uses_arguments(): void
    {
        $this->middleware(TestMiddleware::class, 'bar')->get('test')->assertSee('bar');
    }

    public function test_overwrites_route(): void
    {
        /** @var \Illuminate\Routing\RouteCollection $routes */
        $routes = $this->app->make('router')->getRoutes();

        $this->middleware(TestMiddleware::class)->withRouteMiddleware('web')->get('test/see/method');

        static::assertCount(1, $routes->getRoutes());
        static::assertSame(['web', TestMiddleware::class], Arr::first($routes->getRoutes())->middleware());

        $this->middleware(TestMiddleware::class)->post('test/see/method');

        static::assertCount(1, $routes->getRoutes());
        static::assertSame([TestMiddleware::class], Arr::first($routes->getRoutes())->middleware());
    }

    public function test_adds_web_group_middleware(): void
    {
        $this->middleware(TestMiddleware::class)->inWebGroup()->get('test');

        static::assertSame(
            ['web', TestMiddleware::class],
            Arr::first($this->app->make('router')->getRoutes())->middleware()
        );
    }

    public function test_uses_controller(): void
    {
        $this->middleware(TestMiddleware::class)
            ->using(fn() => 'foo')
            ->get('test')
            ->assertSee('foo');
    }

    public function test_proxies_test_case_method(): void
    {
        $this->middleware(TestMiddleware::class)
            ->withUnencryptedCookie('foo', 'bar')
            ->get('test/see/cookie?cookie=foo')
            ->assertSee('bar');

        $this->middleware(TestMiddleware::class)
            ->withUnencryptedCookie('baz', 'quz')
            ->get('test/see/cookie?cookie=baz')
            ->assertSee('quz');
    }
}

class TestMiddleware
{
    public function handle(Request $request, $next, $argument = null)
    {
        if ($argument) {
            return $argument;
        }

        if (Str::contains($request->path(), 'see/')) {
            return match (Str::afterLast($request->path(), 'see/')) {
                'cookie' => $request->cookie($request->query('cookie')),
                'route' => $request->path(),
                default => $request->getMethod()
            };
        }

        return $next($request);
    }
}
