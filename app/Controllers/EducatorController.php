<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\ApiResponse;
use App\Models\LeaveRequest;
use App\Models\Student;
use App\Helpers\QRHelper;
use RuntimeException;

class EducatorController extends BaseController
{
    /**
     * Получить список заявлений на рассмотрении
     */
    public function getPending(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->checkAccess($token, ['admin', 'moderator', 'teacher', 'leave.view']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $filters = [];
        if (!empty($payload['date_from'])) {
            $filters['date_from'] = $payload['date_from'];
        }
        if (!empty($payload['date_to'])) {
            $filters['date_to'] = $payload['date_to'];
        }

        $pending = LeaveRequest::getPending($filters);
        return ApiResponse::success($pending);
    }

    /**
     * Подтвердить заявление (сгенерировать QR)
     */
    public function approve(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->checkAccess($token, ['admin', 'moderator', 'teacher','leave.approve']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $requestId = (int)($payload['request_id'] ?? 0);
        if ($requestId <= 0) {
            return ApiResponse::error('request_id is required.', 400);
        }

        $request = LeaveRequest::find($requestId);
        if (!$request) {
            return ApiResponse::error('Заявление не найдено.', 404);
        }
        if ($request['status'] !== 'pending') {
            return ApiResponse::error('Заявление не на рассмотрении.', 409);
        }

        // Проверка, что ученик живёт в общежитии
        $student = Student::find($request['student_id']);
        if (!$student || !$student['is_dormitory']) {
            return ApiResponse::error('Ученик не проживает в общежитии.', 400);
        }

        // Если передано новое время окончания – обновляем
        if (!empty($payload['new_end_time'])) {
            if (!strtotime($payload['new_end_time'])) {
                return ApiResponse::error('Invalid new_end_time format.', 400);
            }
            // обновляем end_time в БД
            $db->query("UPDATE leave_requests SET end_time = :end_time WHERE id = :id", [
                'end_time' => $payload['new_end_time'],
                'id' => $requestId
            ]);
        }

        // Генерируем QR
        $qrData = QRHelper::generate($requestId);
        LeaveRequest::setQrCode($requestId, $qrData);
        LeaveRequest::updateStatus($requestId, 'approved');

        // Логирование
        $log = date('Y-m-d H:i:s') . " Educator {$user['id']} approved leave request $requestId\n";
        file_put_contents(__DIR__ . '/../../storage/logs/kpp.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'request_id' => $requestId,
            'status' => 'approved',
            'qr_code' => $qrData,
        ]);
    }

    /**
     * Отклонить заявление
     */
    public function reject(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->checkAccess($token, ['admin', 'moderator', 'teacher', 'leave.approve']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $requestId = (int)($payload['request_id'] ?? 0);
        if ($requestId <= 0) {
            return ApiResponse::error('request_id is required.', 400);
        }

        if (empty($payload['reason'])) {
            return ApiResponse::error('reason is required for rejection.', 400);
        }

        $request = LeaveRequest::find($requestId);
        if (!$request) {
            return ApiResponse::error('Заявление не найдено.', 404);
        }
        if ($request['status'] !== 'pending') {
            return ApiResponse::error('Заявление не на рассмотрении.', 409);
        }

        LeaveRequest::updateStatus($requestId, 'rejected', $payload['reason']);

        $log = date('Y-m-d H:i:s') . " Educator {$user['id']} rejected leave request $requestId, reason: {$payload['reason']}\n";
        file_put_contents(__DIR__ . '/../../storage/logs/kpp.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'request_id' => $requestId,
            'status' => 'rejected',
        ]);
    }

    /**
     * Создать выход без заявления (срочный случай) – сразу approved
     */
    public function create(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->checkAccess($token, ['admin', 'moderator', 'teacher', 'leave.approve']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $studentId = (int)($payload['student_id'] ?? 0);
        if ($studentId <= 0) {
            return ApiResponse::error('student_id is required.', 400);
        }

        $student = Student::find($studentId);
        if (!$student || !$student['is_dormitory']) {
            return ApiResponse::error('Ученик не найден или не проживает в общежитии.', 400);
        }

        $startTime = $payload['start_time'] ?? date('Y-m-d H:i:s');
        if (!strtotime($startTime)) {
            return ApiResponse::error('Invalid start_time format.', 400);
        }
        $endTime = $payload['end_time'] ?? date('Y-m-d H:i:s', strtotime('+2 hours'));
        if (!strtotime($endTime)) {
            return ApiResponse::error('Invalid end_time format.', 400);
        }

        $data = [
            'student_id'  => $studentId,
            'parent_id'   => null,
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'status'      => 'approved',
            'created_by'  => $user['id'],
        ];

        $requestId = LeaveRequest::create($data);

        // Генерируем QR
        $qrData = QRHelper::generate($requestId);
        LeaveRequest::setQrCode($requestId, $qrData);

        $log = date('Y-m-d H:i:s') . " Educator {$user['id']} created emergency leave request $requestId for student $studentId\n";
        file_put_contents(__DIR__ . '/../../storage/logs/kpp.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'request_id' => $requestId,
            'status' => 'approved',
            'qr_code' => $qrData,
        ]);
    }
}