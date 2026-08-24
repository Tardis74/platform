<?php

namespace App\Core;

class Config
{
    private static array $config = [];

    public static function load(string $envFile): void
    {
        if (!file_exists($envFile)) {
            throw new \RuntimeException('.env file not found');
        }
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            [$key, $value] = explode('=', $line, 2);
            self::$config[trim($key)] = trim($value);
        }
    }

    public static function get(string $key, $default = null)
    {
        return self::$config[$key] ?? $default;
    }
}