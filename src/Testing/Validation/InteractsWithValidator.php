<?php

namespace Laragear\Meta\Testing\Validation;

use function is_array;

/**
 * @internal
 */
trait InteractsWithValidator
{
    /**
     * Assert a given rule passes.
     *
     * @param  array<string, mixed>|string  $data
     * @param  array<string, array<string, string>|string>|string  $rules
     * @return void
     */
    protected function assertValidationPasses(array|string $data, array|string $rules): void
    {
        if (! is_array($data) && ! is_array($rules)) {
            [$data, $rules] = [[$rules => $data], [$rules => $rules]];
        }

        $validator = $this->app->make('validator')->make($data, $rules);

        static::assertFalse($validator->fails(), 'The rule has not passed validation.');
    }

    /**
     * Assert a given rule fails.
     *
     * @param  array<string, mixed>|string  $data
     * @param  array<string, array<string, string>|string>|string  $rules
     * @return void
     */
    protected function assertValidationFails(array|string $data, array|string $rules): void
    {
        if (! is_array($data) && ! is_array($rules)) {
            [$data, $rules] = [[$rules => $data], [$rules => $rules]];
        }

        $validator = $this->app->make('validator')->make($data, $rules);

        static::assertTrue($validator->fails(), 'The rule has not failed validation.');
    }
}
