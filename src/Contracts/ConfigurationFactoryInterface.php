<?php

namespace MBsoft\Settings\Contracts;

use Closure;

interface ConfigurationFactoryInterface
{
    /**
     * Create a new instance from an array of settings.
     *
     * @param  array  $data  Configuration settings.
     * @param  bool  $immutable  Whether the configuration is immutable.
     * @return static A new instance.
     */
    public static function fromArray(array $data, bool $immutable = false): static;

    /**
     * Create a new instance from a PHP array file.
     *
     * @param  string  $path  Path to the PHP file.
     * @param  bool  $immutable  Whether the configuration is immutable.
     * @return static A new instance.
     */
    public static function fromPhpArrayFile(string $path, bool $immutable = false): static;

    /**
     * Create a new instance from a dynamic source (closure, array, or file path).
     *
     * @param  string|array|Closure  $source  The source of the configuration.
     * @param  bool  $immutable  Whether the configuration is immutable.
     * @return static A new instance.
     */
    public static function from(string|array|Closure $source, bool $immutable = false): static;
}
