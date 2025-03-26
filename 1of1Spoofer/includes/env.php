<?php

/**
 * 1of1Spoofer - Environment Configuration Loader
 * 
 * Loads environment variables from .env file and makes them
 * available throughout the application
 */

// Define the root path of the application
defined('ROOT_PATH') or define('ROOT_PATH', realpath(dirname(__FILE__) . '/..'));

/**
 * Load environment variables from .env file
 */
function load_env()
{
    $env_file = ROOT_PATH . '/.env';
    $env_example = ROOT_PATH . '/.env.example';

    // Check if .env file exists, otherwise use .env.example
    $file_to_load = file_exists($env_file) ? $env_file : $env_example;

    if (!file_exists($file_to_load)) {
        error_log("Warning: No .env or .env.example file found!");
        return false;
    }

    // Load the .env file
    $lines = file($file_to_load, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse the line
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Remove quotes if present
        if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
            $value = substr($value, 1, -1);
        } elseif (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) {
            $value = substr($value, 1, -1);
        }

        // Set environment variable
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
    }

    return true;
}

/**
 * Get environment variable with fallback
 * 
 * @param string $key The environment variable name
 * @param mixed $default The default value if not found
 * @return mixed The environment variable value or default
 */
function env($key, $default = null)
{
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    // Convert certain strings to their appropriate types
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
        case 'empty':
        case '(empty)':
            return '';
    }

    return $value;
}

// Load environment variables
load_env();
