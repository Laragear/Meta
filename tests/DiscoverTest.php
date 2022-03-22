<?php

namespace Tests;

use Illuminate\Support\Facades\File;
use Laragear\Meta\Discover;
use ReflectionMethod;
use Symfony\Component\Finder\SplFileInfo;
use function realpath;
use const DIRECTORY_SEPARATOR as DS;

class DiscoverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make('files')->copyDirectory(__DIR__ . '/../stubs/App/Events', $this->app->path('Events'));
        $this->app->make('files')->copyDirectory(__DIR__ . '/../stubs/Services', $this->app->basePath('services'));
    }

    protected function tearDown(): void
    {
        $this->app->forgetInstance('files');
        $this->app->make('files')->deleteDirectory($this->app->path('Events'));
        $this->app->make('files')->deleteDirectory($this->app->basePath('services'));

        parent::tearDown();
    }

    protected function mockAllFiles(): void
    {
        File::shouldReceive('allFiles')->with($this->app->path('Events'))->andReturn([
            new SplFileInfo($this->app->path('Events'.DS.'Foo.php'), '', $this->app->path('Events')),
            new SplFileInfo($this->app->path('Events'.DS.'Bar.php'), '', $this->app->path('Events')),
            new SplFileInfo($this->app->path('Events'.DS.'Bar'.DS.'Quz.php'), '', $this->app->path('Events'.DS.'Bar')),
            new SplFileInfo(
                $this->app->path('Events'.DS.'Bar'.DS.'Baz'.DS.'Cougar.php'),
                '',
                $this->app->path('Events'.DS.'Bar'.DS.'Baz')
            ),
        ]);
    }

    public function test_defaults_to_app_namespace_and_path_with_zero_depth(): void
    {
        File::expects('files')->with($this->app->path('Events'))->andReturn([
            new SplFileInfo($this->app->path('Events'.DS.'Foo.php'), '', $this->app->path('Events')),
            new SplFileInfo($this->app->path('Events'.DS.'Bar.php'), '', $this->app->path('Events')),
        ]);

        $classes = Discover::in('Events')->all();

        static::assertCount(2, $classes);
        static::assertStringContainsString(realpath(__DIR__.'/../stubs/App'), $classes->first()->getFileName());
        static::assertStringContainsString(realpath(__DIR__.'/../stubs/App'), $classes->last()->getFileName());
    }

    public function test_doesnt_adds_file_to_reflection_if_not_autoloaded(): void
    {
        File::expects('files')->with($this->app->path('Events'))->andReturn([
            new SplFileInfo($this->app->path('INVALID.php'), '', $this->app->path()),
            new SplFileInfo($this->app->path('INVALID.php'), '', $this->app->path()),
        ]);

        $classes = Discover::in('Events')->all();

        static::assertEmpty($classes);
    }

    public function test_doesnt_adds_traits_abstracts_or_interfaces(): void
    {
        File::expects('files')->with($this->app->path('Events'))->andReturn([
            new SplFileInfo($this->app->path('Events'.DS.'empty.php'), '', $this->app->path('Events')),
            new SplFileInfo($this->app->path('Events'.DS.'TestInterface.php'), '', $this->app->path('Events')),
            new SplFileInfo($this->app->path('Events'.DS.'Bar'.DS.'TestInterface.php'), '', $this->app->path('Events')),
            new SplFileInfo($this->app->path('Events'.DS.'AbstractClass.php'), '', $this->app->path('Events')),
        ]);

        $classes = Discover::in('Events')->all();

        static::assertEmpty($classes);
    }

    public function test_uses_recursively(): void
    {
        $this->mockAllFiles();

        $classes = Discover::in('Events')->recursively()->all();

        static::assertCount(4, $classes);
    }


    public function test_uses_different_root_path_and_root_namespace(): void
    {
        File::shouldReceive('allFiles')->with($this->app->basePath('services'.DS.'Events'))->andReturn([
            new SplFileInfo($this->app->basePath('Services'.DS.'Events'.DS.'Foo.php'), '', ''),
            new SplFileInfo($this->app->basePath('Services'.DS.'Events'.DS.'Bar.php'), '', ''),
            new SplFileInfo($this->app->basePath('Services'.DS.'Events'.DS.'Bar'.DS.'Quz.php'), '', ''),
            new SplFileInfo($this->app->basePath('Services'.DS.'Events'.DS.'Bar'.DS.'Baz'.DS.'Cougar.php'), '', ''),
        ]);

        $classes = Discover::in('Events', 'services', 'Services')->recursively()->all();

        static::assertCount(4, $classes);
    }

    public function test_filters_by_instance_of_interface(): void
    {
        $this->mockAllFiles();

        $classes = Discover::in('Events')->recursively()->instanceOf(\App\Events\Bar\TestInterface::class)->all();

        static::assertCount(1, $classes);
        static::assertTrue($classes->has(\App\Events\Foo::class));
    }

    public function test_filters_by_instance_of_class(): void
    {
        $this->mockAllFiles();

        $classes = Discover::in('Events')->recursively()->instanceOf(\App\Events\Bar\Quz::class)->all();

        static::assertCount(1, $classes);
        static::assertTrue($classes->has(\App\Events\Bar\Baz\Cougar::class));
    }

    public function test_filters_by_public_method(): void
    {
        $this->mockAllFiles();

        $classes = Discover::in('Events')->recursively()->withMethod('handle*')->all();

        static::assertCount(2, $classes);
        static::assertTrue($classes->has(\App\Events\Foo::class));
        static::assertTrue($classes->has(\App\Events\Bar\Baz\Cougar::class));

        $classes = Discover::in('Events')->recursively()->withMethod('protectedFunction')->all();

        static::assertEmpty($classes);
    }

    public function test_filters_by_public_method_and_adds_invokable(): void
    {
        $this->mockAllFiles();

        $classes = Discover::in('Events')->recursively()->withMethod('handle*')->orInvokable()->all();

        static::assertCount(3, $classes);
        static::assertTrue($classes->has(\App\Events\Foo::class));
        static::assertTrue($classes->has(\App\Events\Bar::class));
        static::assertTrue($classes->has(\App\Events\Bar\Baz\Cougar::class));
    }

    public function test_filters_by_method_using_reflection(): void
    {
        $this->mockAllFiles();

        $classes = Discover::in('Events')->recursively()->withMethodReflection('reflective',
            function (ReflectionMethod $method): bool {
                return $method->getReturnType()->getName() === 'int';
            }
        )->orInvokable()->all();

        static::assertCount(1, $classes);
        static::assertTrue($classes->has(\App\Events\Foo::class));

        $classes = Discover::in('Events')->recursively()->withMethodReflection('invalid', fn() => true)->all();

        static::assertEmpty($classes);
    }

    public function test_filters_by_public_property(): void
    {
        $this->mockAllFiles();

        $classes = Discover::in('Events')->recursively()->withProperty('publicString')->all();

        static::assertCount(2, $classes);
        static::assertTrue($classes->has(\App\Events\Bar\Quz::class));
        static::assertTrue($classes->has(\App\Events\Bar\Baz\Cougar::class));

        $classes = Discover::in('Events')->recursively()->withProperty('protectedString')->all();

        static::assertEmpty($classes);
    }

    public function test_filters_by_all_traits(): void
    {
        $this->mockAllFiles();

        $classes = Discover::in('Events')->recursively()->using(\App\Events\Bar\Cougar::class)->all();

        static::assertCount(2, $classes);
        static::assertTrue($classes->has(\App\Events\Bar\Quz::class));
        static::assertTrue($classes->has(\App\Events\Bar\Baz\Cougar::class));
    }

    public function test_filters_by_parent_traits(): void
    {
        $this->mockAllFiles();

        $classes = Discover::in('Events')->recursively()->parentUsing(\App\Events\Bar\Cougar::class)->all();

        static::assertCount(1, $classes);
        static::assertTrue($classes->has(\App\Events\Bar\Baz\Cougar::class));
    }
}
