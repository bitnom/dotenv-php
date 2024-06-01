<?php

namespace Arrilot\DotEnv;

use Arrilot\DotEnv\Exceptions\MissingVariableException;

class DotEnv
{
    /**
     * Key-value storage.
     *
     * @var array
     */
    protected static $variables = [];

    /**
     * Required variables.
     *
     * @var array
     */
    protected static $required = [];

    /**
     * Were variables loaded?
     *
     * @var bool
     */
    protected static $isLoaded = false;

    /**
     * Load .env.php file or array.
     *
     * @param string|array $source
     *
     * @return void
     */
    public static function load($source)
    {
        self::$variables = is_array($source) ? $source : require $source;
        self::$isLoaded = true;

        self::checkRequiredVariables();
    }

    /**
     * Copy all variables to putenv().
     *
     * @param string $prefix
     */
    public static function copyVarsToPutenv($prefix = 'PHP_')
    {
        foreach (self::flatten(self::all()) as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $value = serialize($value);
            }

            putenv("{$prefix}{$key}={$value}");
        }
    }

    /**
     * Copy all variables to $_ENV.
     */
    public static function copyVarsToEnv()
    {
        foreach (self::flatten(self::all()) as $key => $value) {
            $_ENV[$key] = $value;
        }
    }

    /**
     * Copy all variables to $_SERVER.
     */
    public static function copyVarsToServer()
    {
        foreach (self::flatten(self::all()) as $key => $value) {
            $_SERVER[$key] = $value;
        }
    }

    /**
     * Get env variables.
     *
     * @return array
     */
    public static function all()
    {
        return self::$variables;
    }

    /**
     * Get env variable.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::$variables;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set env variable.
     *
     * @param string|array $keys
     * @param mixed        $value
     *
     * @return void
     */
    public static function set($keys, $value = null)
    {
        if (is_array($keys)) {
            self::$variables = array_merge_recursive(self::$variables, $keys);
        } else {
            $keys = explode('.', $keys);
            $temp = &self::$variables;

            foreach ($keys as $key) {
                if (!isset($temp[$key])) {
                    $temp[$key] = [];
                }
                $temp = &$temp[$key];
            }

            $temp = $value;
        }
    }

    /**
     * Set required variables.
     *
     * @param array $variables
     */
    public static function setRequired(array $variables)
    {
        self::$required = $variables;

        if (self::$isLoaded) {
            self::checkRequiredVariables();
        }
    }

    /**
     * Delete all variables.
     *
     * @return void
     */
    public static function flush()
    {
        self::$variables = [];
        self::$isLoaded = false;
    }

    /**
     * Throw exception if any of required variables was not loaded.
     *
     * @throws MissingVariableException
     *
     * @return void
     */
    protected static function checkRequiredVariables()
    {
        foreach (self::$required as $key) {
            if (self::get($key) === null) {
                throw new MissingVariableException(".env variable '{$key}' is missing");
            }
        }
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param array  $array
     * @param string $prefix
     *
     * @return array
     */
    protected static function flatten(array $array, $prefix = '')
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $result = array_merge($result, self::flatten($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
