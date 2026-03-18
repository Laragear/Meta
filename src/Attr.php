<?php

namespace Laragear\Meta;

use Closure;
use Countable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

use function array_values;
use function class_exists;
use function function_exists;
use function is_array;
use function is_object;
use function is_string;
use function method_exists;
use function property_exists;

/** @phpstan-consistent-constructor */
class Attr implements Countable
{
    /**
     * Cached reflection of the target.
     */
    protected ReflectionClass|ReflectionMethod|ReflectionFunction|ReflectionParameter|ReflectionProperty $reflection;

    /**
     * Create a new Attribute instance.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function __construct(mixed $target)
    {
        if (
            $target instanceof ReflectionClass ||
            $target instanceof ReflectionMethod ||
            $target instanceof ReflectionFunction ||
            $target instanceof ReflectionParameter ||
            $target instanceof ReflectionProperty
        ) {
            $this->reflection = $target;
        } elseif (is_string($target)) {
            if (function_exists($target)) {
                $this->reflection = new ReflectionFunction($target);
            } elseif (str_contains($target, '::')) {
                $this->reflection = new ReflectionMethod($target);
            } elseif (class_exists($target)) {
                $this->reflection = new ReflectionClass($target);
            }
        } elseif (is_object($target)) {
            if ($target instanceof Closure) {
                $this->reflection = new ReflectionFunction($target);
            } else {
                $this->reflection = new ReflectionClass($target);
            }
        } elseif (is_array($target)) {
            if (method_exists($target[0], $target[1])) {
                $this->reflection = new ReflectionMethod($target[0], $target[1]);
            }

            if (property_exists($target[0], $target[1])) {
                $this->reflection = new ReflectionProperty($target[0], $target[1]);
            }
        } else {
            throw new InvalidArgumentException(
                'The target must be a class, object, callable, or class-property array.',
            );
        }
    }

    /**
     * Retrieve a collection of all Reflection Attributes.
     *
     * @param  class-string|null  $attribute
     * @return \Illuminate\Support\Collection<int, \ReflectionAttribute>
     */
    protected function collect(?string $attribute): Collection
    {
        return new Collection(
            $this->reflection->getAttributes($attribute, ReflectionAttribute::IS_INSTANCEOF), // @phpstan-ignore-line
        );
    }

    /**
     * Retrieves all the instanced attributes of the target, optionally filtered by the given classes.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>|null  $attribute
     * @return ($attribute is empty ? \Illuminate\Support\Collection<int, object> : \Illuminate\Support\Collection<int, TAttribute>)
     */
    public function all(?string $attribute = null): Collection
    {
        return $this->collect($attribute)->map(static function (ReflectionAttribute $attribute): object {
            return $attribute->newInstance();
        });
    }

    /**
     * Retrieves the first instanced attribute value from a target.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>|null  $attribute
     * @return TAttribute|null
     */
    public function first(?string $attribute = null): ?object
    {
        return $this->collect($attribute)->first()?->newInstance();
    }

    /**
     * Retrieves all the arguments declared for the first given attribute.
     *
     * @param  class-string  $attribute
     * @return scalar[]|null
     */
    public function arguments(string $attribute): ?array
    {
        return $this->collect($attribute)->first()?->getArguments();
    }

    /**
     * Retrieves a Collection of all the arguments for the all declarations of the given attribute.
     *
     * @param  class-string  $attribute
     * @return array<scalar[]>|null
     */
    public function allArguments(string $attribute): ?array
    {
        return $this->collect($attribute)->map(static function (ReflectionAttribute $attribute): array {
            return $attribute->getArguments();
        })->toArray();
    }

    /**
     * Check if the target has no attributes set.
     */
    public function isEmpty(): bool
    {
        return $this->collect(null)->isEmpty();
    }

    /**
     * Check if the target has any attribute set.
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Check if the target has the given attribute.
     */
    public function has(string $attribute): bool
    {
        return $this->collect($attribute)->isNotEmpty();
    }

    /**
     * Check if the target does not have the given attribute.
     */
    public function missing(string $attribute): bool
    {
        return !$this->has($attribute);
    }

    /**
     * Executes a method from the first instanced attribute.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>  $attribute
     */
    public function call(string $attribute, string $method, mixed ...$arguments): mixed
    {
        return $this->first($attribute)?->{$method}(...$arguments);
    }

    /**
     * Retrieves a value from the first instanced attribute.
     *
     * @template TAttribute of object
     *
     * @param  class-string<TAttribute>  $attribute
     */
    public function get(string $attribute, string $property): mixed
    {
        return $this->first($attribute)?->$property;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->collect(null)->count();
    }

    /**
     * Creates a new Attributes instance for the given target.
     *
     * @param  \Closure|object|class-string|callable-string|array{0: string|object, 1: string}  $target
     */
    public static function of(mixed $target): static
    {
        return new static($target);
    }
}
