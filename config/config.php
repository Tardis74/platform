<?php

/**
 * Загрузка переменных окружения из .env и предоставление доступа через Config.
 */

namespace App\Core;

class Config
{
    private static array $env = [];

    /**
     * Загружает .env файл из указанной директории.
     *
     * @param string $path Путь к .env файлу
     * @return void
     * @throws \RuntimeException если файл не найден
     */
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException('.env file not found: ' . $path);
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv(sprintf('%s=%s', $name, $value));
            }
            self::$env[$name] = $value;
        }
    }

    /**
     * Получить значение переменной окружения.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}