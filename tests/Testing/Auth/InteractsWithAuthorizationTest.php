<?php

namespace Tests\Testing\Auth;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Laragear\Meta\Testing\Auth\InteractsWithAuthorization;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\AssertionFailedError;

class InteractsWithAuthorizationTest extends TestCase
{
    use InteractsWithAuthorization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(Gate::class)->define('test_gate', static fn ($user, string $bar): bool => $bar === 'bar');
        $this->app->make(Gate::class)->define('test_guest_gate', static fn (?Authenticatable $user, string $bar): bool => ! $user && $bar === 'bar');
    }

    public function test_assert_can(): void
    {
        $this->assertCan('test_guest_gate', 'bar');
    }

    public function test_assert_can_with_authenticated_user(): void
    {
        $this->be(new GenericUser([]));

        $this->assertCan('test_gate', 'bar');
    }

    public function test_assert_can_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'test_guest_gate' ability is not authorized.");

        $this->assertCan('test_guest_gate', 'foo');
    }

    public function test_assert_cant(): void
    {
        $this->assertCant('test_guest_gate', 'foo');
        $this->assertCannot('test_guest_gate', 'foo');
    }

    public function test_assert_cant_with_authenticated_user(): void
    {
        $this->be(new GenericUser([]));

        $this->assertCant('test_gate', 'foo');
        $this->assertCannot('test_gate', 'foo');
    }

    public function test_assert_cant_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'test_guest_gate' ability is authorized.");

        $this->assertCant('test_guest_gate', 'bar');
    }

    public function test_assert_user_can(): void
    {
        $this->assertUserCan(new GenericUser([]), 'test_gate', 'bar');
    }

    public function test_assert_user_can_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'test_gate' ability is not authorized.");

        $this->assertUserCan(new GenericUser([]), 'test_gate', 'foo');
    }

    public function test_assert_user_cant(): void
    {
        $this->assertUserCant(new GenericUser([]), 'test_gate', 'foo');
        $this->assertUserCannot(new GenericUser([]), 'test_gate', 'foo');
    }

    public function test_assert_user_cant_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The 'test_gate' ability is authorized.");

        $this->assertUserCant(new GenericUser([]), 'test_gate', 'bar');
    }
}
