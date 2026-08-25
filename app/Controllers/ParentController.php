<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\ApiResponse;
use App\Core\Config;
use App\Models\Student;
use App\Models\LinkRequest;
use App\Models\SchoolClass;
use App\Helpers\Validator;
use RuntimeException;

class ParentController extends BaseController
{
    /**
     * Получить список детей текущего родителя (из link_requests).
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

        try {
            $this->requireRole($token, 'parent');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $parent = $db->fetch("SELECT id FROM parents WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$parent) {
            return ApiResponse::error('Parent profile not found.', 404);
        }
        $parentId = $parent['id'];

        $children = $db->fetchAll(
            "SELECT s.id, u.full_name, c.name as class_name, lr.status, s.is_dormitory
             FROM link_requests lr
             JOIN students s ON lr.student_id = s.id
             JOIN users u ON s.user_id = u.id
             LEFT JOIN classes c ON s.class_id = c.id
             WHERE lr.parent_id = :parent_id AND lr.status IN ('pending', 'approved')
             ORDER BY u.full_name",
            ['parent_id' => $parentId]
        );

        foreach ($children as &$child) {
            if ($child['status'] === 'approved') {
                $child['status'] = 'active';
            }
        }

        return ApiResponse::success($children);
    }

    /**
     * Добавить ребёнка (создать нового или отправить заявку на привязку).
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

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $parent = $db->fetch("SELECT id FROM parents WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$parent) {
            return ApiResponse::error('Parent profile not found.', 404);
        }
        $parentId = $parent['id'];

        if (empty($payload['snils']) || empty($payload['full_name'])) {
            return ApiResponse::error('snils and full_name are required.', 400);
        }

        try {
            $snilsCleaned = Validator::validateSnils($payload['snils']);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }

        $snilsHash = hash('sha256', $snilsCleaned . Config::get('SALT'));

        $student = Student::findBySnilsHash($snilsHash);

        if ($student) {
            // Ученик уже существует – создаём заявку
            $existing = $db->fetch(
                "SELECT id FROM link_requests 
                 WHERE parent_id = :parent_id AND student_id = :student_id AND status IN ('pending', 'approved')",
                ['parent_id' => $parentId, 'student_id' => $student['id']]
            );
            if ($existing) {
                return ApiResponse::error('This student is already linked or request pending.', 409);
            }

            LinkRequest::create($parentId, $student['id'], 'pending');

            $log = date('Y-m-d H:i:s') . " Link request for student {$student['id']} by parent $parentId\n";
            file_put_contents(__DIR__ . '/../../storage/logs/notifications.log', $log, FILE_APPEND);

            return ApiResponse::success([
                'student_id' => $student['id'],
                'status'     => 'pending',
                'message'    => 'Ученик уже зарегистрирован. Запрос на привязку отправлен.'
            ]);
        }

        // --- Создание нового ученика ---
        $classId = isset($payload['class_id']) ? (int)$payload['class_id'] : null;
        if ($classId !== null) {
            if (!SchoolClass::find($classId)) {
                return ApiResponse::error('Class not found.', 404);
            }
        }

        $birthDate = $payload['birth_date'] ?? null;
        if ($birthDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDate)) {
            return ApiResponse::error('Invalid birth_date format. Use YYYY-MM-DD.', 400);
        }

        $isDormitory = isset($payload['is_dormitory']) ? (bool)$payload['is_dormitory'] : false;

        $tempPassword = bin2hex(random_bytes(4)); // 8 символов (0-9a-f)

        $studentEmail = 'student_' . uniqid() . '@lyceum.local';
        try {
            $studentUserId = \App\Models\User::create([
                'email'     => $studentEmail,
                'password'  => $tempPassword,
                'role'      => 'student',
                'full_name' => $payload['full_name'],
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create student user: ' . $e->getMessage(), 500);
        }

        $studentId = Student::create([
            'user_id'      => $studentUserId,
            'snils_hash'   => $snilsHash,
            'snils_masked' => $snilsMasked,
            'class_id'     => $classId,
            'birth_date'   => $birthDate,
            'is_dormitory' => $isDormitory ? 1 : 0,
            'status'       => 'awaiting_confirmation'
        ]);

        LinkRequest::create($parentId, $studentId, 'pending');

        $log = date('Y-m-d H:i:s') . " Student created: {$payload['full_name']}, temp password: $tempPassword\n";
        file_put_contents(__DIR__ . '/../../storage/logs/notifications.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'student_id'          => $studentId,
            'temporary_password'  => $tempPassword,
            'status'              => 'awaiting_confirmation',
            'message'             => 'Профиль создан. Передайте временный пароль ученику для входа.'
        ]);
    }

    /**
     * Привязка существующего ученика по СНИЛС (отдельный метод).
     *
     * @param DB $db
     * @param array $payload
     * @return ApiResponse
     */
    public function linkChild(DB $db, array $payload): ApiResponse
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

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $parent = $db->fetch("SELECT id FROM parents WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$parent) {
            return ApiResponse::error('Parent profile not found.', 404);
        }
        $parentId = $parent['id'];

        if (empty($payload['snils'])) {
            return ApiResponse::error('snils is required.', 400);
        }

        try {
            $snilsCleaned = Validator::validateSnils($payload['snils']);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }

        $snilsHash = hash('sha256', $snilsCleaned . Config::get('SALT'));
        $student = Student::findBySnilsHash($snilsHash);
        if (!$student) {
            return ApiResponse::error('Student with this SNILS not found.', 404);
        }

        $existing = $db->fetch(
            "SELECT id FROM link_requests 
             WHERE parent_id = :parent_id AND student_id = :student_id AND status IN ('pending', 'approved')",
            ['parent_id' => $parentId, 'student_id' => $student['id']]
        );
        if ($existing) {
            return ApiResponse::error('This student is already linked or request pending.', 409);
        }

        LinkRequest::create($parentId, $student['id'], 'pending');

        $log = date('Y-m-d H:i:s') . " Link request for student {$student['id']} by parent $parentId (via linkChild)\n";
        file_put_contents(__DIR__ . '/../../storage/logs/notifications.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'student_id' => $student['id'],
            'status'     => 'pending',
            'message'    => 'Запрос на привязку отправлен.'
        ]);
    }

    /**
     * Получить мероприятия, на которые записаны дети родителя.
     */
    public function getChildrenEvents(DB $db, array $payload): ApiResponse
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

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $parent = $db->fetch("SELECT id FROM parents WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$parent) {
            return ApiResponse::error('Parent profile not found.', 404);
        }
        $parentId = $parent['id'];

        $childId = isset($payload['child_id']) ? (int)$payload['child_id'] : null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        // Получаем детей
        $childrenQuery = "SELECT student_id FROM parent_student WHERE parent_id = :parent_id";
        $childrenParams = ['parent_id' => $parentId];
        if ($childId) {
            $childrenQuery .= " AND student_id = :child_id";
            $childrenParams['child_id'] = $childId;
        }
        $children = $db->fetchAll($childrenQuery, $childrenParams);
        $studentIds = array_column($children, 'student_id');
        if (empty($studentIds)) {
            return ApiResponse::success([]);
        }

        // Получаем заявки этих студентов
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $sql = "SELECT r.*, e.title, e.start_datetime, e.end_datetime, e.location, 
                    s.id as student_id, u.full_name as student_name
                FROM event_registrations r
                JOIN events e ON r.event_id = e.id
                JOIN students s ON r.student_id = s.id
                JOIN users u ON s.user_id = u.id
                WHERE r.student_id IN ($placeholders)";

        $params = $studentIds;
        if ($startDate) {
            $sql .= " AND e.start_datetime >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= " AND e.start_datetime <= ?";
            $params[] = $endDate;
        }
        $sql .= " ORDER BY e.start_datetime ASC";

        $registrations = $db->fetchAll($sql, $params);
        return ApiResponse::success($registrations);
    }
}