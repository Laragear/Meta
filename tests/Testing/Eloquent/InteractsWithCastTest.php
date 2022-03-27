<?php

namespace Tests\Testing\Eloquent;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Laragear\Meta\Testing\Eloquent\InteractsWithCast;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\AssertionFailedError;
use function strtoupper;

class InteractsWithCastTest extends TestCase
{
    use InteractsWithCast;

    public function test_assert_casts_to(): void
    {
        $this->cast(TestCastsAttributes::class)
            ->assertCastTo('bar', 'quz');
    }

    public function test_assert_casts_to_array(): void
    {
        $this->cast(TestCastsAttributes::class)
            ->assertCastTo('bar', [
                'test' => 'quz',
                'test_foo' => false,
                'test_bar' => true,
            ]);
    }

    public function test_assert_cast_to_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Failed to assert that the attribute 'test' casts into the expected value.");

        $this->cast(TestCastsAttributes::class)
            ->assertCastTo('bar', 'not_quz');
    }

    public function test_assert_cast_to_raw(): void
    {
        $this->cast(TestCastsAttributes::class)
            ->assertCastToRaw('bar', 'BAR');
    }

    public function test_assert_cast_to_raw_array(): void
    {
        $this->cast(TestCastsAttributes::class)
            ->assertCastToRaw('bar', [
                'test' => 'BAR',
                'test_foo' => false,
                'test_bar' => true,
            ]);
    }

    public function test_assert_cast_to_raw_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("Failed to assert that the attribute 'test' casts into the expected raw value.");

        $this->cast(TestCastsAttributes::class)
            ->assertCastToRaw('bar', 'quz');
    }

    public function test_with_raw_attributes(): void
    {
        $this->cast(TestCastsAttributes::class)
            ->withRawAttributes(['test_baz' => 'baz'])
            ->assertCastToRaw('bar', [
                'test' => 'BAR',
                'test_foo' => false,
                'test_bar' => true,
                'test_baz' => 'baz'
            ]);
    }
}

class TestCastsAttributes implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        return $value === 'BAR' ? 'quz' : $value;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        return [
            $key => strtoupper($value),
            'test_foo' => $value === 'foo',
            'test_bar' => $value === 'bar',
        ];
    }
}
