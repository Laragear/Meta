# Meta
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laragear/meta.svg)](https://packagist.org/packages/laragear/meta)
[![Latest stable test run](https://github.com/Laragear/Meta/workflows/Tests/badge.svg)](https://github.com/Laragear/Meta/actions)
[![Codecov coverage](https://codecov.io/gh/Laragear/Meta/branch/1.x/graph/badge.svg?token=bogXap7Rjn)](https://codecov.io/gh/Laragear/Meta)
[![Maintainability](https://api.codeclimate.com/v1/badges/184a74d77f15271129d3/maintainability)](https://codeclimate.com/github/Laragear/Meta/maintainability)
[![Sonarcloud Status](https://sonarcloud.io/api/project_badges/measure?project=Laragear_Meta&metric=alert_status)](https://sonarcloud.io/dashboard?id=Laragear_Meta)
[![Laravel Octane Compatibility](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://laravel.com/docs/9.x/octane#introduction)

A Laravel Package helper for Laravel Packages.

```php
public function boot()
{
    $this->publishMigrations(__DIR__.'/../migrations');
    
    $this->withSchedule(fn($schedule) => $schedule->command('inspire')->hourly());
}
```

## Become a sponsor

[![](.github/assets/support.png)](https://github.com/sponsors/DarkGhostHunter)

Your support allows me to keep this package free, up-to-date and maintainable. Alternatively, you can **[spread the word!](http://twitter.com/share?text=I%20am%20using%20this%20cool%20PHP%20package&url=https://github.com%2FLaragear%2FMeta&hashtags=PHP,Laravel)**

## Requirements

* PHP 8 or later.
* Laravel 9, 10 or later.

## Installation

Require this package into your project using Composer, along with the development-only testers:

```bash
composer require laragear/meta
composer require --dev laragear/meta-testing
```

## Discoverer

The `Discoverer` class is a builder that allows discovering classes under a given path. It contains various fluent methods to filter the classes to discover, like methods, properties, interfaces and traits, among others. 

Let's make an example about a package that needs to list several classes inside `App\Scoreboards`, that include at least one method starting with `handle`, like `handleAwayTeam()`. 

```php
use Laragear\Meta\Discover;
use Vendor\Package\Facades\Scoreboard;

$classes = Discover::in('Scoreboards')->withMethod('handle*')->all();

Scoreboard::register($classes);
```

By default, it always starts from the Application root path and namespace, which are `/app` and `\App` respectively. If you need to, you can change both independently using `atNamespace()`. 

```php
use Laragear\Meta\Discover;

$classes = Discover::in('Scoreboards')->atNamespace('Score')->withMethod('handle*')->all();
```

The discovered classes are returned as a `Collection` instance, with instances of `ReflectionClass` to further filter the list to your needs. For example, we will filter all those scoreboards that have the property `hidden`.

```php
use Laragear\Meta\Discover;
use ReflectionClass;

Discover::in('Events')->all()->filter->hasProperty('hidden');
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

## Builder extender

The `ExtendsBuilder` trait allows a [Global Scope](https://laravel.com/docs/eloquent#global-scopes) to extend the instance of the Eloquent Builder with new methods. Simply start your builder methods `extend`, no matter wich  visibility scope or if the method is static or not. 

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
    
    private function extendWhereAvailable($builder)
    {
        return $builder->where('available_at', '>', now());
    }
    
    protected static function extendWhereColor($builder, string $color)
    {
        return $builder->where('base_color', $color);
    }
}
```

> If you need the model being queried, you can always use `getModel()` over the Eloquent Builder instance. 

## Command Helpers

This meta package includes the `WithEnvironmentFile` helper trait to modify the environment file keys and values.

```php
use Illuminate\Console\Command;
use Laragear\Meta\Console\Commands\WithEnvironmentFile;


class AddServiceKey extends Command
{
    use WithEnvironmentFile;
    
    public function handle()
    {
        // ...
        
        $this->putEnvKey('AWESOME_SERVICE', $this->argument('service_key'))
    }
}
```

## Upgrading

### Testing

Prior version of Laragear Meta contained testing helpers for packages. These have been migrated to [Laragear MetaTesting](https://github.com/Laragear/MetaTesting) separately. You can use these helpers in your project with Composer to install as development dependency:

```bash
composer require --dev laragear/meta-testing
```

### `WithStubs` trait

This trait has been deprecated. Use `Illuminate\Console\GeneratorCommand` instead.

### `WithProductionConfirmation` trait

This trait has been deprecated. Use `Illuminate\Console\ConfirmableTrait` instead.

### `ExtendsBuilder` trait

The trait has been simplified to take advantage of [first-class callables](https://wiki.php.net/rfc/first_class_callable_syntax), which allows to just register any method that starts with `extends`.

There is no need to set methods publicly static.

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

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011-2023 Laravel LLC.
