<?php

namespace MBsoft\Settings\Traits;

use JsonException;
use MBsoft\Settings\Enums\ConfigFormat;
use RuntimeException;

trait FileOperationsTrait
{
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

    public function serializeToYaml(): string
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

    public static function deserializeFromYaml(string $content): array
    {
        if (!function_exists('yaml_parse')) {
            throw new RuntimeException("YAML support is not enabled.");
        }
        return yaml_parse($content);
    }
}
