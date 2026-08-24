<?php

namespace App\Core;

class Router
{
    public static function route(string $method, $db, array $payload)
    {
        [$controllerName, $action] = explode('.', $method);
        $controllerClass = 'App\\Controllers\\' . ucfirst($controllerName) . 'Controller';
        
        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller $controllerClass not found");
        }
        
        $controller = new $controllerClass();
        
        if (!method_exists($controller, $action)) {
            throw new \RuntimeException("Method $action not found in $controllerClass");
        }
        
        $reflection = new \ReflectionMethod($controller, $action);
        if (!$reflection->isPublic()) {
            throw new \RuntimeException("Method '$action' is not public.");
        }
        
        return $controller->$action($db, $payload);
    }
}