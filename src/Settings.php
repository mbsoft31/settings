<?php

namespace MBsoft\Settings;

use InvalidArgumentException;
use MBsoft\Settings\Contracts\ConfigurationFactoryInterface;
use MBsoft\Settings\Contracts\ConfigurationInterface;
use MBsoft\Settings\Traits\ConfigFactoryTrait;
use MBsoft\Settings\Traits\FileOperationsTrait;
use RuntimeException;

class Settings implements ConfigurationFactoryInterface, ConfigurationInterface
{
    use ConfigFactoryTrait;
    use FileOperationsTrait;

    protected array $settings = [];

    protected bool $immutable;

    protected array $cache = [];

    protected array $validators = [];

    public function __construct(array $settings = [], bool $immutable = false)
    {
        $this->settings = $settings;
        $this->immutable = $immutable;
    }

    /**
     * Core Configuration Methods
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->settings)) {
            return $this->settings[$key];
        }

        return $this->resolveDotNotation($key, $this->settings) ?? $default;
    }

    public function set(string $key, mixed $value): bool
    {
        if ($this->immutable) {
            throw new RuntimeException('Cannot modify immutable configuration.');
        }

        if (isset($this->validators[$key]) && ! $this->validators[$key]($value)) {
            throw new InvalidArgumentException("Invalid value for key: $key");
        }

        $this->settings = $this->setDotNotation($key, $value, $this->settings);

        return true;
    }

    public function has(string $key): bool
    {
        if (array_key_exists($key, $this->settings)) {
            return true;
        }

        return $this->resolveDotNotation($key, $this->settings) !== null;
    }

    public function remove(string $key): bool
    {
        if ($this->immutable) {
            throw new RuntimeException('Cannot modify immutable configuration.');
        }

        if (array_key_exists($key, $this->settings)) {
            unset($this->settings[$key]);

            return true;
        }

        return $this->removeDotNotation($key, $this->settings);
    }

    public function all(): array
    {
        return $this->settings;
    }

    public function keys(): array
    {
        return array_keys($this->flattenArray($this->settings));
    }

    /**
     * Scoped Configuration Methods
     */
    public function getScoped(string $scope, string $key, mixed $default = null): mixed
    {
        return $this->get("$scope.$key", $default);
    }

    public function setScoped(string $scope, string $key, mixed $value): bool
    {
        return $this->set("$scope.$key", $value);
    }

    public function hasScoped(string $scope, string $key): bool
    {
        return $this->has("$scope.$key");
    }

    /**
     * Cached Access
     */
    public function getCached(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $this->get($key, $default);
        }

        return $this->cache[$key];
    }

    /**
     * Type Casting
     */
    public function getTyped(string $key, string $type, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);

        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            'string' => (string) $value,
            default => $value,
        };
    }

    /**
     * Add Validators
     */
    public function addValidator(string $key, callable $validator): void
    {
        $this->validators[$key] = $validator;
    }

    public static function fromEnvironment(array $keys, string $prefix = '', bool $immutable = false): static
    {
        $settings = [];
        foreach ($keys as $key) {
            $envKey = strtoupper($prefix.$key);
            $settings[$key] = getenv($envKey) ?: null;
        }

        return new static($settings, $immutable);
    }

    /**
     * Utility Methods
     */
    protected function resolveDotNotation(string $key, array $data): mixed
    {
        $keys = explode('.', $key);
        foreach ($keys as $subKey) {
            if (! is_array($data) || ! array_key_exists($subKey, $data)) {
                return null;
            }
            $data = $data[$subKey];
        }

        return $data;
    }

    protected function setDotNotation(string $key, mixed $value, array &$data): array
    {
        $keys = explode('.', $key);
        $current = &$data;
        foreach ($keys as $subKey) {
            if (! isset($current[$subKey]) || ! is_array($current[$subKey])) {
                $current[$subKey] = [];
            }
            $current = &$current[$subKey];
        }
        $current = $value;

        return $data;
    }

    protected function removeDotNotation(string $key, array &$data): bool
    {
        $keys = explode('.', $key);
        $lastKey = array_pop($keys);
        $current = &$data;

        foreach ($keys as $subKey) {
            if (! isset($current[$subKey]) || ! is_array($current[$subKey])) {
                return false;
            }
            $current = &$current[$subKey];
        }

        if (isset($current[$lastKey])) {
            unset($current[$lastKey]);

            return true;
        }

        return false;
    }

    protected function flattenArray(array $data, string $prefix = ''): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? "$prefix.$key" : $key;
            if (is_array($value)) {
                $result += $this->flattenArray($value, $fullKey);
            } else {
                $result[$fullKey] = $value;
            }
        }

        return $result;
    }
}
