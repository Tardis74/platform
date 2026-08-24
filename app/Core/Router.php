<?php

namespace App\Core;

use App\Controllers\BaseController;
use ReflectionMethod;
use RuntimeException;

/**
 * Маршрутизатор API-запросов.
 * Преобразует имя метода вида 'controller.action' в вызов контроллера.
 */
class Router
{
    /**
     * Маршрутизирует запрос на соответствующий контроллер.
     *
     * @param string $methodName Имя метода (например, 'auth.login')
     * @param DB $db Объект БД
     * @param array $payload Данные запроса (GET или POST)
     * @return mixed Результат выполнения контроллера (обычно ApiResponse)
     * @throws RuntimeException Если контроллер или метод не найдены
     */
    public static function route(string $methodName, DB $db, array $payload)
    {
        // Разбиваем на части: контроллер и действие
        $parts = explode('.', $methodName, 2);
        if (count($parts) !== 2) {
            throw new RuntimeException('Invalid method format. Use "controller.action".');
        }

        $controllerName = ucfirst($parts[0]) . 'Controller';
        $action = $parts[1];

        $controllerClass = "App\\Controllers\\$controllerName";

        if (!class_exists($controllerClass)) {
            throw new RuntimeException("Controller '$controllerClass' not found.");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $action)) {
            throw new RuntimeException("Method '$action' not found in '$controllerClass'.");
        }

        // Проверяем, что метод публичный
        $reflection = new ReflectionMethod($controller, $action);
        if (!$reflection->isPublic()) {
            throw new RuntimeException("Method '$action' is not public.");
        }

        // Вызываем метод, передавая $db и $payload
        return $controller->$action($db, $payload);
    }
}