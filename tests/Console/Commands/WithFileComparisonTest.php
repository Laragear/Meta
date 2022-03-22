<?php

namespace Tests\Console\Commands;

use Illuminate\Support\Facades\File;
use Laragear\Meta\Console\Commands\WithFileComparison;
use Tests\TestCase;

class WithFileComparisonTest extends TestCase
{
    protected object $command;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new class {
            use WithFileComparison;

            public function runFileEqualTo(string $comparable, string $path): bool
            {
                return $this->fileEqualTo($comparable, $path);
            }

            public function runFileNotEqualTo(string $comparable, string $path): bool
            {
                return $this->fileNotEqualTo($comparable, $path);
            }

            public function getLaravel()
            {
                return app();
            }
        };
    }

    public function test_files_are_equal(): void
    {
        File::expects('exists')->twice()->with('foo')->andReturnTrue();
        File::expects('exists')->twice()->with('bar')->andReturnTrue();
        File::expects('hash')->twice()->with('foo')->andReturn('hash');
        File::expects('hash')->twice()->with('bar')->andReturn('hash');

        static::assertTrue($this->command->runFileEqualTo('foo', 'bar'));
        static::assertFalse($this->command->runFileNotEqualTo('foo', 'bar'));
    }

    public function test_files_are_not_equal(): void
    {
        File::expects('exists')->twice()->with('foo')->andReturnTrue();
        File::expects('exists')->twice()->with('bar')->andReturnTrue();
        File::expects('hash')->twice()->with('bar')->andReturn('no_hash');
        File::expects('hash')->twice()->with('foo')->andReturn('hash');

        static::assertFalse($this->command->runFileEqualTo('foo', 'bar'));
        static::assertTrue($this->command->runFileNotEqualTo('foo', 'bar'));
    }

    public function test_comparable_file_doesnt_exists(): void
    {
        File::expects('exists')->twice()->with('foo')->andReturnFalse();

        static::assertFalse($this->command->runFileEqualTo('foo', 'bar'));
        static::assertFalse($this->command->runFileNotEqualTo('foo', 'bar'));
    }

    public function test_compared_file_doesnt_exists(): void
    {
        File::expects('exists')->twice()->with('foo')->andReturnTrue();
        File::expects('exists')->twice()->with('bar')->andReturnFalse();

        static::assertFalse($this->command->runFileEqualTo('foo', 'bar'));
        static::assertFalse($this->command->runFileNotEqualTo('foo', 'bar'));
    }
}
