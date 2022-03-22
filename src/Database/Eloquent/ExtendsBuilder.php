<?php

namespace Laragear\Meta\Database\Eloquent;

use function array_map;
use Illuminate\Database\Eloquent\Builder;
use ReflectionClass;
use ReflectionMethod;
use SplFixedArray;

/**
 * @internal
 */
trait ExtendsBuilder
{
    /**
     * Cache of methods to avoid using ReflectionClass at instancing.
     *
     * @var \SplFixedArray
     */
    protected static SplFixedArray $methods;

    /**
     * Extend the query builder instance with additional methods.
     *
     * This registers all public static methods using their name.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function extend(Builder $query): void
    {
        if (! isset(static::$methods)) {
            static::$methods = SplFixedArray::fromArray(array_map(
                static function (ReflectionMethod $method): string {
                    return $method->getName();
                },
                (new ReflectionClass($this))->getMethods(ReflectionMethod::IS_STATIC | ReflectionMethod::IS_STATIC)
            ));
        }

        foreach (static::$methods as $method) {
            $query->macro($method, [static::class, $method]);
        }
    }
}
