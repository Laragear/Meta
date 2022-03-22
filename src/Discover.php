<?php

namespace Laragear\Meta;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use SplFixedArray;
use Symfony\Component\Finder\SplFileInfo;
use function array_filter;
use function class_uses_recursive;
use function in_array;
use function ucfirst;
use const DIRECTORY_SEPARATOR;

/**
 * @internal
 */
class Discover
{
    /**
     * Create a new Discover instance.
     *
     * @param  string  $basePath
     * @param  string  $path
     * @param  string  $rootPath
     * @param  string  $rootNamespace
     * @param  \Illuminate\Support\Collection<string, \ReflectionClass>|null  $classes
     * @param  bool  $recursive
     * @param  bool  $invokable
     * @param  array  $filters
     */
    public function __construct(
        protected string $basePath,
        protected string $path,
        protected string $rootPath,
        protected string $rootNamespace,
        protected ?Collection $classes = null,
        protected bool $recursive = false,
        protected bool $invokable = false,
        protected array $filters = [
            'class' => null, 'method' => null, 'property' => null, 'using' => null,
        ],
    ) {
        //
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
                if (!$class->isSubclassOf($comparable)) {
                    return false;
                }
            }

            return true;
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
     * Filter classes implementing the given public methods.
     *
     * @param  string  ...$methods
     * @return $this
     */
    public function withMethod(string ...$methods): static
    {
        $this->filters['method'] = function (ReflectionClass $class) use ($methods): bool {

            if ($this->invokable && !in_array('__invoke', $methods, true)) {
                $methods[] = '__invoke';
            }

            foreach (SplFixedArray::fromArray($class->getMethods(ReflectionMethod::IS_PUBLIC)) as $method) {
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
     * @param  \Closure<\ReflectionMethod>:bool  $callback
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
     * Filters classes implementing the given public properties.
     *
     * @param  string  ...$properties
     * @return $this
     */
    public function withProperty(string ...$properties): static
    {
        $this->filters['property'] = static function (ReflectionClass $class) use ($properties): bool {
            foreach (SplFixedArray::fromArray($class->getProperties(ReflectionProperty::IS_PUBLIC)) as $property) {
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
            foreach (SplFixedArray::fromArray(array_values(class_uses_recursive($class->getName()))) as $trait) {
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
            foreach (SplFixedArray::fromArray($class->getTraitNames()) as $trait) {
                if (Str::is($traits, $trait)) {
                    return true;
                }
            }

            return false;
        };

        return $this;
    }

    /**
     * Builds the finder instance to locate the files.
     *
     * @return Collection<int, \Symfony\Component\Finder\SplFileInfo>
     */
    protected function getFiles(): Collection
    {
        $path = $this->basePath.DIRECTORY_SEPARATOR.$this->rootPath.DIRECTORY_SEPARATOR.$this->path;

        return new Collection($this->recursive ? File::allFiles($path) : File::files($path));
    }

    /**
     * Returns a Lazy Collection for all the classes found.
     *
     * @return \Illuminate\Support\Collection<string, \ReflectionClass>
     */
    public function all(): Collection
    {
        if (!$this->classes) {
            $this->classes = new Collection();

            $filters = array_filter($this->filters);

            foreach ($this->getFiles() as $file) {
                try {
                    $reflection = new ReflectionClass($this->classFromFile($file));
                } catch (ReflectionException) {
                    continue;
                }

                if (!$reflection->isInstantiable()) {
                    continue;
                }

                $passes = true;

                foreach ($filters as $callback) {
                    if (!$callback($reflection)) {
                        $passes = false;
                        break;
                    }
                }

                if ($passes) {
                    $this->classes->put($reflection->name, $reflection);
                }
            }
        }

        return $this->classes;
    }

    /**
     * Returns a new instance of the Discoverer.
     *
     * @param  string  $path  The path to look for, like `Events` or `Models`.
     * @param  string|null  $rootPath  The base path, like `app` or `services`.
     * @param  string|null  $rootNamespace  The base namespace, like `App` or `Service`.
     * @return static
     */
    public static function in(string $path, string $rootPath = null, string $rootNamespace = null): static
    {
        $app = app();

        // If there is no root path, we will guess it from the application default.
        if (!$rootPath) {
            $rootPath = Str::of($app->path())->after($app->basePath())->ltrim(DIRECTORY_SEPARATOR)->toString();
        }

        $rootNamespace = Str::of($rootNamespace ?? $app->getNamespace())->finish('\\')->toString();

        return new static($app->basePath(), $path, $rootPath, $rootNamespace);
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
            ->after($this->basePath)
            ->trim(DIRECTORY_SEPARATOR)
            ->beforeLast('.php')
            ->ucfirst()
            ->replace(
                [DIRECTORY_SEPARATOR, ucfirst($this->rootPath.'\\')],
                ['\\', $this->rootNamespace],
            );
    }
}
