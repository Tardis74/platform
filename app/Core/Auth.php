<?php

namespace App\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;
use RuntimeException;

/**
 * Класс аутентификации на основе JWT.
 */
class Auth
{
    /**
     * Генерирует JWT-токен для пользователя.
     *
     * @param int $userId
     * @param string $role
     * @param int $expiresIn Время жизни в секундах (по умолчанию 3600)
     * @return string
     */
    public static function generateToken(int $userId, string $role, int $expiresIn = 3600): string
    {
        $payload = [
            'user_id' => $userId,
            'role'    => $role,
            'iat'     => time(),
            'exp'     => time() + $expiresIn,
        ];
        return JWT::encode($payload, Config::get('JWT_SECRET'), 'HS256');
    }

    /**
     * Проверяет подпись и срок действия токена.
     *
     * @param string $token
     * @return object Декодированные данные (стандартные поля + пользовательские)
     * @throws RuntimeException если токен невалиден
     */
    public static function verifyToken(string $token): object
    {
        try {
            $decoded = JWT::decode($token, new Key(Config::get('JWT_SECRET'), 'HS256'));
            return $decoded;
        } catch (\Exception $e) {
            throw new RuntimeException('Invalid token: ' . $e->getMessage());
        }
    }

    /**
     * Извлекает user_id из токена и опционально загружает модель пользователя.
     *
     * @param string $token
     * @param bool $loadUser Если true, возвращает массив с данными пользователя
     * @return array ['user_id' => int, 'role' => string, 'user' => User|null]
     * @throws RuntimeException
     */
    public static function getUserFromToken(string $token, bool $loadUser = true): array
    {
        $decoded = self::verifyToken($token);
        $userId = (int)$decoded->user_id;
        $role   = $decoded->role;

        $user = null;
        if ($loadUser) {
            $user = User::find($userId);
            if (!$user) {
                throw new RuntimeException('User not found');
            }
        }
        return [
            'user_id' => $userId,
            'role'    => $role,
            'user'    => $user,
        ];
    }
}