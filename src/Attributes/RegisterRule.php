<?php

namespace Laragear\Meta\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
readonly class RegisterRule
{
    /**
     * Create a new Validation Key instance.
     */
    public function __construct(public string $name, public ?string $translationKey = null, public bool $implicit = false)
    {
        //
    }
}
