<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\ApiResponse;
use App\Models\Achievement;
use App\Models\Student;
use RuntimeException;

class ModeratorController extends BaseController
{
    /**
     * Список достижений на проверке.
     */
    public function getPendingAchievements(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, ['moderator', 'admin']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $studentId = isset($payload['student_id']) ? (int)$payload['student_id'] : null;
        $pending = Achievement::getPending($studentId);

        // Маскируем СНИЛС (уже есть snils_masked)
        // Дополнительно можно добавить маскировку, если нужно
        return ApiResponse::success($pending);
    }

    /**
     * Подтверждение достижения.
     */
    public function confirmAchievement(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, ['moderator', 'admin']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $achievementId = (int)($payload['achievement_id'] ?? 0);
        if ($achievementId <= 0) {
            return ApiResponse::error('achievement_id is required.', 400);
        }

        $achievement = Achievement::find($achievementId);
        if (!$achievement) {
            return ApiResponse::error('Achievement not found.', 404);
        }
        if ($achievement['status'] !== 'pending') {
            return ApiResponse::error('Achievement is not pending.', 409);
        }

        // Получаем вес категории
        $weight = Achievement::getCategoryWeight($achievementId);
        if ($weight === null) {
            return ApiResponse::error('Category weight not found.', 500);
        }

        // Обновляем статус
        $comment = $payload['comment'] ?? null;
        if (!Achievement::updateStatus($achievementId, 'approved', $comment)) {
            return ApiResponse::error('Failed to update achievement status.', 500);
        }

        // Увеличиваем баллы ученика
        $studentId = (int)$achievement['student_id'];
        Student::addPoints($studentId, $weight);

        // Логирование
        $log = date('Y-m-d H:i:s') . " Moderator confirmed achievement $achievementId, added $weight points to student $studentId\n";
        file_put_contents(__DIR__ . '/../../storage/logs/portfolio.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'achievement_id' => $achievementId,
            'status'         => 'approved',
            'points_added'   => $weight,
        ]);
    }

    /**
     * Отклонение достижения.
     */
    public function rejectAchievement(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, ['moderator', 'admin']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $achievementId = (int)($payload['achievement_id'] ?? 0);
        if ($achievementId <= 0) {
            return ApiResponse::error('achievement_id is required.', 400);
        }

        if (empty($payload['comment'])) {
            return ApiResponse::error('Comment is required for rejection.', 400);
        }

        $achievement = Achievement::find($achievementId);
        if (!$achievement) {
            return ApiResponse::error('Achievement not found.', 404);
        }
        if ($achievement['status'] !== 'pending') {
            return ApiResponse::error('Achievement is not pending.', 409);
        }

        $comment = $payload['comment'];
        if (!Achievement::updateStatus($achievementId, 'rejected', $comment)) {
            return ApiResponse::error('Failed to update achievement status.', 500);
        }

        // Логирование
        $log = date('Y-m-d H:i:s') . " Moderator rejected achievement $achievementId, reason: $comment\n";
        file_put_contents(__DIR__ . '/../../storage/logs/portfolio.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'achievement_id' => $achievementId,
            'status'         => 'rejected',
        ]);
    }
}