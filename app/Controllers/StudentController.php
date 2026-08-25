<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\ApiResponse;
use App\Models\Student;
use App\Models\Achievement;
use App\Models\AchievementCategory;
use App\Helpers\FileHelper;
use RuntimeException;

class StudentController extends BaseController
{
    /**
     * Профиль ученика.
     */
    public function profile(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, 'student');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $student = Student::findByUserId($user['id']);
        if (!$student) {
            return ApiResponse::error('Student profile not found.', 404);
        }

        // Получаем полные данные с именем класса
        $fullData = Student::find($student['id']);
        return ApiResponse::success([
            'id'            => $student['id'],
            'full_name'     => $user['full_name'],
            'class_name'    => $fullData['class_name'] ?? null,
            'total_points'  => (int)$student['total_points'],
            'is_dormitory'  => (bool)$student['is_dormitory'],
        ]);
    }

    /**
     * Загрузка нового достижения.
     */
    public function achievementAdd(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, 'student');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        $student = Student::findByUserId($user['id']);
        if (!$student || $student['status'] !== 'active') {
            return ApiResponse::error('Student not found or inactive.', 404);
        }

        // Проверка обязательных полей
        if (empty($payload['category_id']) || empty($payload['title'])) {
            return ApiResponse::error('category_id and title are required.', 400);
        }

        $categoryId = (int)$payload['category_id'];
        $category = AchievementCategory::find($categoryId);
        if (!$category) {
            return ApiResponse::error('Category not found.', 404);
        }

        // Проверка файла
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return ApiResponse::error('File upload failed.', 400);
        }

        // Сохраняем файл
        $filePath = FileHelper::saveUploadedFile($_FILES['file'], $student['id']);
        if (!$filePath) {
            return ApiResponse::error('Invalid file type or size. Allowed: jpg, jpeg, png, gif, pdf, max 10MB.', 400);
        }

        // Создаём запись
        $achievementId = Achievement::create([
            'student_id'   => $student['id'],
            'category_id'  => $categoryId,
            'title'        => $payload['title'],
            'description'  => $payload['description'] ?? null,
            'file_path'    => $filePath,
            'status'       => 'pending',
        ]);

        // Логирование
        $log = date('Y-m-d H:i:s') . " Student {$student['id']} uploaded achievement ID $achievementId\n";
        file_put_contents(__DIR__ . '/../../storage/logs/portfolio.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'achievement_id' => $achievementId,
            'status'         => 'pending',
            'message'        => 'Достижение отправлено на проверку',
        ]);
    }

    /**
     * Список достижений ученика с фильтрацией.
     */
    public function achievementList(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, 'student');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        $student = Student::findByUserId($user['id']);
        if (!$student) {
            return ApiResponse::error('Student not found.', 404);
        }

        $categoryId = isset($payload['category_id']) ? (int)$payload['category_id'] : null;
        $year = isset($payload['year']) ? (int)$payload['year'] : null;

        $achievements = Achievement::getByStudent($student['id'], $categoryId, $year);

        // Формируем URL для скачивания (без раскрытия пути)
        $data = array_map(function ($ach) {
            return [
                'id'            => $ach['id'],
                'title'         => $ach['title'],
                'category_name' => $ach['category_name'],
                'status'        => $ach['status'],
                'created_at'    => $ach['created_at'],
                'file_url'      => '/api.php?method=achievement.download&id=' . $ach['id'],
            ];
        }, $achievements);

        return ApiResponse::success($data);
    }

    /**
     * Детальная информация о достижении.
     */
    public function achievementGet(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, 'student');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        $student = Student::findByUserId($user['id']);
        if (!$student) {
            return ApiResponse::error('Student not found.', 404);
        }

        $achievementId = (int)($payload['id'] ?? 0);
        if ($achievementId <= 0) {
            return ApiResponse::error('achievement_id is required.', 400);
        }

        $achievement = Achievement::find($achievementId);
        if (!$achievement) {
            return ApiResponse::error('Achievement not found.', 404);
        }

        // Проверка принадлежности
        if ((int)$achievement['student_id'] !== (int)$student['id']) {
            return ApiResponse::error('Access denied.', 403);
        }

        return ApiResponse::success([
            'id'                => $achievement['id'],
            'title'             => $achievement['title'],
            'description'       => $achievement['description'],
            'category_name'     => $achievement['category_name'],
            'status'            => $achievement['status'],
            'moderator_comment' => $achievement['moderator_comment'],
            'file_url'          => '/api.php?method=achievement.download&id=' . $achievement['id'],
            'created_at'        => $achievement['created_at'],
        ]);
    }

    /**
     * Скачивание файла достижения (с проверкой прав).
     */
    public function achievementDownload(DB $db, array $payload): void
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            ApiResponse::error('Token required.', 401)->send();
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            ApiResponse::error('User not found.', 404)->send();
        }

        $achievementId = (int)($_GET['id'] ?? 0);
        if ($achievementId <= 0) {
            ApiResponse::error('achievement_id is required.', 400)->send();
        }

        $achievement = Achievement::find($achievementId);
        if (!$achievement) {
            ApiResponse::error('Achievement not found.', 404)->send();
        }

        // Определяем роль и права
        $role = $user['role'];
        $student = Student::findByUserId($user['id']);

        // Если студент – может скачивать только свои
        if ($role === 'student') {
            if (!$student || (int)$student['id'] !== (int)$achievement['student_id']) {
                ApiResponse::error('Access denied.', 403)->send();
            }
        } elseif (!in_array($role, ['moderator', 'admin'])) {
            ApiResponse::error('Access denied.', 403)->send();
        }

        // Проверка существования файла
        $filePath = FileHelper::getFilePath($achievement['file_path']);
        if (!file_exists($filePath)) {
            ApiResponse::error('File not found.', 404)->send();
        }

        // Отдаём файл
        header('Content-Type: ' . mime_content_type($filePath));
        header('Content-Disposition: attachment; filename="' . basename($achievement['file_path']) . '"');
        readfile($filePath);
        exit;
    }

    /**
     * Подать заявку на мероприятие.
     */
    public function eventRegister(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, 'student');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        $student = Student::findByUserId($user['id']);
        if (!$student || $student['status'] !== 'active') {
            return ApiResponse::error('Student not found or inactive.', 404);
        }

        $eventId = (int)($payload['event_id'] ?? 0);
        if ($eventId <= 0) {
            return ApiResponse::error('event_id is required.', 400);
        }

        // Проверяем существование мероприятия и его статус
        $event = Event::find($eventId);
        if (!$event || $event['status'] !== 'active') {
            return ApiResponse::error('Event not available.', 404);
        }

        // Проверяем доступность для ученика
        if (!Event::isAvailableForStudent($eventId, $student['id'])) {
            return ApiResponse::error('This event is not available for you.', 403);
        }

        // Проверяем, не зарегистрирован ли уже
        $existing = \App\Models\EventRegistration::findByEventAndStudent($eventId, $student['id']);
        if ($existing) {
            return ApiResponse::error('You are already registered for this event.', 409);
        }

        // Атомарное увеличение счётчика
        $affected = Event::atomicIncrement($eventId);
        if ($affected === 0) {
            return ApiResponse::error('No available spots left.', 409);
        }

        // Определяем статус заявки
        $status = $event['requires_confirmation'] ? 'pending' : 'approved';

        // Создаём заявку
        try {
            $regId = \App\Models\EventRegistration::create([
                'event_id' => $eventId,
                'student_id' => $student['id'],
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            // Откатываем увеличение, если создание не удалось
            Event::atomicDecrement($eventId);
            return ApiResponse::error('Failed to create registration: ' . $e->getMessage(), 500);
        }

        // Логирование
        $log = date('Y-m-d H:i:s') . " Student {$student['id']} registered for event $eventId, status $status\n";
        file_put_contents(__DIR__ . '/../../storage/logs/events.log', $log, FILE_APPEND);

        $message = $status === 'pending' ? 'Заявка подана, ожидает подтверждения' : 'Вы записаны на мероприятие';
        return ApiResponse::success([
            'registration_id' => $regId,
            'status' => $status,
            'message' => $message,
        ]);
    }

    /**
     * Отмена заявки учеником.
     */
    public function eventUnregister(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, 'student');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        $student = Student::findByUserId($user['id']);
        if (!$student) {
            return ApiResponse::error('Student not found.', 404);
        }

        $eventId = (int)($payload['event_id'] ?? 0);
        if ($eventId <= 0) {
            return ApiResponse::error('event_id is required.', 400);
        }

        $registration = \App\Models\EventRegistration::findByEventAndStudent($eventId, $student['id']);
        if (!$registration) {
            return ApiResponse::error('Registration not found.', 404);
        }

        // Можно отменить только если статус pending или approved
        if (!in_array($registration['status'], ['pending', 'approved'])) {
            return ApiResponse::error('Cannot cancel registration with status ' . $registration['status'], 400);
        }

        // Обновляем статус на cancelled
        try {
            \App\Models\EventRegistration::updateStatus($registration['id'], 'cancelled');
            // Уменьшаем счётчик
            Event::atomicDecrement($eventId);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to cancel registration: ' . $e->getMessage(), 500);
        }

        $log = date('Y-m-d H:i:s') . " Student {$student['id']} cancelled registration for event $eventId\n";
        file_put_contents(__DIR__ . '/../../storage/logs/events.log', $log, FILE_APPEND);

        return ApiResponse::success(['message' => 'Заявка отменена']);
    }

    /**
     * Список мероприятий, на которые ученик записан.
     */
    public function eventMyRegistrations(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->requireRole($token, 'student');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        $student = Student::findByUserId($user['id']);
        if (!$student) {
            return ApiResponse::error('Student not found.', 404);
        }

        $filters = [
            'status' => $_GET['status'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
        ];

        $registrations = \App\Models\EventRegistration::getByStudent($student['id'], $filters);
        return ApiResponse::success($registrations);
    }

    // ========== Документы ученика ==========

    public function uploadDocument(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->requireRole($token, 'student'); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $student = \App\Models\Student::findByUserId($user['id']);
        if (!$student || $student['status'] !== 'active') {
            return ApiResponse::error('Student not found or inactive.', 404);
        }

        $templateId = isset($payload['template_id']) ? (int)$payload['template_id'] : null;
        $template = null;
        if ($templateId) {
            $template = \App\Models\DocumentTemplate::find($templateId);
            if (!$template) return ApiResponse::error('Template not found.', 404);
        }

        $filePath = null;
        $signatureData = null;
        $requiresFile = $template ? (bool)$template['requires_file'] : true;

        if ($requiresFile) {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                return ApiResponse::error('File upload required.', 400);
            }
            $filePath = \App\Helpers\FileHelper::saveDocument($_FILES['file'], $student['id']);
            if (!$filePath) return ApiResponse::error('Invalid file or size.', 400);
        } else {
            if (isset($payload['signature']) && $payload['signature'] === true) {
                $signatureData = json_encode(['confirmed' => true, 'date' => date('Y-m-d H:i:s')]);
            } else {
                return ApiResponse::error('Signature required.', 400);
            }
        }

        $eventId = isset($payload['event_id']) ? (int)$payload['event_id'] : null;
        $expiryDate = $payload['expiry_date'] ?? null;
        if ($expiryDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)) {
            return ApiResponse::error('Invalid expiry_date format.', 400);
        }

        $docData = [
            'student_id' => $student['id'],
            'template_id' => $templateId,
            'event_id' => $eventId,
            'uploaded_by' => $user['id'],
            'file_path' => $filePath,
            'signature_data' => $signatureData,
            'status' => 'pending',
            'expiry_date' => $expiryDate,
            'version' => 1,
        ];

        try {
            $docId = \App\Models\Document::create($docData);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to upload document: ' . $e->getMessage(), 500);
        }

        $log = date('Y-m-d H:i:s') . " [user_id: {$user['id']}] Ученик загрузил документ ID $docId\n";
        file_put_contents(__DIR__ . '/../../storage/logs/documents.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'document_id' => $docId,
            'status' => 'pending',
            'message' => 'Документ отправлен на проверку'
        ]);
    }

    public function getDocuments(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->requireRole($token, 'student'); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $student = \App\Models\Student::findByUserId($user['id']);
        if (!$student) return ApiResponse::error('Student not found.', 404);

        $status = $payload['status'] ?? null;
        $docs = \App\Models\Document::getByStudent($student['id'], $status);
        foreach ($docs as &$d) {
            $d['file_url'] = $d['file_path'] ? '/api.php?method=document.download&id=' . $d['id'] : null;
        }

        return ApiResponse::success($docs);
    }

    /**
     * Подача заявления на выход учеником
     */
    public function leaveRequestCreate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->requireRole($token, 'student');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $student = Student::findByUserId($user['id']);
        if (!$student || $student['status'] !== 'active') {
            return ApiResponse::error('Student not found or inactive.', 404);
        }
        if (!$student['is_dormitory']) {
            return ApiResponse::error('Ученик не проживает в общежитии.', 400);
        }

        if (empty($payload['start_time']) || empty($payload['end_time'])) {
            return ApiResponse::error('start_time and end_time are required.', 400);
        }
        if (!strtotime($payload['start_time']) || !strtotime($payload['end_time'])) {
            return ApiResponse::error('Invalid date format. Use YYYY-MM-DD HH:MM:SS.', 400);
        }

        $data = [
            'student_id' => $student['id'],
            'parent_id'  => null,
            'start_time' => $payload['start_time'],
            'end_time'   => $payload['end_time'],
            'status'     => 'pending',
            'created_by' => $user['id'],
        ];

        $requestId = LeaveRequest::create($data);

        $log = date('Y-m-d H:i:s') . " Student {$user['id']} created leave request $requestId\n";
        file_put_contents(__DIR__ . '/../../storage/logs/kpp.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'request_id' => $requestId,
            'status' => 'pending',
            'message' => 'Заявление отправлено воспитателю',
        ]);
    }

    /**
     * Список своих заявлений
     */
    public function leaveRequestList(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->requireRole($token, 'student');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $student = Student::findByUserId($user['id']);
        if (!$student) {
            return ApiResponse::error('Student not found.', 404);
        }

        $status = $payload['status'] ?? null;
        $requests = LeaveRequest::getByStudent($student['id'], $status);
        return ApiResponse::success($requests);
    }
}