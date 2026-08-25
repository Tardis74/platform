<?php
/**
 * Фронт-контроллер – анализирует параметр route,
 * подключает соответствующий шаблон страницы через общий макет.
 */

$route = $_GET['route'] ?? '';
if (empty($route)) {
    header('Location: /auth/login');
    exit;
}

$parts = explode('/', $route, 2);
if (count($parts) !== 2) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}
$role = $parts[0];
$page = $parts[1];

// Теперь пути ищем внутри public/views/
$viewFile = __DIR__ . "/views/{$role}/{$page}.php";
if (!file_exists($viewFile)) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

$layoutFile = __DIR__ . '/views/layouts/main.php';
if (!file_exists($layoutFile)) {
    die('Layout not found');
}

include $layoutFile;