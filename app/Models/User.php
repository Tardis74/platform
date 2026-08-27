<?php

namespace App\Models;

use App\Core\DB;
use PDO;

/**
 * Модель пользователя.
 */
class User
{
    /**
     * Находит пользователя по ID.
     *
     * @param int $id
     * @return array|null Ассоциативный массив с данными пользователя или null
     */
    public static function find(int $id): ?array
    {
        $db = DB::getInstance();
        return $db->fetch("SELECT * FROM users WHERE id = :id", ['id' => $id]);
    }

    /**
     * Находит пользователя по email.
     *
     * @param string $email
     * @return array|null
     */
    public static function getByEmail(string $email): ?array
    {
        $db = DB::getInstance();
        return $db->fetch("SELECT * FROM users WHERE email = :email", ['email' => $email]);
    }

    /**
     * Создаёт нового пользователя.
     * Пароль хэшируется с помощью password_hash (BCRYPT).
     *
     * @param array $data Должен содержать email, password, role (и другие поля)
     * @return int ID созданного пользователя
     * @throws \RuntimeException если email уже существует
     */
    public static function create(array $data): int
    {
        $db = DB::getInstance();

        // Проверяем уникальность email
        $existing = self::getByEmail($data['email']);
        if ($existing) {
            throw new \RuntimeException('User with this email already exists.');
        }

        // Хэшируем пароль
        $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        unset($data['password']);

        return $db->insert('users', $data);
    }

    /**
     * Проверяет пароль.
     *
     * @param string $plain
     * @param string $hash
     * @return bool
     */
    public static function checkPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function updatePassword(int $userId, string $newPassword): bool
    {
        $db = DB::getInstance();
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE users SET password_hash = :hash WHERE id = :id";
        $stmt = $db->query($sql, ['hash' => $hash, 'id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function updateFirstLogin(int $userId, bool $value): bool
    {
        $db = DB::getInstance();
        $sql = "UPDATE users SET first_login = :first_login WHERE id = :id";
        $stmt = $db->query($sql, ['first_login' => (int)$value, 'id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function getPermissions(int $userId): array
    {
        return \App\Models\UserPermission::getPermissions($userId);
    }
}