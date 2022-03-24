<?php

namespace Tests\Testing;

use Illuminate\Support\Facades\Validator;
use Laragear\Meta\Testing\Validation\InteractsWithValidator;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\AssertionFailedError;

class InteractsWithValidatorTest extends TestCase
{
    use InteractsWithValidator;

    protected function setUp(): void
    {
        parent::setUp();

        Validator::extend('test_rule', function ($key, $value): bool {
            return $value === 'bar';
        });
    }

    public function test_validates_rule(): void
    {
        $this->assertValidationPasses(['foo' => 'bar'], ['foo' => 'test_rule']);
        $this->assertValidationFails(['foo' => 'invalid'], ['foo' => 'test_rule']);
    }

    public function test_validation_passes_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The rule has not passed validation.');

        $this->assertValidationPasses(['foo' => 'invalid'], ['foo' => 'test_rule']);
    }

    public function test_validation_fails_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The rule has not failed validation.');

        $this->assertValidationFails(['foo' => 'bar'], ['foo' => 'test_rule']);
    }
}
