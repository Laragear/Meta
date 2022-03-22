<?php

namespace App\Events\Bar;

class Quz
{
    public string $publicString;

    protected string $protectedString;

    private string $privateString;

    use Qux;
}
