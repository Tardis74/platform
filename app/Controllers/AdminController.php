<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\ApiResponse;
use App\Models\Student;
use App\Models\LinkRequest;
use App\Models\ParentStudent;
use RuntimeException;

class AdminController extends BaseController
{
    public function getAllPendingStudents(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, ['admin', 'moderator']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $students = Student::findAllPending();
        // snils_masked уже содержит маску
        return ApiResponse::success($students);
    }

    public function confirmStudentByAdmin(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, ['admin', 'moderator']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
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

        try {
            Student::confirm($studentId);
            LinkRequest::approveByStudent($studentId);
            ParentStudent::activateByStudent($studentId);
        } catch (\Exception $e) {
            return ApiResponse::error('Ошибка подтверждения: ' . $e->getMessage(), 500);
        }

        file_put_contents(
            __DIR__ . '/../../storage/logs/confirmations.log',
            date('Y-m-d H:i:s') . " Admin/Moderator (user_id: {$user['id']}) confirmed student ID $studentId\n",
            FILE_APPEND
        );

        return ApiResponse::success([
            'student_id' => $studentId,
            'status'     => 'active',
            'message'    => 'Ученик подтверждён администратором'
        ]);
    }

    public function rejectStudentByAdmin(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, ['admin', 'moderator']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
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

        $reason = $payload['reason'] ?? null;
        Student::reject($studentId, $reason);

        file_put_contents(
            __DIR__ . '/../../storage/logs/confirmations.log',
            date('Y-m-d H:i:s') . " Admin/Moderator (user_id: {$user['id']}) rejected student ID $studentId, reason: " . ($reason ?: 'не указана') . "\n",
            FILE_APPEND
        );

        return ApiResponse::success([
            'student_id' => $studentId,
            'status'     => 'rejected',
            'message'    => 'Ученик отклонён администратором'
        ]);
    }
}