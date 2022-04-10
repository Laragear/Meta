<?php

namespace Laragear\Meta\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod as Method;
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
        foreach (static::$methods ??= SplFixedArray::fromArray($this->filterMethods()->toArray()) as $method) {
            $query->macro($method, [static::class, $method]);
        }
    }

    /**
     * Filters the methods of this Scope by those static and public.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function filterMethods(): Collection
    {
        return Collection::make((new ReflectionClass($this))->getMethods(Method::IS_PUBLIC | Method::IS_STATIC))
            ->filter(static function (Method $method): bool {
                return $method->isPublic() && $method->isStatic();
            })
            ->map(static function (Method $method): string {
                return $method->getName();
            });
    }
}
