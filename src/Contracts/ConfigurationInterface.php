<?php

namespace MBsoft\Settings\Contracts;

interface ConfigurationInterface
{
    /**
     * Get a configuration value by key.
     *
     * @param  string  $key  The configuration key.
     * @param  mixed|null  $default  Default value if key is not found.
     * @return mixed The configuration value or default.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set a configuration value.
     *
     * @param  string  $key  The configuration key.
     * @param  mixed  $value  The value to set.
     * @return bool True on success.
     */
    public function set(string $key, mixed $value): bool;

    /**
     * Check if a configuration key exists.
     *
     * @param  string  $key  The configuration key.
     * @return bool True if the key exists.
     */
    public function has(string $key): bool;

    /**
     * Remove a configuration value by key.
     *
     * @param  string  $key  The configuration key.
     * @return bool True if the key was removed.
     */
    public function remove(string $key): bool;

    /**
     * Retrieve all configuration settings.
     *
     * @return array All configuration settings as an associative array.
     */
    public function all(): array;

    /**
     * Get all configuration keys.
     *
     * @return array An array of all keys.
     */
    public function keys(): array;
}
