<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\ApiResponse;
use App\Models\Student;
use App\Models\LinkRequest;
use App\Models\ParentStudent;
use RuntimeException;

class TeacherController extends BaseController
{
    public function getPendingStudents(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, 'teacher');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $teacher = $db->fetch(
            "SELECT id, class_id FROM teachers WHERE user_id = :user_id",
            ['user_id' => $user['id']]
        );
        if (!$teacher || empty($teacher['class_id'])) {
            return ApiResponse::error('У вас нет привязанного класса.', 404);
        }

        $classId = (int)$teacher['class_id'];
        $students = Student::findPendingByClass($classId);

        // Поле snils_masked уже содержит маску, дополнительных преобразований не нужно
        return ApiResponse::success($students);
    }

    public function confirmStudent(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, 'teacher');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $teacher = $db->fetch(
            "SELECT id, class_id FROM teachers WHERE user_id = :user_id",
            ['user_id' => $user['id']]
        );
        if (!$teacher || empty($teacher['class_id'])) {
            return ApiResponse::error('У вас нет привязанного класса.', 404);
        }

        $studentId = (int)($payload['student_id'] ?? 0);
        if ($studentId <= 0) {
            return ApiResponse::error('student_id is required and must be positive.', 400);
        }

        $student = Student::find($studentId);
        if (!$student) {
            return ApiResponse::error('Ученик не найден.', 404);
        }

        if ($student['status'] === 'active') {
            return ApiResponse::error('Ученик уже подтверждён.', 409);
        }
        if ($student['status'] === 'rejected') {
            return ApiResponse::error('Ученик был отклонён.', 409);
        }

        if ((int)$student['class_id'] !== (int)$teacher['class_id']) {
            return ApiResponse::error('Ученик не из вашего класса.', 403);
        }

        try {
            Student::confirm($studentId);
            LinkRequest::approveByStudent($studentId);
            ParentStudent::activateByStudent($studentId);
        } catch (\Exception $e) {
            return ApiResponse::error('Ошибка подтверждения: ' . $e->getMessage(), 500);
        }

        file_put_contents(
            __DIR__ . '/../../storage/logs/confirmations.log',
            date('Y-m-d H:i:s') . " Teacher (user_id: {$user['id']}) confirmed student ID $studentId\n",
            FILE_APPEND
        );

        return ApiResponse::success([
            'student_id' => $studentId,
            'status'     => 'active',
            'message'    => 'Ученик подтверждён'
        ]);
    }

    public function rejectStudent(DB $db, array $payload): ApiResponse
    {
        // аналогично, без маскировки
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, 'teacher');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $teacher = $db->fetch(
            "SELECT id, class_id FROM teachers WHERE user_id = :user_id",
            ['user_id' => $user['id']]
        );
        if (!$teacher || empty($teacher['class_id'])) {
            return ApiResponse::error('У вас нет привязанного класса.', 404);
        }

        $studentId = (int)($payload['student_id'] ?? 0);
        if ($studentId <= 0) {
            return ApiResponse::error('student_id is required and must be positive.', 400);
        }

        $student = Student::find($studentId);
        if (!$student) {
            return ApiResponse::error('Ученик не найден.', 404);
        }

        if ($student['status'] === 'active') {
            return ApiResponse::error('Ученик уже подтверждён, отклонение невозможно.', 409);
        }
        if ($student['status'] === 'rejected') {
            return ApiResponse::error('Ученик уже отклонён.', 409);
        }

        if ((int)$student['class_id'] !== (int)$teacher['class_id']) {
            return ApiResponse::error('Ученик не из вашего класса.', 403);
        }

        $reason = $payload['reason'] ?? null;
        Student::reject($studentId, $reason);

        file_put_contents(
            __DIR__ . '/../../storage/logs/confirmations.log',
            date('Y-m-d H:i:s') . " Teacher (user_id: {$user['id']}) rejected student ID $studentId, reason: " . ($reason ?: 'не указана') . "\n",
            FILE_APPEND
        );

        return ApiResponse::success([
            'student_id' => $studentId,
            'status'     => 'rejected',
            'message'    => 'Ученик отклонён'
        ]);
    }
}