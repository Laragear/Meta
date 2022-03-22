<?php

namespace App\Events;

use App\Events\Bar\TestInterface;
use Illuminate\Support\Fluent;

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
