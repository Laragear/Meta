<?php

namespace Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laragear\Meta\Discover;
use Mockery;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Finder\SplFileInfo;
use function realpath;
use function tap;
use const DIRECTORY_SEPARATOR as DS;

class DiscoverTest extends TestCase
{
    protected function file(string $path): Mockery\MockInterface
    {
        return tap(Mockery::mock(SplFileInfo::class), static function (Mockery\MockInterface $mock) use ($path): void {
            $mock->expects('getRealPath')->andReturn(
                Str::of($path)->replace(['\\', '/'], [DS, DS])->toString()
            );
        });
    }

    protected function mockAllFiles(): void
    {
        File::shouldReceive('allFiles')->with($this->app->path('Events'))->andReturn([
            $this->file($this->app->path('Events/Foo.php')),
            $this->file($this->app->path('Events/Bar.php')),
            $this->file($this->app->path('Events/Bar/Quz.php')),
            $this->file($this->app->path('Events/Bar/Baz/Cougar.php')),
        ]);
    }

    public function test_defaults_to_app_namespace_and_path_with_zero_depth(): void
    {
        File::expects('files')->with($this->app->path('Events'))->andReturn([
            $this->file($this->app->path('Events/Foo.php')),
            $this->file($this->app->path('Events/Bar.php')),
        ]);

        $classes = Discover::in('Events')->all();

        static::assertCount(2, $classes);
        static::assertStringContainsString(realpath(__DIR__.'/../stubs/App'), $classes->first()->getFileName());
        static::assertStringContainsString(realpath(__DIR__.'/../stubs/App'), $classes->last()->getFileName());
    }

    public function test_doesnt_adds_file_to_reflection_if_not_autoloaded(): void
    {
        File::expects('files')->with($this->app->path('Events'))->andReturn([
            $this->file($this->app->path('INVALID.php')),
            $this->file($this->app->path('INVALID.php')),
        ]);

        $classes = Discover::in('Events')->all();

        static::assertEmpty($classes);
    }

    public function test_doesnt_adds_traits_abstracts_or_interfaces(): void
    {
        File::expects('files')->with($this->app->path('Events'))->andReturn([
            $this->file($this->app->path('Events/empty.php')),
            $this->file($this->app->path('Events/TestInterface.php')),
            $this->file($this->app->path('Events/Bar/TestInterface.php')),
            $this->file($this->app->path('Events/AbstractClass.php')),
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
            $this->file($this->app->basePath('Services/Events/Foo.php')),
            $this->file($this->app->basePath('Services/Events/Bar.php')),
            $this->file($this->app->basePath('Services/Events/Bar/Quz.php')),
            $this->file($this->app->basePath('Services/Events/Bar/Baz/Cougar.php')),
        ]);

        $classes = Discover::in('Events')->atNamespace('services')->recursively()->all();

        static::assertCount(4, $classes);

        foreach ($classes as $class) {
            static::assertInstanceOf(ReflectionClass::class, $class);
        }
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
    }

    public function test_filters_by_public_method_doesnt_take_hidden_methods(): void
    {
        $this->mockAllFiles();

        $classes = Discover::in('Events')->recursively()->withMethod('protectedFunction', 'privateFunction')->all();

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
                return $method->getReturnType()?->getName() === 'int';
            }
        )->orInvokable()->all();

        static::assertCount(1, $classes);
        static::assertTrue($classes->has(\App\Events\Foo::class));
    }

    public function test_filters_by_method_using_reflection_doesnt_find_non_existent_method(): void
    {
        $this->mockAllFiles();

        $classes = Discover::in('Events')->recursively()->withMethodReflection('invalid', fn () => true)->all();

        static::assertEmpty($classes);
    }

    public function test_filters_by_public_property(): void
    {
        $this->mockAllFiles();

        $classes = Discover::in('Events')->recursively()->withProperty('publicString')->all();

        static::assertCount(2, $classes);
        static::assertTrue($classes->has(\App\Events\Bar\Quz::class));
        static::assertTrue($classes->has(\App\Events\Bar\Baz\Cougar::class));
    }

    public function test_filters_by_public_property_doesnt_find_hidden_properties(): void
    {
        $this->mockAllFiles();

        $classes = Discover::in('Events')->recursively()->withProperty('protectedString', 'privateString')->all();

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

    public function test_filters_by_attribute_names(): void
    {
        File::expects('files')->with($this->app->path('Events'))->andReturn([
            $this->file($this->app->path('Events/AttributeClass.php')),
            $this->file($this->app->path('Events/Bar.php')),
        ]);

        $classes = Discover::in('Events')->withAttributes('MockClass')->all();

        static::assertCount(1, $classes);
        static::assertTrue($classes->has(\App\Events\AttributeClass::class));
    }
}
