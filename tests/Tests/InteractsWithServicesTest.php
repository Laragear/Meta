<?php

namespace Tests\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Laragear\Meta\Tests\InteractsWithServices;
use Mockery\MockInterface;
use Tests\TestCase;

class InteractsWithServicesTest extends TestCase
{
    use InteractsWithServices;

    public function test_service(): void
    {
        $this->app->instance('something', $fluent = new Fluent(['foo' => 'bar']));

        static::assertSame($fluent, $this->service('something'));
    }

    public function test_service_with_callback(): void
    {
        $this->app->instance('something', $fluent = new Fluent(['foo' => 'bar']));

        static::assertSame($fluent, $this->service('something', static function (Fluent $fluent): void {
            $fluent->foo = 'quz';
        }));

        static::assertSame('quz', $fluent->foo);
    }

    public function test_service_once(): void
    {
        $this->app->instance('something', $fluent = new Fluent(['foo' => 'bar']));

        static::assertSame($fluent, $this->serviceOnce('something', static function (Fluent $fluent): void {
            $fluent->foo = 'quz';
        }));

        static::assertSame('quz', $fluent->foo);

        static::assertFalse($this->app->has('something'));
    }

    public function test_unmock(): void
    {
        $this->mock('files');

        $collection = new Collection();

        $this->unmock('files', static function (Filesystem $files) use ($collection): void {
            $collection->push(...$files->files(__DIR__));
        });

        static::assertNotEmpty($collection);

        static::assertInstanceOf(MockInterface::class, $this->app->make('files'));
    }
}
