<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\ApiResponse;
use App\Models\Event;
use App\Models\Student;
use App\Models\User;
use RuntimeException;

class EventController extends BaseController
{
    /**
     * Создание мероприятия (admin, moderator, teacher).
     */
    public function create(DB $db, array $payload): ApiResponse
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

        // Валидация обязательных полей
        $required = ['title', 'start_datetime'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                return ApiResponse::error("$field is required.", 400);
            }
        }

        // Валидация даты
        if (!strtotime($payload['start_datetime'])) {
            return ApiResponse::error('Invalid start_datetime format. Use YYYY-MM-DD HH:MM:SS.', 400);
        }
        if (!empty($payload['end_datetime']) && !strtotime($payload['end_datetime'])) {
            return ApiResponse::error('Invalid end_datetime format.', 400);
        }

        // Подготовка данных
        $eventData = [
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'start_datetime' => $payload['start_datetime'],
            'end_datetime' => $payload['end_datetime'] ?? null,
            'location' => $payload['location'] ?? null,
            'category_id' => isset($payload['category_id']) ? (int)$payload['category_id'] : null,
            'max_participants' => isset($payload['max_participants']) ? (int)$payload['max_participants'] : null,
            'points' => isset($payload['points']) ? (int)$payload['points'] : 0,
            'requires_confirmation' => isset($payload['requires_confirmation']) ? (bool)$payload['requires_confirmation'] : true,
            'requires_documents' => isset($payload['requires_documents']) ? (bool)$payload['requires_documents'] : false,
            'status' => 'active',
            'created_by' => $user['id'],
        ];

        // Дополнительные массивы
        $classIds = isset($payload['class_ids']) && is_array($payload['class_ids']) ? array_map('intval', $payload['class_ids']) : [];
        $tagIds = isset($payload['tag_ids']) && is_array($payload['tag_ids']) ? array_map('intval', $payload['tag_ids']) : [];
        $dormitoryAccess = isset($payload['dormitory_access']) && is_array($payload['dormitory_access']) ? $payload['dormitory_access'] : [];

        try {
            $eventId = Event::create($eventData, $classIds, $tagIds, $dormitoryAccess);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create event: ' . $e->getMessage(), 500);
        }

        // Логирование
        $log = date('Y-m-d H:i:s') . " Event created ID $eventId by user {$user['id']}\n";
        file_put_contents(__DIR__ . '/../../storage/logs/events.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'event_id' => $eventId,
            'message' => 'Мероприятие создано',
        ]);
    }

    /**
     * Обновление мероприятия (создатель или admin).
     */
    public function update(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        $eventId = (int)($payload['event_id'] ?? 0);
        if ($eventId <= 0) {
            return ApiResponse::error('event_id is required.', 400);
        }

        $event = Event::find($eventId);
        if (!$event) {
            return ApiResponse::error('Event not found.', 404);
        }

        // Проверка прав: создатель или admin
        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        if ($user['role'] !== 'admin' && (int)$event['created_by'] !== (int)$user['id']) {
            return ApiResponse::error('Access denied: you are not the creator and not admin.', 403);
        }

        // Проверка, что мероприятие ещё не началось (можно разрешить изменение только будущих)
        if (strtotime($event['start_datetime']) < time()) {
            return ApiResponse::error('Cannot update an event that has already started.', 400);
        }

        // Подготовка обновляемых полей
        $updatable = ['title', 'description', 'start_datetime', 'end_datetime', 'location', 'category_id',
                      'max_participants', 'points', 'requires_confirmation', 'requires_documents', 'status'];
        $eventData = [];
        foreach ($updatable as $field) {
            if (array_key_exists($field, $payload)) {
                $eventData[$field] = $payload[$field];
            }
        }

        // Валидация дат, если переданы
        if (isset($eventData['start_datetime']) && !strtotime($eventData['start_datetime'])) {
            return ApiResponse::error('Invalid start_datetime format.', 400);
        }
        if (isset($eventData['end_datetime']) && !empty($eventData['end_datetime']) && !strtotime($eventData['end_datetime'])) {
            return ApiResponse::error('Invalid end_datetime format.', 400);
        }

        // Дополнительные связи
        $classIds = isset($payload['class_ids']) ? array_map('intval', $payload['class_ids']) : null;
        $tagIds = isset($payload['tag_ids']) ? array_map('intval', $payload['tag_ids']) : null;
        $dormitoryAccess = isset($payload['dormitory_access']) ? $payload['dormitory_access'] : null;

        try {
            Event::update($eventId, $eventData, $classIds, $tagIds, $dormitoryAccess);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update event: ' . $e->getMessage(), 500);
        }

        // Логирование
        $log = date('Y-m-d H:i:s') . " Event updated ID $eventId by user {$user['id']}\n";
        file_put_contents(__DIR__ . '/../../storage/logs/events.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'event_id' => $eventId,
            'message' => 'Мероприятие обновлено',
        ]);
    }

    /**
     * Отмена мероприятия (создатель или admin).
     */
    public function delete(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        $eventId = (int)($payload['event_id'] ?? 0);
        if ($eventId <= 0) {
            return ApiResponse::error('event_id is required.', 400);
        }

        $event = Event::find($eventId);
        if (!$event) {
            return ApiResponse::error('Event not found.', 404);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        if ($user['role'] !== 'admin' && (int)$event['created_by'] !== (int)$user['id']) {
            return ApiResponse::error('Access denied.', 403);
        }

        // Если есть заявки, можно только отменить, не удалять физически
        try {
            Event::cancel($eventId);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to cancel event: ' . $e->getMessage(), 500);
        }

        $log = date('Y-m-d H:i:s') . " Event cancelled ID $eventId by user {$user['id']}\n";
        file_put_contents(__DIR__ . '/../../storage/logs/events.log', $log, FILE_APPEND);

        return ApiResponse::success(['message' => 'Мероприятие отменено']);
    }

    /**
     * Список мероприятий с фильтрацией (для всех ролей).
     */
    public function list(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $role = $user['role'];
        $studentId = null;
        if ($role === 'student') {
            $student = Student::findByUserId($user['id']);
            if (!$student) {
                return ApiResponse::error('Student profile not found.', 404);
            }
            $studentId = $student['id'];
        }

        // Фильтры из GET
        $filters = [
            'start_date' => $_GET['start_date'] ?? null,
            'end_date'   => $_GET['end_date'] ?? null,
            'category_id' => isset($_GET['category_id']) ? (int)$_GET['category_id'] : null,
            'tag_id'     => isset($_GET['tag_id']) ? (int)$_GET['tag_id'] : null,
            'status'     => $_GET['status'] ?? null,
            'page'       => isset($_GET['page']) ? (int)$_GET['page'] : 1,
            'limit'      => isset($_GET['limit']) ? (int)$_GET['limit'] : 20,
        ];

        // Для родителя – показываем мероприятия его детей (отдельный метод)
        if ($role === 'parent') {
            // Возвращаем пустой список или вызываем другой метод
            return ApiResponse::success([]);
        }

        $events = Event::list($filters, $user['id'], $role, $studentId);

        return ApiResponse::success($events);
    }

    /**
     * Детальная информация о мероприятии.
     */
    public function get(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        $eventId = (int)($payload['event_id'] ?? $_GET['event_id'] ?? 0);
        if ($eventId <= 0) {
            return ApiResponse::error('event_id is required.', 400);
        }

        $event = Event::find($eventId);
        if (!$event) {
            return ApiResponse::error('Event not found.', 404);
        }

        // Проверка доступности для ученика
        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        if ($user['role'] === 'student') {
            $student = Student::findByUserId($user['id']);
            if (!$student) {
                return ApiResponse::error('Student profile not found.', 404);
            }
            if (!Event::isAvailableForStudent($eventId, $student['id'])) {
                return ApiResponse::error('Event is not available for this student.', 403);
            }
            // Добавляем статус заявки
            $reg = \App\Models\EventRegistration::findByEventAndStudent($eventId, $student['id']);
            $event['registration_status'] = $reg ? $reg['status'] : null;
        }

        return ApiResponse::success($event);
    }
}