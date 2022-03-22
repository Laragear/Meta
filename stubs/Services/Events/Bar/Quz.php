<?php

namespace Services\Events\Bar;

class Quz
{
    public string $publicString;

    protected string $protectedString;

    use Qux;
}
