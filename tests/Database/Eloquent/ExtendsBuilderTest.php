<?php

namespace Tests\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Foundation\Auth\User;
use Laragear\Meta\Database\Eloquent\ExtendsBuilder;
use Tests\TestCase;

class ExtendsBuilderTest extends TestCase
{
    public function test_extends_builder(): void
    {
        $builder = User::query();

        $scope = new class implements Scope
        {
            use ExtendsBuilder;

            public function apply(Builder $builder, Model $model)
            {
                //
            }

            public static function test()
            {
                return 'foo';
            }

            protected static function dontExtend()
            {

            }

            public function alsoDontExtend()
            {

            }
        };

        $builder->withGlobalScope('something', $scope);

        static::assertTrue($builder->hasMacro('test'));
        static::assertFalse($builder->hasMacro('dontExtend'));
        static::assertFalse($builder->hasMacro('alsoDontExtend'));
        static::assertSame('foo', $builder->test());

        $builder = User::query();

        $builder->withGlobalScope('something', $scope);

        static::assertTrue($builder->hasMacro('test'));
        static::assertFalse($builder->hasMacro('dontExtend'));
        static::assertFalse($builder->hasMacro('alsoDontExtend'));
        static::assertSame('foo', $builder->test());
    }
}
