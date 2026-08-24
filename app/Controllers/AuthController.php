<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\ApiResponse;
use App\Models\User;
use RuntimeException;

/**
 * Контроллер аутентификации.
 */
class AuthController extends BaseController
{
    /**
     * Авторизация пользователя.
     * Ожидает email и password в payload.
     *
     * @param DB $db
     * @param array $payload
     * @return ApiResponse
     */
    public function login(DB $db, array $payload): ApiResponse
    {
        if (empty($payload['email']) || empty($payload['password'])) {
            return ApiResponse::error('Email and password are required.', 400);
        }

        $user = User::getByEmail($payload['email']);
        if (!$user || !User::checkPassword($payload['password'], $user['password_hash'])) {
            return ApiResponse::error('Invalid credentials.', 401);
        }

        // Генерируем токен (роль возьмём из БД, предположим поле role есть)
        $token = \App\Core\Auth::generateToken($user['id'], $user['role']);

        return ApiResponse::success([
            'token' => $token,
            'user'  => [
                'id'    => $user['id'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ]
        ]);
    }

    /**
     * Выход (клиент удаляет токен).
     *
     * @param DB $db
     * @param array $payload
     * @return ApiResponse
     */
    public function logout(DB $db, array $payload): ApiResponse
    {
        return ApiResponse::success(null, 'Logged out successfully.');
    }

    /**
     * Проверка токена – возвращает данные текущего пользователя.
     * Требует валидный JWT.
     *
     * @param DB $db
     * @param array $payload
     * @return ApiResponse
     */
    public function check(DB $db, array $payload): ApiResponse
    {
        // Токен уже проверен в middleware, получаем пользователя
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }
        return ApiResponse::success([
            'user' => [
                'id'    => $user['id'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ]
        ]);
    }

    /**
     * Обновление токена (опционально).
     *
     * @param DB $db
     * @param array $payload
     * @return ApiResponse
     */
    public function refresh(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        // Проверяем текущий токен
        try {
            $data = \App\Core\Auth::getUserFromToken($token, false);
            $userId = $data['user_id'];
            $role = $data['role'];
        } catch (\RuntimeException $e) {
            return ApiResponse::error('Invalid token.', 401);
        }

        // Генерируем новый
        $newToken = \App\Core\Auth::generateToken($userId, $role);
        return ApiResponse::success(['token' => $newToken]);
    }
}