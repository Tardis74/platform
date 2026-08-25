<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\ApiResponse;
use App\Models\LeaveRequest;
use App\Models\KppLog;
use App\Models\Student;
use App\Helpers\QRHelper;
use RuntimeException;

class KppController extends BaseController
{
    /**
     * Список подтверждённых заявлений на сегодня
     */
    public function getTodayRequests(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->requireRole($token, ['admin', 'moderator', 'teacher', 'kpp']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $requests = LeaveRequest::getTodayApproved();

        // Добавляем фото ученика (если есть)
        foreach ($requests as &$req) {
            $student = Student::find($req['student_id']);
            $req['photo_url'] = $student && !empty($student['photo_path']) 
                ? '/storage/photos/' . $student['photo_path'] 
                : null;
        }

        return ApiResponse::success($requests);
    }

    /**
     * Сканирование QR-кода – возвращает данные ученика
     */
    public function scan(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->requireRole($token, ['admin', 'moderator', 'teacher', 'kpp']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        if (empty($payload['qr_data'])) {
            return ApiResponse::error('qr_data is required.', 400);
        }

        $requestId = QRHelper::verify($payload['qr_data']);
        if (!$requestId) {
            return ApiResponse::error('Invalid QR code signature.', 401);
        }

        $request = LeaveRequest::findWithStudent($requestId);
        if (!$request) {
            return ApiResponse::error('Заявление не найдено.', 404);
        }

        // Проверка статуса
        if (!in_array($request['status'], ['approved', 'exited'])) {
            return ApiResponse::error('Заявление неактивно.', 400);
        }

        // Проверка временного интервала
        $now = date('Y-m-d H:i:s');
        if ($request['start_time'] > $now || $request['end_time'] < $now) {
            return ApiResponse::error('Выход не разрешён в данный момент (вне временного интервала).', 400);
        }

        $student = Student::find($request['student_id']);
        return ApiResponse::success([
            'request_id' => $requestId,
            'student' => [
                'id'        => $student['id'],
                'full_name' => $request['student_name'],
                'photo_url' => $student && !empty($student['photo_path']) ? '/storage/photos/' . $student['photo_path'] : null,
            ],
            'start_time' => $request['start_time'],
            'end_time'   => $request['end_time'],
            'current_status' => $request['status'],
        ]);
    }

    /**
     * Фиксация выхода
     */
    public function exit(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->requireRole($token, ['admin', 'moderator', 'teacher', 'kpp']);
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
        if ($request['status'] !== 'approved') {
            return ApiResponse::error('Заявление не в статусе "approved".', 409);
        }

        $now = date('Y-m-d H:i:s');
        LeaveRequest::setExitTime($requestId, $now);
        LeaveRequest::updateStatus($requestId, 'exited');

        // Логируем в kpp_logs
        KppLog::log($requestId, 'exit', $user['id']);

        $log = date('Y-m-d H:i:s') . " KPP user {$user['id']} marked exit for request $requestId\n";
        file_put_contents(__DIR__ . '/../../storage/logs/kpp.log', $log, FILE_APPEND);

        return ApiResponse::success(['message' => 'Выход зафиксирован']);
    }

    /**
     * Фиксация возврата
     */
    public function entry(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->requireRole($token, ['admin', 'moderator', 'teacher', 'kpp']);
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
        // Можно разрешить возврат из статусов exited или approved (если не выходили через КПП)
        if (!in_array($request['status'], ['exited', 'approved'])) {
            return ApiResponse::error('Заявление не в статусе "exited" или "approved".', 409);
        }

        $now = date('Y-m-d H:i:s');
        LeaveRequest::setEntryTime($requestId, $now);
        LeaveRequest::updateStatus($requestId, 'returned');

        // Логируем в kpp_logs
        KppLog::log($requestId, 'entry', $user['id']);

        $log = date('Y-m-d H:i:s') . " KPP user {$user['id']} marked entry for request $requestId\n";
        file_put_contents(__DIR__ . '/../../storage/logs/kpp.log', $log, FILE_APPEND);

        return ApiResponse::success(['message' => 'Возврат зафиксирован']);
    }

    /**
     * Поиск ученика по фамилии (для ручного ввода)
     */
    public function search(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->requireRole($token, ['admin', 'moderator', 'teacher', 'kpp']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        if (empty($payload['query'])) {
            return ApiResponse::error('query is required.', 400);
        }

        $results = LeaveRequest::searchByStudentName($payload['query']);

        // Добавляем фото
        foreach ($results as &$item) {
            $student = Student::find($item['student_id']);
            $item['photo_url'] = $student && !empty($student['photo_path']) 
                ? '/storage/photos/' . $student['photo_path'] 
                : null;
        }

        return ApiResponse::success($results);
    }
}