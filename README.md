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

This package contains traits and classes to ease package development and package testing.

> All classes and traits have been marked with the [`@internal` PHPDoc tag](https://docs.phpdoc.org/guide/references/phpdoc/tags/internal.html). This will avoid some IDE to take into account these structural files into autocompletion / intellisense.

## Discoverer

The `Discoverer` class is a builder that allows discovering classes under a given path. It contains various fluent methods to filter the classes to discover, like methods, properties, interfaces and traits, among others. 

You can use this class, let's make an example about a package that needs to list several classes inside `App\Scoreboards`, that include at least one method starting with `handle`, like `handleAwayTeam()`. 

```php
use Laragear\Meta\Discover;
use Vendor\Package\Facades\Scoreboard;

$classes = Discover::in('Scoreboards')->withMethod('handle*')->all();

Scoreboard::register($classes);
```

By default, it always starts from the Application root path and namespace, which are `app` and `App` respectively. If you need to, you can change both independently using `atNamespace()`. 

```php
use Laragear\Meta\Discover;

$classes = Discover::in('Scoreboards')->atNamespace('Score')->withMethod('handle*')->all();
```

In any case, the discovered classes are returned as a `Collection` instance, with instances of `ReflectionClass` to further filter the list to your needs. For example, we will filter all those scoreboards that have the property `hidden`.

```php
use Laragear\Meta\Discover;
use ReflectionClass;

Discover::in('Events')->all()->filter(function (ReflectionClass $class) {
    return $class->hasProperty('hidden');
});
```

> The discovered classes must be always [PSR-4 autoloaded](https://getcomposer.org/doc/04-schema.md#psr-4).

## Boot Helpers

The `BootHelpers` trait adds some convenient Service Provider methods at **boot** time to add rules, middleware, listeners, and subscribers.

```php
public function boot()
{
    // Extends a service manager with a custom driver
    $this->withDriver('cache', 'nfs', fn () => new NfsCacheDriver());
    
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
}
```

### Middleware declaration

When using `withMiddleware()` you will receive a `MiddlewareDeclaration` object with convenient methods to register the middleware globally or inside a group, set it as first/last in the global priority stack, and register an alias for it.

```php
public function boot()
{
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
}
```

## Testing

### Testing the Service Provider

The `InteractsWithServiceProvider` allows to quickly test if the Service Provider of your package has registered all the needed bits of code into the Service Container.

```php
use Orchestra\Testbench\TestCase
use Laragear\Meta\Testing\InteractsWithServiceProvider;

class ServiceProviderTest extends TestCase
{
    use InteractsWithServiceProvider;
    
    public function test_is_registered_as_singleton(): void
    {
        $this->assertHasSingletons(\Vendor\Package\MyService::class);
    }
}
```

The available assertions are in this table:

| Methods                       |                            |                               |
|-------------------------------|----------------------------|-------------------------------|
| `assertServices()`            | `assertViews()`            | `assertMiddlewareInGroup()`   |
| `assertSingletons()`          | `assertBladeComponent()`   | `assertScheduledTask()`       |
| `assertConfigMerged()`        | `assertBladeDirectives()`  | `assertScheduledTaskRunsAt()` |
| `assertPublishes()`           | `assertValidationRules()`  | `assertMacro()`               |
| `assertPublishesMigrations()` | `assertMiddlewareAlias()`  |                               |
| `assertTranslations()`        | `assertGlobalMiddleware()` |                               |

### Service Helpers

The `InteractsWithServices` trait includes helpers to retrieve services from the Service Container and do quick things. 

```php
public function test_something_important(): void
{
    // Get a service from the Service Container, optionally run over a callback.
    $this->service('cache', fn ($cache) => $cache->set('foo', 'bar', 30))
    
    // Run a service once and forgets it, while running a callback over it.
    $this->serviceOnce('blade.compiler', fn($compiler) => $compiler->check('cool'));
    
    // Executes a callback over a REAL service when already mocked.
    $this->unmock('files', function ($files): void {
        $files->copyDirectory('foo', 'bar');
    })
}
```

### Validation

This meta package includes a `InteractsWithValidation` trait, that assert if a rule passes or fails using minimal data. This is useful when creating validation rules and testing them without too much boilerplate.

```php
public function test_validation_rule(): void
{
    // Assert the validation rule passes.
    $this->assertValidationPasses(['test' => 'foo'],['test' => 'my_rule']);
    
    // Assert the validation rule fails.
    $this->assertValidationFails(['test' => 'bar'],['test' => 'my_rule']);
}
```

### Middleware

You can test a middleware easily using the `InteractsWithMiddleware` trait and its `middleware()` method. It creates an on-demand route for the given path before sending a test Request to it, so there is no need to register a route.

```php
use Illuminate\Http\Request;
use Vendor\Package\Http\Middleware\MyMiddleware;
use Laragear\Meta\Testing\Http\Middleware\InteractsWithMiddleware;

public function test_middleware(): void
{
    $response = $this->middleware(MyMiddleware::class)->using(function (Request $request) {
        // ...
    })->post('test', ['foo' => 'bar']);
    
    $response->assertOk();
}
```

It proxies all `MakesHttpRequest` trait methods, like `get()` or `withUnencryptedCookie()`, so you can get creative with testing your middleware.

```php
$this->middleware(MyMiddleware::class, 'test_argument')
    ->withUnencryptedCookie()
    ->be($this->myTestUser)
    ->post('test/route', ['foo' => 'bar'])
    ->assertSee('John');
```

### Form Request

You can test a Form Request if it passes authorization an validation using different data using the `InteractsWithFormRequests` trait. The `formRequest()` requires the Form Request class, and an `array` with the data to include in the request, to test in isolation.

```php
public function test_form_request()
{
    $this->formRequest(MyFormRequest::class, ['foo' => 'bar'])->assertOk();
}
```

## Builder extender

The `ExtendsBuilder` trait allows a [Global Scope](https://laravel.com/docs/eloquent#global-scopes) to extend the instance of the Eloquent Builder with new methods. Simply add public static methods in the scope that receive a `Builder` instance, and optional parameters if you deem so.

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

> If you need the model being queried, you can always use `getModel()` over the Eloquent Builder instance. 

## Command Helpers

This meta package includes command helpers for modifying the environment file, other files, confirm on production, and operate with stub files.

| Trait                        | Description                                                         |
|------------------------------|---------------------------------------------------------------------|
| `WithEnvironmentFile`        | Retrieve and replace environment file keys.                         |
| `WithProductionConfirmation` | Confirm an action on production environments.                       |
| `WithStubs`                  | Copy custom stubs to a destination, while replacing custom strings. |

## Laravel Octane compatibility

- There are no singletons using a stale application instance.
- There are no singletons using a stale config instance.
- There are no singletons using a stale request instance.
- `ExtendsBuilder` only initializes its static property once per Scope.

There should be no problems using this package with Laravel Octane.

## Security

If you discover any security related issues, please email darkghosthunter@gmail.com instead of using the issue tracker.

# License

This specific package version is licensed under the terms of the [MIT License](LICENSE.md), at time of publishing.

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011-2022 Laravel LLC.
