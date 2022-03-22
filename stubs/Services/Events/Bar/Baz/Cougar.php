<?php

namespace Services\Events\Bar\Baz;

use Services\Events\Bar\Quz;

class Cougar extends Quz
{
    use \Services\Events\Bar\Cougar;

    public function handleSomething()
    {

    }
}
