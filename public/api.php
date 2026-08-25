<?php

/**
 * Единая точка входа для всех API-запросов.
 * Выполняет:
 * - загрузку конфигурации и автозагрузки,
 * - подключение к БД,
 * - обработку Rate Limiting,
 * - аутентификацию через JWT (кроме публичных методов),
 * - маршрутизацию через Router,
 * - логирование запросов.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use App\Core\Config;
use App\Core\DB;
use App\Core\ApiResponse;
use App\Core\Auth;
use App\Core\Router;

// Загрузка .env
Config::load(__DIR__ . '/../config/.env');

// Настройка обработки ошибок и исключений
set_exception_handler(function ($e) {
    // Логируем ошибку
    $log = __DIR__ . '/../storage/logs/error.log';
    $message = date('Y-m-d H:i:s') . ' [ERROR] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    file_put_contents($log, $message, FILE_APPEND);

    // Отправляем JSON-ошибку
    $code = $e->getCode() ?: 500;
    ApiResponse::error($e->getMessage(), $code)->send();
});

// Подключение к БД (один раз за запрос)
$db = DB::getInstance();

// Определяем метод запроса
$method = $_GET['method'] ?? '';
if (empty($method)) {
    ApiResponse::error('Method parameter is required.', 400)->send();
}

// Получаем данные: для GET используем $_GET, для POST/JSON - php://input
$payload = [];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $payload = $_GET;
    unset($payload['method']); // убираем метод из данных
} else {
    $input = file_get_contents('php://input');
    if (!empty($input)) {
        $payload = json_decode($input, true) ?? [];
    }
    // Если данные не JSON, то берём $_POST
    if (empty($payload) && !empty($_POST)) {
        $payload = $_POST;
    }
}

// === Rate Limiting (простая реализация) ===
$rateLimit = (int) Config::get('RATE_LIMIT', 60);
$rateWindow = (int) Config::get('RATE_WINDOW', 60);
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$now = time();
$windowStart = $now - $rateWindow;

// Удаляем старые записи (для очистки)
$db->query("DELETE FROM rate_limits WHERE window_start < :window_start", ['window_start' => $windowStart]);

// Проверяем текущее количество запросов за окно
$row = $db->fetch(
    "SELECT count FROM rate_limits WHERE ip = :ip AND window_start >= :window_start ORDER BY window_start DESC LIMIT 1",
    ['ip' => $ip, 'window_start' => $windowStart]
);

if ($row && $row['count'] >= $rateLimit) {
    ApiResponse::error('Too many requests. Please try again later.', 429)->send();
}

// Обновляем счётчик (INSERT ... ON DUPLICATE KEY UPDATE)
$db->query(
    "INSERT INTO rate_limits (ip, window_start, count) VALUES (:ip, :window_start, 1)
     ON DUPLICATE KEY UPDATE count = count + 1",
    ['ip' => $ip, 'window_start' => $now]
);

// === Специальная обработка метода ping (до роутера) ===
if ($method === 'ping') {
    ApiResponse::success('pong')->send();
}

// === JWT Middleware (пропускаем публичные методы) ===
$publicMethods = ['auth.login', 'auth.refresh', 'auth.register', 'student.login'];
$isPublic = in_array($method, $publicMethods);

if (!$isPublic) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
        ApiResponse::error('Missing or invalid Authorization header.', 401)->send();
    }
    $token = $matches[1];
    try {
        Auth::verifyToken($token);
    } catch (\Exception $e) {
        ApiResponse::error($e->getMessage(), 401)->send();
    }
}

// === Логирование запроса (синхронно, но подготовлено к очереди) ===
$startTime = microtime(true);

// === Маршрутизация ===
try {
    $response = Router::route($method, $db, $payload);
    if ($response instanceof ApiResponse) {
        $response->send();
    } else {
        // Если контроллер вернул что-то другое, оборачиваем в успех
        ApiResponse::success($response)->send();
    }
} catch (Exception $e) {
    // Ловится в глобальном обработчике, но мы можем дополнительно обработать
    throw $e;
} finally {
    // Логируем запрос (дата, IP, метод, время выполнения, статус ответа)
    if (Config::get('LOG_REQUESTS', true)) {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $status = http_response_code();
        $logEntry = date('Y-m-d H:i:s') . " | IP: $ip | Method: $method | Status: $status | Duration: {$duration}ms" . PHP_EOL;
        file_put_contents(__DIR__ . '/../storage/logs/api.log', $logEntry, FILE_APPEND);
    }
}