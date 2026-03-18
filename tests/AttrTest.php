<?php

namespace Tests;

use Attribute;
use Error;
use InvalidArgumentException;
use Laragear\Meta\Attr;
use ReflectionClass;

class AttrTest extends TestCase
{
    public function test_resolves_from_reflection(): void
    {
        $attr = Attr::of(new ReflectionClass(StubClass::class));

        static::assertNotEmpty($attr);
    }

    public function test_resolves_from_class_string(): void
    {
        $attr = Attr::of(StubClass::class);
        $result = $attr->all(TestAttribute::class);

        static::assertCount(1, $result);
        static::assertEquals('class', $result->first()->value);
    }

    public function test_resolves_from_object_instance(): void
    {
        $attr = Attr::of(new StubClass());
        $result = $attr->all(TestAttribute::class);

        static::assertCount(1, $result);
        static::assertEquals('class', $result->first()->value);
    }

    public function test_resolves_from_method_string(): void
    {
        $target = StubClass::class.'::stubMethod';
        $attr = Attr::of($target);
        $result = $attr->all(TestAttribute::class);

        static::assertCount(1, $result);
        static::assertEquals('method', $result->first()->value);
    }

    public function test_resolves_method_from_callable_array(): void
    {
        $attr = Attr::of([StubClass::class, 'stubMethod']);
        $result = $attr->all(TestAttribute::class);

        static::assertCount(1, $result);
        static::assertEquals('method', $result->first()->value);
    }

    public function test_resolves_property_from_property_array(): void
    {
        $attr = Attr::of([StubClass::class, 'property']);
        $result = $attr->all(TestAttribute::class);

        static::assertCount(1, $result);
        static::assertEquals('property', $result->first()->value);
    }

    public function test_resolves_from_function_string(): void
    {
        $attr = Attr::of('Tests\stubFunction');
        $result = $attr->all(TestAttribute::class);

        static::assertCount(1, $result);
        static::assertEquals('function', $result->first()->value);
    }

    public function test_resolves_from_closure(): void
    {
        $closure = #[TestAttribute('closure')] function () {
        };

        $attr = Attr::of($closure);
        $result = $attr->all(TestAttribute::class);

        static::assertCount(1, $result);
        static::assertEquals('closure', $result->first()->value);
    }

    public function test_throws_exception_on_invalid_target(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The target must be a class, object, callable, or class-property array.');

        Attr::of(12345)->all();
    }

    public function test_first_returns_single_attribute_instance(): void
    {
        $attr = Attr::of(StubClass::class);
        $instance = $attr->first(TestAttribute::class);

        static::assertInstanceOf(TestAttribute::class, $instance);
        static::assertEquals('class', $instance->value);
    }

    public function test_throws_error_if_attribute_class_does_not_exist(): void
    {
        $attr = Attr::of(StubClass::class);

        $this->expectException(Error::class);

        $attr->first('NonExistentAttribute');
    }

    public function test_retrieves_arguments(): void
    {
        $attr = Attr::of(StubClass::class);

        static::assertSame(['class'], $attr->arguments(TestAttribute::class));
    }

    public function test_retrieves_all_arguments(): void
    {
        $attr = Attr::of(StubClass::class);

        static::assertSame([['class']], $attr->allArguments(TestAttribute::class));
    }

    public function test_emptiness(): void
    {
        $attr = Attr::of(StubClass::class);

        static::assertFalse($attr->isEmpty());
        static::assertTrue($attr->isNotEmpty());
    }

    public function test_test_not_emptiness(): void
    {
        $attr = Attr::of(StubClassWithoutAttributes::class);

        static::assertTrue($attr->isEmpty());
        static::assertFalse($attr->isNotEmpty());
    }

    public function test_has_given_attribute(): void
    {
        $attr = Attr::of(StubClass::class);

        static::assertTrue($attr->has(TestAttribute::class));
        static::assertFalse($attr->has(StubClass::class));
    }

    public function test_missing_given_attribute(): void
    {
        $attr = Attr::of(StubClass::class);

        static::assertFalse($attr->missing(TestAttribute::class));
        static::assertTrue($attr->missing(StubClass::class));
    }

    public function test_get_retrieves_property_from_attribute(): void
    {
        $attr = Attr::of(StubClass::class);
        $value = $attr->get(TestAttribute::class, 'value');

        static::assertEquals('class', $value);
    }

    public function test_call_executes_method_on_attribute(): void
    {
        $attr = Attr::of(StubClass::class);
        $value = $attr->call(TestAttribute::class, 'getValue');

        static::assertEquals('class', $value);
    }

    public function test_all_returns_collection_of_all_attributes_when_empty_params(): void
    {
        $attr = Attr::of(StubClass::class);
        $results = $attr->all();

        static::assertNotEmpty($results);
    }

    public function test_counts(): void
    {
        $attr = Attr::of(StubClass::class);

        static::assertCount(1, $attr);
    }
}

#[Attribute]
class TestAttribute
{
    public function __construct(public string $value = 'default')
    {
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class TestRepeatableAttribute
{
    public function __construct(public string $value = 'default', public ?string $second = null)
    {
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

#[TestAttribute('class')]
class StubClass
{
    #[TestAttribute('property')]
    protected $property = 'value';

    #[TestAttribute('method')]
    public function stubMethod()
    {
    }

    public function stubMethodWithoutAttributes()
    {
    }

    #[TestRepeatableAttribute('first')]
    #[TestRepeatableAttribute(value: 'second')]
    #[TestRepeatableAttribute(second: 'test-argument', value: 'third')]
    public function stubMethodWithMultipleAttributes()
    {
    }
}

class StubClassWithoutAttributes
{
    #[TestAttribute('method')]
    public function stubMethod()
    {

    }
}

#[TestAttribute('function')]
function stubFunction()
{
}
