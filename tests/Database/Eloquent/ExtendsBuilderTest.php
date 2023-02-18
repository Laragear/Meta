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

            protected static function extendThis()
            {
                return 'foo';
            }

            private function extendThat()
            {
            }

            public static function dontExtendThis()
            {
                return 'foo';
            }

            public function dontExtendThat()
            {
            }
        };

        $this->beforeApplicationDestroyed($scope::flushMethods(...));

        $builder->withGlobalScope('something', $scope);

        static::assertTrue($builder->hasMacro('this'));
        static::assertTrue($builder->hasMacro('that'));
        static::assertFalse($builder->hasMacro('dontExtendThis'));
        static::assertFalse($builder->hasMacro('dontExtendThat'));
        static::assertFalse($builder->hasMacro('dontThis'));
        static::assertFalse($builder->hasMacro('dontThat'));
        static::assertFalse($builder->hasMacro('extendThis'));
        static::assertFalse($builder->hasMacro('extendThat'));
        static::assertSame('foo', $builder->this());

        $builder = User::query();

        $builder->withGlobalScope('something', $scope);

        static::assertTrue($builder->hasMacro('this'));
        static::assertTrue($builder->hasMacro('that'));
        static::assertFalse($builder->hasMacro('dontExtendThis'));
        static::assertFalse($builder->hasMacro('dontExtendThat'));
        static::assertFalse($builder->hasMacro('dontThis'));
        static::assertFalse($builder->hasMacro('dontThat'));
        static::assertFalse($builder->hasMacro('extendThis'));
        static::assertFalse($builder->hasMacro('extendThat'));
        static::assertSame('foo', $builder->this());
    }
}
