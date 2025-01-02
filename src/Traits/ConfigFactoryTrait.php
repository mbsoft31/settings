<?php

namespace MBsoft\Settings\Traits;

use MBsoft\Settings\Exceptions\FileDoesNotExistException;
use MBsoft\Settings\Exceptions\InvalidConfigurationException;
use Closure;

trait ConfigFactoryTrait
{

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

}
