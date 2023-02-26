<?php

namespace Laragear\Meta;

use function app;
use function array_filter;
use function class_uses_recursive;
use Closure;
use const DIRECTORY_SEPARATOR;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use function in_array;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Finder\SplFileInfo;
use function trim;
use function ucfirst;

class Discover
{
    /**
     * Project path where all discoveries will be done.
     *
     * @var string
     */
    protected string $projectPath;

    /**
     * If the discovery should be recursive.
     *
     * @var bool
     */
    protected bool $recursive = false;

    /**
     * If the method filtering should also take into account invokable classes.
     *
     * @var bool
     */
    protected bool $invokable = false;

    /**
     * List of filters to iterate on each discovered class.
     *
     * @var array|null[]
     */
    protected array $filters = ['class' => null, 'method' => null, 'property' => null, 'using' => null];

    /**
     * Create a new Discover instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  string  $path
     * @param  string  $basePath
     * @param  string  $baseNamespace
     */
    final public function __construct(
        protected Application $app,
        protected string $path = '',
        protected string $basePath = '',
        protected string $baseNamespace = '',
    ) {
        $this->projectPath = $this->app->basePath();

        if (! $this->baseNamespace) {
            $this->baseNamespace = $this->app->getNamespace();
        }

        if (! $this->basePath) {
            // @phpstan-ignore-next-line
            $this->basePath = Str::of($this->app->path())->after($this->projectPath)->trim(DIRECTORY_SEPARATOR);
        }
    }

    /**
     * Changes the base location and root namespace to discover files.
     *
     * @param  string  $baseNamespace
     * @param  string|null  $basePath
     * @return $this
     */
    public function atNamespace(string $baseNamespace, string $basePath = null): static
    {
        $this->baseNamespace = Str::finish(ucfirst($baseNamespace), '\\');
        $this->basePath = trim($basePath ?: $baseNamespace, '\\');

        return $this;
    }

    /**
     * Search of files recursively.
     *
     * @return $this
     */
    public function recursively(): static
    {
        $this->recursive = true;

        return $this;
    }

    /**
     * Filter classes that are instances of the given classes or interfaces.
     *
     * @param  string  ...$classes
     * @return $this
     */
    public function instanceOf(string ...$classes): static
    {
        $this->filters['class'] = static function (ReflectionClass $class) use ($classes): bool {
            foreach ($classes as $comparable) {
                if (! $class->isSubclassOf($comparable)) {
                    return false;
                }
            }

            return true;
        };

        return $this;
    }

    /**
     * Filter classes implementing the given public methods.
     *
     * @param  string  ...$methods
     * @return $this
     */
    public function withMethod(string ...$methods): static
    {
        $this->filters['method'] = function (ReflectionClass $class) use ($methods): bool {
            if ($this->invokable && ! in_array('__invoke', $methods, true)) {
                $methods[] = '__invoke';
            }

            foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (Str::is($methods, $method->getName())) {
                    return true;
                }
            }

            return false;
        };

        return $this;
    }

    /**
     * Filter classes implementing the given method using a callback for the ReflectionMethod object.
     *
     * @param  string  $method
     * @param  \Closure(\ReflectionMethod):bool  $callback
     * @return $this
     */
    public function withMethodReflection(string $method, Closure $callback): static
    {
        $this->filters['method'] = static function (ReflectionClass $class) use ($method, $callback): bool {
            return $class->hasMethod($method) && $callback($class->getMethod($method));
        };

        return $this;
    }

    /**
     * Adds the classes that are invokable when filtering by methods.
     *
     * @return $this
     */
    public function orInvokable(): static
    {
        $this->invokable = true;

        return $this;
    }

    /**
     * Filters classes implementing the given public properties.
     *
     * @param  string  ...$properties
     * @return $this
     */
    public function withProperty(string ...$properties): static
    {
        $this->filters['property'] = static function (ReflectionClass $class) use ($properties): bool {
            foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                if (in_array($property->name, $properties, true)) {
                    return true;
                }
            }

            return false;
        };

        return $this;
    }

    /**
     * Filter the classes for those using the given traits, recursively.
     *
     * @param  string  ...$traits
     * @return $this
     */
    public function using(string ...$traits): static
    {
        $this->filters['using'] = static function (ReflectionClass $class) use ($traits): bool {
            foreach (class_uses_recursive($class->getName()) as $trait) {
                if (Str::is($traits, $trait)) {
                    return true;
                }
            }

            return false;
        };

        return $this;
    }

    /**
     * Filter the classes for those using the given traits, without inheritance.
     *
     * @param  string  ...$traits
     * @return $this
     */
    public function parentUsing(string ...$traits): static
    {
        $this->filters['using'] = static function (ReflectionClass $class) use ($traits): bool {
            foreach ($class->getTraitNames() as $trait) {
                if (Str::is($traits, $trait)) {
                    return true;
                }
            }

            return false;
        };

        return $this;
    }

    /**
     * Filters classes implementing the given public properties.
     *
     * @param  string|class-string  ...$attributes
     * @return $this
     */
    public function withAttributes(string ...$attributes): static
    {
        $this->filters['attributes'] = static function (ReflectionClass $class) use ($attributes): bool {
            foreach ($class->getAttributes() as $attribute) {
                if (in_array($attribute->getName(), $attributes, true)) {
                    return true;
                }
            }

            return false;
        };

        return $this;
    }

    /**
     * Returns a Collection for all the classes found.
     *
     * @return \Illuminate\Support\Collection<string, \ReflectionClass>
     */
    public function all(): Collection
    {
        $classes = new Collection;

        $filters = array_filter($this->filters);

        foreach ($this->listAllFiles() as $file) {
            // Try to get the class from the file. If we can't then it's not a class file.
            try {
                $reflection = new ReflectionClass($this->classFromFile($file));
            } catch (ReflectionException) {
                continue;
            }

            // If the class cannot be instantiated (like abstract, traits or interfaces), continue.
            if (! $reflection->isInstantiable()) {
                continue;
            }

            // Preemptively pass this class. Now it's left for the filters to keep allowing it.
            $passes = true;

            // @phpstan-ignore-next-line
            foreach ($filters as $callback) {
                // If the callback returns false, then didn't pass.
                if (! $passes = $callback($reflection)) {
                    break;
                }
            }

            // @phpstan-ignore-next-line
            if ($passes) {
                $classes->put($reflection->name, $reflection);
            }
        }

        return $classes;
    }

    /**
     * Builds the finder instance to locate the files.
     *
     * @return \Illuminate\Support\Collection<int, \Symfony\Component\Finder\SplFileInfo>
     */
    protected function listAllFiles(): Collection
    {
        return new Collection(
            $this->recursive
                ? $this->app->make('files')->allFiles($this->buildPath())
                : $this->app->make('files')->files($this->buildPath())
        );
    }

    /**
     * Build the path to search for files.
     *
     * @return string
     */
    protected function buildPath(): string
    {
        return Str::of($this->path)
            ->when($this->path, static function (Stringable $string): Stringable {
                return $string->start(DIRECTORY_SEPARATOR);
            })
            ->prepend($this->basePath)
            ->start(DIRECTORY_SEPARATOR)
            ->prepend($this->projectPath);
    }

    /**
     * Create a new instance of the discoverer.
     *
     * @param  string  $dir
     * @return static
     */
    public static function in(string $dir): static
    {
        return new static(app(), $dir);
    }

    /**
     * Extract the class name from the given file path.
     *
     * @param  \Symfony\Component\Finder\SplFileInfo  $file
     * @return string
     */
    protected function classFromFile(SplFileInfo $file): string
    {
        return Str::of($file->getRealPath())
            ->after($this->projectPath)
            ->trim(DIRECTORY_SEPARATOR)
            ->beforeLast('.php')
            ->ucfirst()
            ->replace(
                [DIRECTORY_SEPARATOR, ucfirst($this->basePath.'\\')],
                ['\\', $this->baseNamespace],
            );
    }
}
