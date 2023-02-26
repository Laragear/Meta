<?php

namespace App\Events;

use App\Events\Bar\TestInterface;
use Illuminate\Support\Fluent;

#[\MockClass('foo')]
class AttributeClass implements TestInterface
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

    private function privateFunction()
    {
    }

    public function reflective(Fluent $fluent): int
    {
    }
}
