<?php

namespace Tests\Http\Middleware;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Laragear\Meta\Http\Middleware\MiddlewareDeclaration;
use Laragear\Meta\Testing\InteractsWithServiceProvider;
use Tests\TestCase;

class MiddlewareDeclarationTest extends TestCase
{
    use InteractsWithServiceProvider;

    protected MiddlewareDeclaration $declaration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->declaration = new MiddlewareDeclaration(
            $this->app->make(Router::class),
            $this->app->make(Kernel::class),
            'foo'
        );
    }

    public function test_as(): void
    {
        $this->declaration->as('bar');

        $this->assertHasMiddlewareAlias('bar', 'foo');
    }

    public function test_in_group(): void
    {
        $this->declaration->inGroup('web');

        $this->assertHasMiddlewareInGroup('web', 'foo');
    }

    public function test_globally(): void
    {
        $this->declaration->globally();

        $this->assertHasGlobalMiddleware('foo');
    }

    public function test_first(): void
    {
        $this->declaration->first();

        static::assertSame('foo', $this->app->make(Kernel::class)->getMiddlewarePriority()[0]);
    }

    public function test_last(): void
    {
        $this->declaration->last();

        static::assertSame('foo', $this->app->make(Kernel::class)->getMiddlewarePriority()[9]);
    }

    public function test_shared(): void
    {
        $this->declaration->shared();

        $this->assertHasSingletons('foo');
    }
}
