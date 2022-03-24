<?php

namespace Tests\Testing\Http;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laragear\Meta\Testing\Http\InteractsWithMiddleware;
use RuntimeException;
use Tests\TestCase;
use function redirect;
use function response;

class InteractsWithMiddlewareTest extends TestCase
{
    use InteractsWithMiddleware;

    protected Closure $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = static function (Request $request): JsonResponse {
            return response()->json($request->route()->computedMiddleware);
        };
    }

    public function test_methods(): void
    {
        $this->middleware(TestMiddleware::class)->get(['method' => 0])->assertOk();
        $this->middleware(TestMiddleware::class)->getJson(['method' => 0])->assertOk();

        $this->middleware(TestMiddleware::class)->post(['method' => 0])->assertOk()->assertSee('POST');
        $this->middleware(TestMiddleware::class)->postJson(['method' => 0])->assertOk()->assertSee('POST');
        $this->middleware(TestMiddleware::class)->put(['method' => 0])->assertOk()->assertSee('PUT');
        $this->middleware(TestMiddleware::class)->putJson(['method' => 0])->assertOk()->assertSee('PUT');
        $this->middleware(TestMiddleware::class)->patch(['method' => 0])->assertOk()->assertSee('PATCH');
        $this->middleware(TestMiddleware::class)->patchJson(['method' => 0])->assertOk()->assertSee('PATCH');
        $this->middleware(TestMiddleware::class)->delete(['method' => 0])->assertOk()->assertSee('DELETE');
        $this->middleware(TestMiddleware::class)->deleteJson(['method' => 0])->assertOk()->assertSee('DELETE');
        $this->middleware(TestMiddleware::class)->options(['method' => 0])->assertOk()->assertSee('OPTIONS');
        $this->middleware(TestMiddleware::class)->optionsJson(['method' => 0])->assertOk()->assertSee('OPTIONS');
        $this->middleware(TestMiddleware::class)->json('get', ['method' => 0])->assertOk()->assertSee('GET');
        $this->middleware(TestMiddleware::class)->call('get', ['method' => 0])->assertOk()->assertSee('GET');
    }

    public function test_with_web_middleware_group(): void
    {
        $this->middleware(TestMiddleware::class)
            ->using($this->controller)
            ->get()
            ->assertJson([TestMiddleware::class]);

        $this->middleware(TestMiddleware::class)
            ->withWebMiddlewareGroup()
            ->using($this->controller)
            ->get()
            ->assertJson(['web', TestMiddleware::class]);
    }

    public function test_with_middleware(): void
    {
        $this->middleware(TestMiddleware::class)
            ->withMiddleware()
            ->using($this->controller)
            ->get()
            ->assertJson([TestMiddleware::class]);

        $this->middleware(TestMiddleware::class)
            ->withMiddleware('guest')
            ->using($this->controller)
            ->get()
            ->assertJson(['guest', TestMiddleware::class]);
    }

    public function test_using(): void
    {
        $this->middleware(TestMiddleware::class)
            ->withMiddleware()
            ->using(static function (): string {
                return 'foo';
            })
            ->get()
            ->assertSee('foo');
    }

    public function test_cookies(): void
    {
        $this->middleware(TestMiddleware::class)
            ->withWebMiddlewareGroup()
            ->withCookies(['foo' => 'bar'])
            ->using(static function (Request $request) {
                \dd($request->cookies);
                return response('doo')->withCookie('baz', $request->cookie('foo'));
            })
            ->get()
            ->assertCookie('baz', 'bar');
    }
}

class TestMiddleware
{
    public function handle(Request $request, $next)
    {
        if ($request->has('throw')) {
            return new RuntimeException('thrown');
        }

        if ($request->get('foo') === 'bar') {
            return response('bar');
        }

        if ($request->has('redirect')) {
            return redirect()->to('/test_redirection');
        }

        if ($request->has('method')) {
            return response($request->getMethod());
        }

        if ($request->has('code')) {
            return response('test_code', $request->code);
        }

        if ($request->has('mirror')) {
            return response($request->mirror);
        }

        return $next($request);
    }
}
