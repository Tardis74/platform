<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\ApiResponse;
use App\Models\Student;
use RuntimeException;

/**
 * Контроллер для родителей.
 */
class ParentController extends BaseController
{
    /**
     * Получить список детей текущего родителя.
     *
     * @param DB $db
     * @param array $payload
     * @return ApiResponse
     */
    public function getChildren(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        // Проверяем роль
        try {
            $this->requireRole($token, 'parent');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        // Получаем текущего пользователя
        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        // Ищем запись родителя по user_id
        $parent = $db->fetch("SELECT id FROM parents WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$parent) {
            return ApiResponse::error('Parent profile not found.', 404);
        }

        $parentId = $parent['id'];

        // Получаем всех учеников, связанных с этим родителем
        $children = $db->fetchAll(
            "SELECT s.*, u.full_name, u.email, c.name as class_name
             FROM parent_student ps
             JOIN students s ON ps.student_id = s.id
             JOIN users u ON s.user_id = u.id
             LEFT JOIN classes c ON s.class_id = c.id
             WHERE ps.parent_id = :parent_id",
            ['parent_id' => $parentId]
        );

        return ApiResponse::success($children);
    }

    /**
     * Добавить ребёнка (привязать ученика к родителю).
     * Ожидает student_id в payload.
     *
     * @param DB $db
     * @param array $payload
     * @return ApiResponse
     */
    public function addChild(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, 'parent');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        if (empty($payload['student_id'])) {
            return ApiResponse::error('student_id is required.', 400);
        }

        $studentId = (int)$payload['student_id'];

        // Проверяем, существует ли ученик
        $student = Student::find($studentId);
        if (!$student) {
            return ApiResponse::error('Student not found.', 404);
        }

        // Получаем текущего родителя
        $user = $this->getCurrentUser($token);
        $parent = $db->fetch("SELECT id FROM parents WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$parent) {
            return ApiResponse::error('Parent profile not found.', 404);
        }

        $parentId = $parent['id'];

        // Проверяем, не связаны ли уже
        $exists = $db->fetch(
            "SELECT 1 FROM parent_student WHERE parent_id = :parent_id AND student_id = :student_id",
            ['parent_id' => $parentId, 'student_id' => $studentId]
        );
        if ($exists) {
            return ApiResponse::error('This student is already linked to you.', 409);
        }

        // Вставляем связь
        $db->insert('parent_student', [
            'parent_id'  => $parentId,
            'student_id' => $studentId
        ]);

        return ApiResponse::success(null, 'Student linked successfully.');
    }
}