<?php

namespace Services\Events;

use Illuminate\Support\Fluent;
use Services\Events\Bar\TestInterface;

class Foo implements TestInterface
{
    public function handle()
    {

    }

    public function publicFunction()
    {

    }

    protected function protectedFunction()
    {

    }

    public function reflective(Fluent $fluent): int
    {

    }
}
