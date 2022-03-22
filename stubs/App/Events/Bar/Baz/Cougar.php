<?php

namespace App\Events\Bar\Baz;

use App\Events\Bar\Quz;

class Cougar extends Quz
{
    use \App\Events\Bar\Cougar;

    public function handleSomething()
    {
    }
}
