<?php

namespace Laragear\Meta\Testing\Auth;

use Illuminate\Contracts\Auth\Access\Gate;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * @internal
 */
trait InteractsWithAuthorization
{
    /**
     * Assert that the authenticated user is authorized for a given ability.
     *
     * @param  iterable|string  $ability
     * @param  mixed  $parameters
     * @return void
     */
    public function assertCan(iterable|string $ability, mixed $parameters = []): void
    {
        PHPUnit::assertThat(
            $this->app->make(Gate::class)->check($ability, $parameters),
            PHPUnit::isTrue(),
            "The '$ability' ability is not authorized."
        );
    }

    /**
     * Assert that the authenticated user is not authorized for a given ability.
     *
     * @param  iterable|string  $ability
     * @param  mixed  $parameters
     * @return void
     */
    public function assertCannot(iterable|string $ability, mixed $parameters = []): void
    {
        PHPUnit::assertThat(
            $this->app->make(Gate::class)->check($ability, $parameters),
            PHPUnit::isFalse(),
            "The '$ability' ability is authorized."
        );
    }

    /**
     * Assert that the authenticated user is not authorized for a given ability.
     *
     * @param  iterable|string  $ability
     * @param  mixed  $parameters
     * @return void
     */
    public function assertCant(iterable|string $ability, mixed $parameters = []): void
    {
        $this->assertCannot($ability, $parameters);
    }

    /**
     * Assert that the given user is authorized for a given ability.
     *
     * @param  mixed  $user
     * @param  iterable|string  $ability
     * @param  mixed  $parameters
     * @return void
     */
    public function assertUserCan(mixed $user, iterable|string $ability, mixed $parameters = []): void
    {
        PHPUnit::assertThat(
            $this->app->make(Gate::class)->forUser($user)->check($ability, $parameters),
            PHPUnit::isTrue(),
            "The '$ability' ability is not authorized."
        );
    }

    /**
     * Assert that the given user is not authorized for a given ability.
     *
     * @param  mixed  $user
     * @param  iterable|string  $ability
     * @param  mixed  $parameters
     * @return void
     */
    public function assertUserCannot(mixed $user, iterable|string $ability, mixed $parameters = []): void
    {
        PHPUnit::assertThat(
            $this->app->make(Gate::class)->forUser($user)->check($ability, $parameters),
            PHPUnit::isFalse(),
            "The '$ability' ability is authorized."
        );
    }

    /**
     * Assert that the given user is not authorized for a given ability.
     *
     * @param  mixed  $user
     * @param  iterable|string  $ability
     * @param  mixed  $parameters
     * @return void
     */
    public function assertUserCant(mixed $user, iterable|string $ability, mixed $parameters = []): void
    {
        $this->assertUserCannot($user, $ability, $parameters);
    }
}
