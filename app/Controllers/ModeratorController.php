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

    /**
     * Список заявок на подтверждение.
     */
    public function getPendingRegistrations(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, ['admin', 'moderator', 'teacher']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $classId = null;
        if ($user['role'] === 'teacher') {
            // Находим класс учителя
            $teacher = $db->fetch("SELECT class_id FROM teachers WHERE user_id = :user_id", ['user_id' => $user['id']]);
            if (!$teacher || !$teacher['class_id']) {
                return ApiResponse::error('У вас нет привязанного класса.', 404);
            }
            $classId = (int)$teacher['class_id'];
        }

        $filters = [
            'event_id' => isset($payload['event_id']) ? (int)$payload['event_id'] : null,
            'student_id' => isset($payload['student_id']) ? (int)$payload['student_id'] : null,
            'status' => $payload['status'] ?? 'pending',
        ];

        $registrations = \App\Models\EventRegistration::getPending($filters, $classId);
        return ApiResponse::success($registrations);
    }

    /**
     * Подтверждение заявки.
     */
    public function confirmRegistration(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, ['admin', 'moderator', 'teacher']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $registrationId = (int)($payload['registration_id'] ?? 0);
        if ($registrationId <= 0) {
            return ApiResponse::error('registration_id is required.', 400);
        }

        $registration = \App\Models\EventRegistration::find($registrationId);
        if (!$registration) {
            return ApiResponse::error('Registration not found.', 404);
        }

        if ($registration['status'] !== 'pending') {
            return ApiResponse::error('Registration is not pending.', 409);
        }

        // Для teacher проверяем, что ученик из его класса
        if ($user['role'] === 'teacher') {
            $teacher = $db->fetch("SELECT class_id FROM teachers WHERE user_id = :user_id", ['user_id' => $user['id']]);
            if (!$teacher || !$teacher['class_id']) {
                return ApiResponse::error('У вас нет привязанного класса.', 404);
            }
            $student = Student::find($registration['student_id']);
            if (!$student || (int)$student['class_id'] !== (int)$teacher['class_id']) {
                return ApiResponse::error('Этот ученик не из вашего класса.', 403);
            }
        }

        try {
            \App\Models\EventRegistration::updateStatus($registrationId, 'approved');
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to confirm registration: ' . $e->getMessage(), 500);
        }

        $log = date('Y-m-d H:i:s') . " Registration $registrationId confirmed by user {$user['id']}\n";
        file_put_contents(__DIR__ . '/../../storage/logs/events.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'registration_id' => $registrationId,
            'status' => 'approved',
        ]);
    }

    /**
     * Отклонение заявки.
     */
    public function rejectRegistration(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, ['admin', 'moderator', 'teacher']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $registrationId = (int)($payload['registration_id'] ?? 0);
        if ($registrationId <= 0) {
            return ApiResponse::error('registration_id is required.', 400);
        }

        $registration = \App\Models\EventRegistration::find($registrationId);
        if (!$registration) {
            return ApiResponse::error('Registration not found.', 404);
        }

        if ($registration['status'] !== 'pending') {
            return ApiResponse::error('Registration is not pending.', 409);
        }

        // Права для teacher
        if ($user['role'] === 'teacher') {
            $teacher = $db->fetch("SELECT class_id FROM teachers WHERE user_id = :user_id", ['user_id' => $user['id']]);
            if (!$teacher || !$teacher['class_id']) {
                return ApiResponse::error('У вас нет привязанного класса.', 404);
            }
            $student = Student::find($registration['student_id']);
            if (!$student || (int)$student['class_id'] !== (int)$teacher['class_id']) {
                return ApiResponse::error('Этот ученик не из вашего класса.', 403);
            }
        }

        $reason = $payload['reason'] ?? null;

        try {
            \App\Models\EventRegistration::updateStatus($registrationId, 'rejected', $reason);
            // Возвращаем место
            Event::atomicDecrement($registration['event_id']);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to reject registration: ' . $e->getMessage(), 500);
        }

        $log = date('Y-m-d H:i:s') . " Registration $registrationId rejected by user {$user['id']}, reason: " . ($reason ?: 'не указана') . "\n";
        file_put_contents(__DIR__ . '/../../storage/logs/events.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'registration_id' => $registrationId,
            'status' => 'rejected',
        ]);
    }
}