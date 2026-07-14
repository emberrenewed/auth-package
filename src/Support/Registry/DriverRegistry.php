<?php

declare(strict_types=1);

namespace Technobase\AuthKit\Support\Registry;

use Closure;
use Technobase\AuthKit\Contracts\Drivers\AuthDriver;
use Technobase\AuthKit\Exceptions\DriverNotFoundException;

final class DriverRegistry
{
    /** @var array<string, Closure> */
    private array $factories = [];

    /** @var array<string, AuthDriver> */
    private array $resolved = [];

    public function extend(string $name, Closure $factory): void
    {
        $this->factories[$name] = $factory;
        unset($this->resolved[$name]);

        foreach (array_keys($this->resolved) as $key) {
            if (str_starts_with($key, $name.'.')) {
                unset($this->resolved[$key]);
            }
        }
    }

    public function driver(string $name): AuthDriver
    {
        $flavor = app()->bound('auth-kit.flavor')
            ? (string) app('auth-kit.flavor')
            : 'default';

        $cacheKey = $name.'.'.$flavor;

        if (isset($this->resolved[$cacheKey])) {
            return $this->resolved[$cacheKey];
        }

        if (! isset($this->factories[$name])) {
            throw DriverNotFoundException::make($name);
        }

        return $this->resolved[$cacheKey] = ($this->factories[$name])(app());
    }

    /**
     * @return list<string>
     */
    public function drivers(): array
    {
        return array_keys($this->factories);
    }

    public function register(string $name, Closure $factory): void
    {
        $this->extend($name, $factory);
    }
}
