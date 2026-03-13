# Meta
[![Latest Version on Packagist](https://img.shields.io/packagist/v/laragear/meta.svg)](https://packagist.org/packages/laragear/meta)
[![Latest stable test run](https://github.com/Laragear/Meta/workflows/Tests/badge.svg)](https://github.com/Laragear/Meta/actions)
[![Codecov Coverage](https://codecov.io/gh/Laragear/Meta/graph/badge.svg?token=bogXap7Rjn)](https://codecov.io/gh/Laragear/Meta)
[![Maintainability](https://qlty.sh/badges/69538547-2e27-49d1-9c33-fdc3c7f35f33/maintainability.svg)](https://qlty.sh/gh/Laragear/projects/Meta)
[![Sonarcloud Status](https://sonarcloud.io/api/project_badges/measure?project=Laragear_Meta&metric=alert_status)](https://sonarcloud.io/dashboard?id=Laragear_Meta)
[![Laravel Octane Compatibility](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://laravel.com/docs/13.x/octane#introduction)

A Laravel Package helper for Laravel Packages.

```php
public function boot()
{
    $this->withPublishableMigrations(__DIR__.'/../migrations');
    
    $this->withSchedule(fn($schedule) => $schedule->command('inspire')->hourly());
}
```

## Become a sponsor

[![](.github/assets/support.png)](https://github.com/sponsors/DarkGhostHunter)

Your support allows me to keep this package free, up-to-date and maintainable. Alternatively, you can **spread the word in social media**.

## Requirements

* PHP 8.3 or later
* Laravel 12 or later.

## Installation

Require this package into your project using Composer:

```shell
composer require laragear/meta
```

### Package testers 

You may additionally install testing helpers for your package. These should be installed in your `require-dev` arm of your composer so these are not shipped in the production version of your package.

```shell
composer require --dev laragear/meta-testing
```

### Discoverer

The `Discover` class is a builder that allows discovering classes under a given path. It contains various fluent methods to filter the classes to discover, like methods, properties, interfaces and traits, among others. 

```shell
composer require laragear/discover
```

## Middleware declaration

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

> [!TIP]
> 
> If you need the model being queried, you can always use `getModel()` over the Eloquent Builder instance. 

## Command Helpers

This meta-package includes the `WithEnvironmentFile` helper trait to modify the environment file keys and values.

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

## Attribute extractor

The package contains the `Attr` helper that receives a target class, object, function, and allows to retrieve all or one attribute. Better yet, you can directly call a method or retrieve an attribute property.

```php
use Laragear\Meta\Attr;use Vendor\Package\Attributes\MyCustomAttribute;

#[MyCustomAttribute(color: 'blue')]
class Car
{
    // 
}

$car = new Car;

echo Attr::of($car)->get(MyCustomAttribute::class, 'color'); // "blue"
```

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

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011-2025 Laravel LLC.
