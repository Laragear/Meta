<?php

namespace Laragear\Meta\Testing\Validation;

/**
 * @internal
 */
trait InteractsWithValidator
{
    /**
     * Assert a given rule passes.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string|array<int, string>  $rules
     * @return void
     */
    protected function assertValidationPasses(array $data = [], array $rules = []): void
    {
        $validator = $this->app->make('validator')->make($data, $rules);

        static::assertFalse($validator->fails(), 'The rule has not passed validation.');
    }

    /**
     * Assert a given rule fails.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string|array<int, string>  $rules
     * @return void
     */
    protected function assertValidationFails(array $data = [], array $rules = []): void
    {
        $validator = $this->app->make('validator')->make($data, $rules);

        static::assertTrue($validator->fails(), 'The rule has not failed validation.');
    }
}
