#!/usr/bin/env php
<?php

/**
 * Скрипт для быстрого создания тестового пользователя.
 * Использование:
 *   php scripts/create_user.php <email> <password> <role> <full_name>
 *
 * Пример:
 *   php scripts/create_user.php admin@example.com admin123 admin "Administrator"
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use App\Core\Config;
use App\Core\DB;
use App\Models\User;

// Загружаем .env
Config::load(__DIR__ . '/../config/.env');

// Подключаемся к БД (для проверки)
$db = DB::getInstance();

// Проверяем аргументы
if ($argc < 5) {
    echo "Usage: php scripts/create_user.php <email> <password> <role> <full_name>\n";
    echo "Roles: admin, teacher, parent, student\n";
    exit(1);
}

$email = $argv[1];
$password = $argv[2];
$role = $argv[3];
$fullName = $argv[4];

// Проверка допустимой роли
$allowedRoles = ['admin', 'teacher', 'parent', 'student'];
if (!in_array($role, $allowedRoles)) {
    echo "Error: Invalid role. Allowed: " . implode(', ', $allowedRoles) . "\n";
    exit(1);
}

try {
    $userId = User::create([
        'email'     => $email,
        'password'  => $password,
        'role'      => $role,
        'full_name' => $fullName,
    ]);
    echo "✅ User created successfully!\n";
    echo "ID: $userId\n";
    echo "Email: $email\n";
    echo "Role: $role\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}