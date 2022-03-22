# Meta
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laragear/meta.svg)](https://packagist.org/packages/laragear/meta) [![Latest stable test run](https://github.com/Laragear/Meta/workflows/Tests/badge.svg)](https://github.com/Laragear/Meta/actions) [![Codecov coverage](https://codecov.io/gh/Laragear/Meta/branch/1.x/graph/badge.svg?token=bogXap7Rjn)](https://codecov.io/gh/Laragear/Meta) [![Maintainability](https://api.codeclimate.com/v1/badges/184a74d77f15271129d3/maintainability)](https://codeclimate.com/github/Laragear/Meta/maintainability) [![Sonarcloud Status](https://sonarcloud.io/api/project_badges/measure?project=Laragear_Meta&metric=alert_status)](https://sonarcloud.io/dashboard?id=Laragear_Meta) [![Laravel Octane Compatibility](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://laravel.com/docs/9.x/octane#introduction)

A Laravel Package helper for Laravel Packages.

```php
public function boot()
{
    $this->publishMigrations(__DIR__.'/../migrations');
    
    $this->withSchedule(fn($schedule) => $schedule->command('inspire')->hourly());
}
```

## Keep this package free

[![](.assets/patreon.png)](https://patreon.com/packagesforlaravel)[![](.assets/ko-fi.png)](https://ko-fi.com/DarkGhostHunter)[![](.assets/buymeacoffee.png)](https://www.buymeacoffee.com/darkghosthunter)[![](.assets/paypal.png)](https://www.paypal.com/paypalme/darkghosthunter)

Your support allows me to keep this package free, up-to-date and maintainable. Alternatively, you can **[spread the word!](http://twitter.com/share?text=I%20am%20using%20this%20cool%20PHP%20package&url=https://github.com%2FLaragear%2FMeta&hashtags=PHP,Laravel)**

## Requirements

* PHP 8.0 or later.
* Laravel 9.x or later.

## Installation

Require this package into your project using Composer:

```bash
composer require laragear/meta
```

## Usage

This package contains traits and classes to ease package development and package testing.

All classes and traits have been marked with the [`@internal` PHPDoc tag](https://docs.phpdoc.org/guide/references/phpdoc/tags/internal.html). This will avoid some IDE to take into account these structural files into autocompletion / intellisense.

### Discoverer

The `Discoverer` class is a builder that allows discovering classes under a given path. It contains various fluent methods to filter the classes to discover, like methods, properties, interfaces and traits, among others.

```php
use Laragear\Meta\Discover;
use Vendor\Package\Facades\MyMutator;

$files = Discover::in('Events')->withMethod('handle*')->all();

MyMutator::add($files);
```

It returns a `Collection` instance with instances of `ReflectionClass` to further filter the list.

```php
use Laragear\Meta\Discover;
use ReflectionClass;

Discover::in('Events')->all()->filter(function (ReflectionClass $class) {
    // ...
});
```

### Boot Helpers

The `BootHelpers` trait adds some convenient Service Provider methods at boot time to add rules, middleware, listeners, and subscribers.

```php
// Extends a service manager after it resolves
$this->withExtending('cache', 'nfs', fn () => new NfsCacheDriver());

// Registers a validation rule.
$this->withValidationRule('age', fn($attribute, $value) => $value > 18, 'You are too young!', true);

// Registers a middleware using fluent methods.
$this->withMiddleware(OnlyAdults::class)->as('adults');

// Registers a listener for a given event.
$this->withListener('birthday', GreetOnBirthday::class);

// Registers a subscriber for many events.
$this->withSubscriber(BirthdaySubscriber::class);

// Registers one or many scheduled jobs using a callback.
$this->withSchedule(function ($schedule) {
    $schedule->command('package:something')->everyFifteenMinutes();
}) 
```

#### Middleware declaration

When using `withMiddleware()` you will receive a `MiddlewareDeclaration` object with convenient methods to register the middleware globally or inside a group, set it as first/last in the stack, and register an alias for it.

```php
$declaration = $this->withMiddleware(OnlyAdults::class);

// Make it a shared instance.
$declaration->shared();

// Set an alias
$declaration->as('adults');

// Puts it inside a middleware group.
$declaration->inGroup('web');

// Sets the middleware in the global stack.
$declaration->globally();

// Makes the middleware run first or last in the priority stack.
$declaration->first();
$declaration->last();
```

## Testing

### Testing the Service Provider

The `InteractsWithServiceProvider` allows to quickly test if the Service Provider of your package has registered all the needed bits of code into the Service Container.

```php
use Orchestra\Testbench\TestCase
use Laragear\Meta\Tests\InteractsWithServiceProvider;

class ServiceProviderTest extends TestCase
{
    use InteractsWithServiceProvider
    
    public function test_is_registered_as_singleton(): void
    {
        $this->assertSingletons(\Vendor\Package\MyService::class);
    }
}
```

The available assertions are in this table:

| Methods                |                           |                               |
|------------------------|---------------------------|-------------------------------|
| `assertServices()`     | `assertViews()`           | `assertGlobalMiddleware()`    |
| `assertSingletons()`   | `assertBladeComponent()`  | `assertMiddlewareInGroup()`   |
| `assertConfigMerged()` | `assertBladeDirectives()` | `assertScheduledTask()`       |
| `assertPublishes()`    | `assertValidationRules()` | `assertScheduledTaskRunsAt()` |
| `assertTranslations()` | `assertMiddlewareAlias()` | `assertMacro()`               |

### Service Helpers

The `InteractsWithServices` trait includes helpers to retrieve services from the Service Container and do quick things. 

```php
// Get a service from the Service Container, optionally run over a callback.
$this->service('cache', fn ($cache) => $cache->set('foo', 'bar', 30))

// Run a service once and forgets it, while running a callback over it.
$this->serviceOnce('blade.compiler', fn($compiler) => $compiler->check('cool'));

// Executes a callback over a REAL service when already mocked.
$this->unmock('files', function ($files): void {
    $files->copyDirectory('foo', 'bar');
})
```

### Validation

This meta package includes a `InteractsWithValidation` trait, that assert if a rule passes or fails using minimal data. This is useful when creating validation rules and testing them without too much boilerplate.

```php
// Assert the validation rule passes.
$this->assertValidationPasses(['test' => 'foo'],['test' => 'my_rule']);

// Assert the validation rule fails.
$this->assertValidationFails(['test' => 'bar'],['test' => 'my_rule']);
```

### Middleware

The `InteractsWithMiddleware` trait allows to quickly test a middleware with a temporal random route using `testMiddleware()`. It returns an instance of `PendingRequest`, which you can build with additional data to test a middleware thoughtfully.

```php
$this->testMiddleware('my-middleware')
    ->inWebGroup()
    ->withCookie('foo', 'bar')
    ->get();
```

## Builder extender

The `ExtendsBuilder` trait allows a scope to extend the instance of the Eloquent Builder with new methods. Simply add public static methods in the scope that receive a `Builder` instance, and optional parameters if you deem so.

```php
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laragear\Meta\Database\Eloquent\ExtendsBuilder;

class Cars implements Scope
{
    use ExtendsBuilder;
    
    public function apply(Builder $builder, Model $model)
    {
        // ...
    }
    
    public static function whereAvailable(Builder $builder)
    {
        return $builder->where('available_at', '>', now());
    }
    
    public static function whereColor(Builder $builder, string $color)
    {
        return $builder->where('base_color', $color);
    }
}
```

## Command Helpers

This meta package includes command helpers for modifying the environment file, other files, confirm on production, and operate with stub files.

- `WithEnvironmentFile` trait allows checking and replace environment file keys.
- `WithFileComparison` trait allows checking files existence and equality (hash).
- `WithProductionConfirmation` trait allows to confirm an action on production environments.
- `WithStubs` trait allows copying custom stubs to a destination, while replacing custom strings.


## Laravel Octane compatibility

- There are no singletons using a stale application instance.
- There are no singletons using a stale config instance.
- There are no singletons using a stale request instance.
- There are no static properties being overwritten constantly.

There should be no problems using this package with Laravel Octane.

## Security

If you discover any security related issues, please email darkghosthunter@gmail.com instead of using the issue tracker.

# License

This specific package version is licensed under the terms of the [MIT License](LICENSE.md), at time of publishing.

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011-2022 Laravel LLC.
