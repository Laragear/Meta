<?php

namespace Tests\Console\Commands;

use Illuminate\Support\Facades\File;
use Laragear\Meta\Console\Commands\WithStubs;
use Tests\TestCase;

class WithStubsTest extends TestCase
{
    protected object $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new class {
            use WithStubs;

            public function runPublishStub()
            {
                return $this->publishStub(...func_get_args());
            }

            public function getLaravel()
            {
                return app();
            }
        };
    }

    public function test_adds_stubs(): void
    {
        File::expects('exists')->with('bar')->andReturnFalse();
        File::expects('get')->with('foo')->andReturn('test foo foo test');
        File::expects('put')->with('bar', 'test bar bar test')->andReturnTrue();

        $this->command->runPublishStub('foo', 'bar', [
            'foo' => 'bar'
        ]);
    }

    public function test_doesnt_replaces_stub(): void
    {
        File::expects('exists')->with('bar')->andReturnTrue();
        File::expects('get')->never();
        File::expects('put')->never();

        $this->command->runPublishStub('foo', 'bar', [
            'foo' => 'bar'
        ]);
    }

    public function test_replaces_stub_with_force(): void
    {
        File::expects('exists')->with('bar')->andReturnTrue();
        File::expects('get')->with('foo')->andReturn('test foo foo test');
        File::expects('put')->with('bar', 'test bar bar test')->andReturnTrue();

        $this->command->runPublishStub('foo', 'bar', [
            'foo' => 'bar'
        ], true);
    }
}
