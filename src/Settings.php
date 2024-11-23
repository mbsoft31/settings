<?php

namespace MBsoft\Settings;

use Closure;
use InvalidArgumentException;
use JsonException;
use MBsoft\Settings\Contracts\ConfigurationFactoryInterface;
use MBsoft\Settings\Contracts\ConfigurationInterface;
use MBsoft\Settings\Enums\ConfigFormat;
use MBsoft\Settings\Exceptions\FileDoesNotExistException;
use MBsoft\Settings\Exceptions\InvalidConfigurationException;
use RuntimeException;

class Settings  implements ConfigurationInterface, ConfigurationFactoryInterface
{
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
        if (key_exists($key, $this->settings)) {
            return $this->settings[$key];
        }
        return $this->resolveDotNotation($key, $this->settings) ?? $default;
    }

    public function set(string $key, mixed $value): bool
    {
        if ($this->immutable) {
            throw new RuntimeException('Cannot modify immutable configuration.');
        }

        if (isset($this->validators[$key]) && !$this->validators[$key]($value)) {
            throw new InvalidArgumentException("Invalid value for key: $key");
        }

        $this->settings = $this->setDotNotation($key, $value, $this->settings);
        return true;
    }

    public function has(string $key): bool
    {
        if (key_exists($key, $this->settings)) {
            return true;
        }
        return $this->resolveDotNotation($key, $this->settings) !== null;
    }

    public function remove(string $key): bool
    {
        if ($this->immutable) {
            throw new RuntimeException('Cannot modify immutable configuration.');
        }

        if (key_exists($key, $this->settings)) {
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
        if (!array_key_exists($key, $this->cache)) {
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
            'int' => (int)$value,
            'float' => (float)$value,
            'bool' => (bool)$value,
            'string' => (string)$value,
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

    /**
     * Factory Methods
     */
    public static function fromArray(array $data, bool $immutable = false): static
    {
        return new static($data, $immutable);
    }

    /**
     * @throws FileDoesNotExistException
     * @throws InvalidConfigurationException
     */
    public static function fromPhpArrayFile(string $path, bool $immutable = false): static
    {
        if (!file_exists($path)) {
            throw new FileDoesNotExistException("File does not exist: $path");
        }

        $data = include $path;
        if (!is_array($data)) {
            throw new InvalidConfigurationException("File must return an array: $path");
        }

        return new static($data, $immutable);
    }

    /**
     * @throws FileDoesNotExistException
     * @throws InvalidConfigurationException
     */
    public static function from(string|array|Closure $source, bool $immutable = false): static
    {
        if (is_callable($source)) {
            $data = call_user_func($source);
            if (!is_array($data)) {
                throw new InvalidConfigurationException("Closure must return an array.");
            }
            return static::fromArray($data, $immutable);
        }

        if (is_array($source)) {
            return static::fromArray($source, $immutable);
        }

        if (is_string($source) && file_exists($source)) {
            return static::fromPhpArrayFile($source, $immutable);
        }

        throw new InvalidConfigurationException("Invalid configuration source: $source");
    }

    public static function fromEnvironment(array $keys, string $prefix = '', bool $immutable = false): static
    {
        $settings = [];
        foreach ($keys as $key) {
            $envKey = strtoupper($prefix . $key);
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
            if (!is_array($data) || !array_key_exists($subKey, $data)) {
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
            if (!isset($current[$subKey]) || !is_array($current[$subKey])) {
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
            if (!isset($current[$subKey]) || !is_array($current[$subKey])) {
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

    /**
     * Save configurations to a file.
     *
     * @throws RuntimeException|JsonException If saving fails.
     */
    public function saveToFile(string $path, ConfigFormat $format): bool
    {
        $content = match ($format) {
            ConfigFormat::PHP => $this->serializeToPhp(),
            ConfigFormat::JSON => $this->serializeToJson(),
            ConfigFormat::YAML => $this->serializeToYaml(),
        };

        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException("Failed to save configuration to file: $path");
        }

        return true;
    }

    /**
     * Load configurations from a file.
     *
     * @throws RuntimeException|JsonException If loading fails or the format is invalid.
     */
    public static function loadFromFile(string $path, ConfigFormat $format, bool $immutable = false): static
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Configuration file does not exist: $path");
        }

        $content = file_get_contents($path);
        $data = match ($format) {
            ConfigFormat::PHP => self::deserializeFromPhp($content),
            ConfigFormat::JSON => self::deserializeFromJson($content),
            ConfigFormat::YAML => self::deserializeFromYaml($content),
        };

        return new static($data, $immutable);
    }

    /**
     * Serialization Methods
     */
    protected function serializeToPhp(): string
    {
        return "<?php\n\nreturn " . var_export($this->settings, true) . ";\n";
    }

    /**
     * @throws JsonException
     */
    protected function serializeToJson(): string
    {
        return json_encode($this->settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    protected function serializeToYaml(): string
    {
        if (!function_exists('yaml_emit')) {
            throw new RuntimeException("YAML support is not enabled.");
        }
        return yaml_emit($this->settings);
    }

    /**
     * Deserialization Methods
     */
    protected static function deserializeFromPhp(string $content): array
    {
        return eval('?>' . $content);
    }

    /**
     * @throws JsonException
     */
    protected static function deserializeFromJson(string $content): array
    {
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    protected static function deserializeFromYaml(string $content): array
    {
        if (!function_exists('yaml_parse')) {
            throw new RuntimeException("YAML support is not enabled.");
        }
        return yaml_parse($content);
    }
}
