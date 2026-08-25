<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Models\User;
use RuntimeException;

/**
 * Базовый контроллер с общими вспомогательными методами.
 */
abstract class BaseController
{
    /**
     * Возвращает текущего пользователя на основе JWT из заголовка.
     * Предполагает, что токен уже проверен в middleware.
     *
     * @param string $token
     * @return User|null
     * @throws RuntimeException
     */
    protected function getCurrentUser(string $token): ?array
    {
        $data = Auth::getUserFromToken($token, true);
        return $data['user'] ?? null;
    }

    /**
     * Проверяет, что текущий пользователь имеет заданную роль.
     * Если нет – выбрасывает исключение.
     *
     * @param string $token
     * @param string|array $allowedRoles
     * @return void
     * @throws RuntimeException
     */
    protected function requireRole(string $token, $allowedRoles): void
    {
        $data = Auth::getUserFromToken($token, false);
        $role = $data['role'] ?? '';

        if (is_array($allowedRoles) && !in_array($role, $allowedRoles)) {
            throw new RuntimeException('Access denied: insufficient role.', 403);
        }
        if (is_string($allowedRoles) && $role !== $allowedRoles) {
            throw new RuntimeException('Access denied: insufficient role.', 403);
        }
    }

    /**
     * Проверяет наличие токена в заголовке Authorization.
     *
     * @return string|null
     */
    protected function extractTokenFromHeader(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        return null;
    }
}