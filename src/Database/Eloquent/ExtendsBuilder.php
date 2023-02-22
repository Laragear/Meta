<?php

namespace Laragear\Meta\Database\Eloquent;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use function lcfirst;
use ReflectionClass;
use ReflectionMethod as Method;
use SplFixedArray;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * @internal
 */
trait ExtendsBuilder
{
    /**
     * Cache of methods to avoid using ReflectionClass at instancing.
     *
     * @var \SplFixedArray|null
     */
    public static ?SplFixedArray $methods;

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
        static::$methods ??= SplFixedArray::fromArray(static::filterMethods()->toArray());

        foreach (static::$methods as [$name, $isStatic, $method]) {
            $query->macro($name, Closure::fromCallable([$isStatic ? static::class : $this, $method]));
        }
    }

    /**
     * Filters the methods of this Scope by those static and public.
     *
     * @return \Illuminate\Support\Collection
     */
    protected static function filterMethods(): Collection
    {
        return Collection::make((new ReflectionClass(static::class))->getMethods())
            ->filter(static function (Method $method): bool {
                return ! $method->isConstructor()
                    && ! $method->isDestructor()
                    && ! $method->isAbstract()
                    && strlen($method->getName()) > 6
                    && str_starts_with($method->getName(), 'extend');
            })
            ->map(static function (Method $method): SplFixedArray {
                return SplFixedArray::fromArray([
                    lcfirst(substr($method->getName(), 6)), $method->isStatic(), $method->getName(),
                ]);
            })
            ->values();
    }

    /**
     * Flushes the methods captured by the trait.
     *
     * @return void
     */
    public static function flushMethods(): void
    {
        static::$methods = null;
    }
}
