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
    * Дополнительно поддерживается фильтрация по event_id, child_id, датам.
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

        // ---- ИЗМЕНЕНИЕ: получаем параметры из payload (и из GET для обратной совместимости) ----
        $childId   = isset($payload['child_id']) ? (int)$payload['child_id'] : null;
        $startDate = $payload['start_date'] ?? $_GET['start_date'] ?? null;
        $endDate   = $payload['end_date'] ?? $_GET['end_date'] ?? null;
        $eventId   = isset($payload['event_id']) ? (int)$payload['event_id'] : null;   // <-- НОВЫЙ ПАРАМЕТР

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

        // ---- ИЗМЕНЕНИЕ: добавляем фильтр по event_id, если передан ----
        if ($eventId) {
            $sql .= " AND r.event_id = ?";
            $params[] = $eventId;
        }

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

    // ========== Документы ==========

    public function uploadDocument(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->requireRole($token, 'parent'); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $parent = $db->fetch("SELECT id FROM parents WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$parent) return ApiResponse::error('Parent profile not found.', 404);
        $parentId = $parent['id'];

        $studentId = (int)($payload['student_id'] ?? 0);
        if ($studentId <= 0) return ApiResponse::error('student_id is required.', 400);

        // Проверка, что ребёнок принадлежит родителю
        $link = $db->fetch("SELECT 1 FROM parent_student WHERE parent_id = :parent_id AND student_id = :student_id",
            ['parent_id' => $parentId, 'student_id' => $studentId]);
        if (!$link) return ApiResponse::error('This student is not linked to you.', 403);

        // Проверка шаблона, если указан
        $templateId = isset($payload['template_id']) ? (int)$payload['template_id'] : null;
        $template = null;
        if ($templateId) {
            $template = \App\Models\DocumentTemplate::find($templateId);
            if (!$template) return ApiResponse::error('Template not found.', 404);
        }

        // Обработка файла
        $filePath = null;
        $signatureData = null;
        $requiresFile = $template ? (bool)$template['requires_file'] : true; // если нет шаблона – всегда требуется файл

        if ($requiresFile) {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                return ApiResponse::error('File upload required.', 400);
            }
            $filePath = \App\Helpers\FileHelper::saveDocument($_FILES['file'], $studentId);
            if (!$filePath) return ApiResponse::error('Invalid file or size. Allowed: pdf, jpg, png, doc, docx, odt, max 10MB.', 400);
        } else {
            // Простая подпись (галочка)
            if (isset($payload['signature']) && $payload['signature'] === true) {
                $signatureData = json_encode(['confirmed' => true, 'date' => date('Y-m-d H:i:s')]);
            } else {
                return ApiResponse::error('Signature required for this template.', 400);
            }
        }

        $eventId = isset($payload['event_id']) ? (int)$payload['event_id'] : null;
        $expiryDate = $payload['expiry_date'] ?? null;
        if ($expiryDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate)) {
            return ApiResponse::error('Invalid expiry_date format. Use YYYY-MM-DD.', 400);
        }

        // Создание документа
        $docData = [
            'student_id' => $studentId,
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

        $log = date('Y-m-d H:i:s') . " [user_id: {$user['id']}] Загружен документ ID $docId для студента $studentId\n";
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
        try { $this->requireRole($token, 'parent'); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $parent = $db->fetch("SELECT id FROM parents WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$parent) return ApiResponse::error('Parent profile not found.', 404);
        $parentId = $parent['id'];

        $studentId = isset($payload['student_id']) ? (int)$payload['student_id'] : null;
        $status = $payload['status'] ?? null;

        // Получаем детей родителя
        $children = $db->fetchAll("SELECT student_id FROM parent_student WHERE parent_id = :parent_id", ['parent_id' => $parentId]);
        $studentIds = array_column($children, 'student_id');
        if (empty($studentIds)) return ApiResponse::success([]);

        // Если указан конкретный ребёнок, проверяем принадлежность
        if ($studentId && !in_array($studentId, $studentIds)) {
            return ApiResponse::error('Access denied to this student.', 403);
        }
        if ($studentId) $studentIds = [$studentId];

        // Собираем документы для всех детей
        $documents = [];
        foreach ($studentIds as $sid) {
            $docs = \App\Models\Document::getByStudent($sid, $status);
            foreach ($docs as &$d) {
                $d['file_url'] = $d['file_path'] ? '/api.php?method=document.download&id=' . $d['id'] : null;
            }
            $documents = array_merge($documents, $docs);
        }
        usort($documents, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return ApiResponse::success($documents);
    }

    // ========== Согласия ==========

    public function giveConsent(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->requireRole($token, 'parent'); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $parent = $db->fetch("SELECT id FROM parents WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$parent) return ApiResponse::error('Parent profile not found.', 404);
        $parentId = $parent['id'];

        $studentId = (int)($payload['student_id'] ?? 0);
        if ($studentId <= 0) return ApiResponse::error('student_id is required.', 400);

        $link = $db->fetch("SELECT 1 FROM parent_student WHERE parent_id = :parent_id AND student_id = :student_id",
            ['parent_id' => $parentId, 'student_id' => $studentId]);
        if (!$link) return ApiResponse::error('This student is not linked to you.', 403);

        $type = $payload['type'] ?? null;
        if (!in_array($type, ['general', 'event', 'data_processing'])) {
            return ApiResponse::error('Invalid consent type. Allowed: general, event, data_processing.', 400);
        }
        if (empty($payload['version'])) return ApiResponse::error('version is required.', 400);

        // Деактивируем предыдущее согласие того же типа
        \App\Models\Consent::deactivatePrevious($studentId, $type);

        $data = [
            'user_id' => $user['id'],
            'student_id' => $studentId,
            'type' => $type,
            'version' => $payload['version'],
            'status' => 'active',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ];
        try {
            $consentId = \App\Models\Consent::create($data);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to save consent: ' . $e->getMessage(), 500);
        }

        $log = date('Y-m-d H:i:s') . " [user_id: {$user['id']}] Дано согласие ID $consentId (type $type) для студента $studentId\n";
        file_put_contents(__DIR__ . '/../../storage/logs/documents.log', $log, FILE_APPEND);

        return ApiResponse::success(['consent_id' => $consentId, 'status' => 'active']);
    }

    public function revokeConsent(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->requireRole($token, 'parent'); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $consentId = (int)($payload['consent_id'] ?? 0);
        if ($consentId <= 0) return ApiResponse::error('consent_id is required.', 400);

        $consent = \App\Models\Consent::find($consentId);
        if (!$consent) return ApiResponse::error('Consent not found.', 404);
        if ((int)$consent['user_id'] !== (int)$user['id']) return ApiResponse::error('Access denied.', 403);
        if ($consent['status'] === 'revoked') return ApiResponse::error('Consent already revoked.', 409);

        try {
            \App\Models\Consent::revoke($consentId, $_SERVER['REMOTE_ADDR'] ?? null);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to revoke consent: ' . $e->getMessage(), 500);
        }

        $log = date('Y-m-d H:i:s') . " [user_id: {$user['id']}] Отозвано согласие ID $consentId\n";
        file_put_contents(__DIR__ . '/../../storage/logs/documents.log', $log, FILE_APPEND);

        return ApiResponse::success(['message' => 'Согласие отозвано']);
    }

    public function getConsents(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->requireRole($token, 'parent'); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $parent = $db->fetch("SELECT id FROM parents WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$parent) return ApiResponse::error('Parent profile not found.', 404);
        $parentId = $parent['id'];

        $studentId = isset($payload['student_id']) ? (int)$payload['student_id'] : null;
        $type = $payload['type'] ?? null;

        $children = $db->fetchAll("SELECT student_id FROM parent_student WHERE parent_id = :parent_id", ['parent_id' => $parentId]);
        $studentIds = array_column($children, 'student_id');
        if (empty($studentIds)) return ApiResponse::success([]);

        if ($studentId && !in_array($studentId, $studentIds)) {
            return ApiResponse::error('Access denied to this student.', 403);
        }
        if ($studentId) $studentIds = [$studentId];

        $consents = [];
        foreach ($studentIds as $sid) {
            $consents = array_merge($consents, \App\Models\Consent::getByStudent($sid, $type));
        }
        usort($consents, fn($a, $b) => strtotime($b['given_at']) - strtotime($a['given_at']));

        return ApiResponse::success($consents);
    }

    /**
     * Подача заявления на выход родителем
     */
    public function leaveRequestCreate(DB $db, array $payload): ApiResponse
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

        $studentId = (int)($payload['student_id'] ?? 0);
        if ($studentId <= 0) {
            return ApiResponse::error('student_id is required.', 400);
        }

        // Проверка, что ребёнок привязан
        $link = $db->fetch("SELECT 1 FROM parent_student WHERE parent_id = :parent_id AND student_id = :student_id",
            ['parent_id' => $parentId, 'student_id' => $studentId]);
        if (!$link) {
            return ApiResponse::error('This student is not linked to you.', 403);
        }

        $student = Student::find($studentId);
        if (!$student || !$student['is_dormitory']) {
            return ApiResponse::error('Ученик не проживает в общежитии.', 400);
        }

        if (empty($payload['start_time']) || empty($payload['end_time'])) {
            return ApiResponse::error('start_time and end_time are required.', 400);
        }
        if (!strtotime($payload['start_time']) || !strtotime($payload['end_time'])) {
            return ApiResponse::error('Invalid date format. Use YYYY-MM-DD HH:MM:SS.', 400);
        }

        $data = [
            'student_id' => $studentId,
            'parent_id'  => $parentId,
            'start_time' => $payload['start_time'],
            'end_time'   => $payload['end_time'],
            'status'     => 'pending',
            'created_by' => $user['id'],
        ];

        $requestId = LeaveRequest::create($data);

        $log = date('Y-m-d H:i:s') . " Parent {$user['id']} created leave request $requestId for student $studentId\n";
        file_put_contents(__DIR__ . '/../../storage/logs/kpp.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'request_id' => $requestId,
            'status' => 'pending',
            'message' => 'Заявление отправлено воспитателю',
        ]);
    }

    /**
     * Список заявлений для своих детей
     */
    public function leaveRequestList(DB $db, array $payload): ApiResponse
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

        $status = $payload['status'] ?? null;
        $requests = LeaveRequest::getByParent($parentId, $status);
        return ApiResponse::success($requests);
    }

    // ========== Уведомления ==========
    public function getNotifications(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->requireRole($token, 'parent'); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $parent = $db->fetch("SELECT id FROM parents WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$parent) return ApiResponse::error('Parent profile not found.', 404);
        $parentId = $parent['id'];

        // Генерируем уведомления: статусы детей, последние события
        $children = $this->getChildren($db, $payload)->getData();
        $notifications = [];
        foreach ($children as $child) {
            $notifications[] = [
                'text' => $child['full_name'] . ' — ' . ($child['status'] === 'active' ? 'подтверждён' : 'ожидает подтверждения'),
                'time' => date('Y-m-d H:i:s'),
                'icon' => $child['status'] === 'active' ? '✅' : '⏳'
            ];
        }
        // Можно добавить больше событий (например, из таблицы аудита) – для простоты оставляем так
        return ApiResponse::success($notifications);
    }

    // ========== Запись на мероприятие для ребёнка ==========
    public function registerChildForEvent(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->requireRole($token, 'parent'); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $parent = $db->fetch("SELECT id FROM parents WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$parent) return ApiResponse::error('Parent profile not found.', 404);
        $parentId = $parent['id'];

        $studentId = (int)($payload['student_id'] ?? 0);
        $eventId = (int)($payload['event_id'] ?? 0);
        if ($studentId <= 0 || $eventId <= 0) return ApiResponse::error('student_id and event_id required.', 400);

        // Проверка, что ребёнок привязан
        $link = $db->fetch("SELECT 1 FROM parent_student WHERE parent_id = :parent_id AND student_id = :student_id",
            ['parent_id' => $parentId, 'student_id' => $studentId]);
        if (!$link) return ApiResponse::error('This student is not linked to you.', 403);

        // Проверка существования мероприятия и доступности
        $event = \App\Models\Event::find($eventId);
        if (!$event || $event['status'] !== 'active') return ApiResponse::error('Event not available.', 404);
        if (!\App\Models\Event::isAvailableForStudent($eventId, $studentId)) {
            return ApiResponse::error('Event not available for this student.', 403);
        }

        // Проверка, не зарегистрирован ли уже
        $existing = \App\Models\EventRegistration::findByEventAndStudent($eventId, $studentId);
        if ($existing) return ApiResponse::error('Already registered.', 409);

        // Атомарное увеличение счётчика
        $affected = \App\Models\Event::atomicIncrement($eventId);
        if ($affected === 0) return ApiResponse::error('No available spots left.', 409);

        $status = $event['requires_confirmation'] ? 'pending' : 'approved';
        try {
            $regId = \App\Models\EventRegistration::create([
                'event_id'   => $eventId,
                'student_id' => $studentId,
                'status'     => $status,
            ]);
        } catch (\Exception $e) {
            \App\Models\Event::atomicDecrement($eventId);
            return ApiResponse::error('Failed to create registration: ' . $e->getMessage(), 500);
        }

        return ApiResponse::success(['registration_id' => $regId, 'status' => $status]);
    }

    public function unregisterChildForEvent(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->requireRole($token, 'parent'); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $parent = $db->fetch("SELECT id FROM parents WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$parent) return ApiResponse::error('Parent profile not found.', 404);
        $parentId = $parent['id'];

        $studentId = (int)($payload['student_id'] ?? 0);
        $eventId = (int)($payload['event_id'] ?? 0);
        if ($studentId <= 0 || $eventId <= 0) return ApiResponse::error('student_id and event_id required.', 400);

        $link = $db->fetch("SELECT 1 FROM parent_student WHERE parent_id = :parent_id AND student_id = :student_id",
            ['parent_id' => $parentId, 'student_id' => $studentId]);
        if (!$link) return ApiResponse::error('This student is not linked to you.', 403);

        $registration = \App\Models\EventRegistration::findByEventAndStudent($eventId, $studentId);
        if (!$registration) return ApiResponse::error('Registration not found.', 404);
        if (!in_array($registration['status'], ['pending', 'approved'])) {
            return ApiResponse::error('Cannot cancel registration with status ' . $registration['status'], 400);
        }

        try {
            \App\Models\EventRegistration::updateStatus($registration['id'], 'cancelled');
            \App\Models\Event::atomicDecrement($eventId);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to cancel registration: ' . $e->getMessage(), 500);
        }

        return ApiResponse::success(['message' => 'Registration cancelled']);
    }
}