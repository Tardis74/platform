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

    /**
     * Регистрация нового родителя.
     *
     * @param DB $db
     * @param array $payload
     * @return ApiResponse
     */
    public function register(DB $db, array $payload): ApiResponse
    {
        $required = ['full_name', 'email', 'password', 'consent'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                return ApiResponse::error("$field is required.", 400);
            }
        }

        if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            return ApiResponse::error('Invalid email format.', 400);
        }
        if (strlen($payload['password']) < 6) {
            return ApiResponse::error('Password must be at least 6 characters.', 400);
        }
        if ($payload['consent'] !== true) {
            return ApiResponse::error('Consent to personal data processing is required.', 422);
        }

        if (User::getByEmail($payload['email'])) {
            return ApiResponse::error('Email already taken.', 409);
        }

        try {
            $userId = User::create([
                'email'     => $payload['email'],
                'password'  => $payload['password'],
                'role'      => 'parent',
                'full_name' => $payload['full_name'],
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Registration failed: ' . $e->getMessage(), 500);
        }

        $db->insert('parents', ['user_id' => $userId]);

        $logMessage = date('Y-m-d H:i:s') . " Parent registered: {$payload['email']}\n";
        file_put_contents(__DIR__ . '/../../storage/logs/auth.log', $logMessage, FILE_APPEND);

        $token = Auth::generateToken($userId, 'parent');
        $user = User::find($userId);

        return ApiResponse::success([
            'token' => $token,
            'user'  => [
                'id'        => $user['id'],
                'email'     => $user['email'],
                'full_name' => $user['full_name'],
                'role'      => $user['role'],
            ]
        ]);
    }
}