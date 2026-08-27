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
            $this->checkAccess($token, ['admin', 'moderator', 'students.view']);
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
            $this->checkAccess($token, ['admin', 'moderator', 'students.edit']);
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
            $this->checkAccess($token, ['admin', 'moderator', 'students.edit']);
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

    // Категории
    public function categoryList(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'categories.view']);
        // Изменяем: берём из achievement_categories, а не из event_categories
        return ApiResponse::success($db->fetchAll("SELECT id, name, weight FROM achievement_categories ORDER BY name"));
    }

    /**
     * Создать новую категорию (только admin).
     */
    public function categoryCreate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'categories.create']);

        if (empty($payload['name']) || !isset($payload['weight'])) {
            return ApiResponse::error('name and weight are required.', 400);
        }

        $id = $db->insert('achievement_categories', [
            'name'   => $payload['name'],
            'weight' => (int)$payload['weight']
        ]);
        return ApiResponse::success(['id' => $id, 'message' => 'Category created']);
    }

    /**
     * Обновить категорию (только admin).
     */
    public function categoryUpdate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'categories.edit']);

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id required.', 400);
        if (empty($payload['name']) || !isset($payload['weight'])) {
            return ApiResponse::error('name and weight are required.', 400);
        }

        $updated = $db->update('achievement_categories', [
            'name'   => $payload['name'],
            'weight' => (int)$payload['weight']
        ], 'id = :id', ['id' => $id]);

        if ($updated === 0) {
            return ApiResponse::error('Category not updated (no changes?)', 400);
        }

        return ApiResponse::success(['message' => 'Category updated']);
    }

    /**
     * Удалить категорию (только admin).
     */
    public function categoryDelete(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'categories.delete']);

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id required.', 400);

        $db->query("DELETE FROM achievement_categories WHERE id = ?", [$id]);
        return ApiResponse::success(['message' => 'Category deleted']);
    }

    // ========== Управление тегами ==========

    /**
     * Получить список всех тегов (для всех авторизованных).
     */
    public function tagList(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->getCurrentUser($token);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 401);
        }
        return ApiResponse::success(\App\Models\EventTag::all());
    }

    /**
     * Создать новый тег (только admin).
     */
    public function tagCreate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->checkAccess($token, ['admin', 'tags.create']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (empty($payload['name'])) {
            return ApiResponse::error('name is required.', 400);
        }

        // Проверяем уникальность имени
        if (\App\Models\EventTag::findByName($payload['name'])) {
            return ApiResponse::error('Tag with this name already exists.', 409);
        }

        try {
            $id = \App\Models\EventTag::create($payload['name']);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create tag: ' . $e->getMessage(), 500);
        }

        $log = date('Y-m-d H:i:s') . " Tag created ID $id by user {$user['id']}\n";
        file_put_contents(__DIR__ . '/../../storage/logs/events.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'tag_id'  => $id,
            'message' => 'Тег создан',
        ]);
    }

    /**
     * Обновить тег (только admin).
     */
    public function tagUpdate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->checkAccess($token, ['admin', 'tags.edit']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $tagId = (int)($payload['id'] ?? 0);
        if ($tagId <= 0) {
            return ApiResponse::error('id is required and must be positive.', 400);
        }
        if (empty($payload['name'])) {
            return ApiResponse::error('name is required.', 400);
        }

        $tag = \App\Models\EventTag::find($tagId);
        if (!$tag) {
            return ApiResponse::error('Tag not found.', 404);
        }

        // Если имя меняется, проверяем уникальность
        if ($tag['name'] !== $payload['name'] && \App\Models\EventTag::findByName($payload['name'])) {
            return ApiResponse::error('Tag with this name already exists.', 409);
        }

        try {
            $updated = \App\Models\EventTag::update($tagId, $payload['name']);
            if (!$updated) {
                return ApiResponse::error('Tag not updated (no changes?)', 400);
            }
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update tag: ' . $e->getMessage(), 500);
        }

        $user = $this->getCurrentUser($token);
        $log = date('Y-m-d H:i:s') . " Tag updated ID $tagId by user {$user['id']}\n";
        file_put_contents(__DIR__ . '/../../storage/logs/events.log', $log, FILE_APPEND);

        return ApiResponse::success(['message' => 'Тег обновлён']);
    }

    /**
     * Удалить тег (только admin).
     */
    public function tagDelete(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->checkAccess($token, ['admin', 'tags.delete']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $tagId = (int)($payload['id'] ?? 0);
        if ($tagId <= 0) {
            return ApiResponse::error('id is required and must be positive.', 400);
        }

        $tag = \App\Models\EventTag::find($tagId);
        if (!$tag) {
            return ApiResponse::error('Tag not found.', 404);
        }

        try {
            $deleted = \App\Models\EventTag::delete($tagId);
            if (!$deleted) {
                return ApiResponse::error('Tag not deleted.', 400);
            }
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete tag: ' . $e->getMessage(), 500);
        }

        $user = $this->getCurrentUser($token);
        $log = date('Y-m-d H:i:s') . " Tag deleted ID $tagId by user {$user['id']}\n";
        file_put_contents(__DIR__ . '/../../storage/logs/events.log', $log, FILE_APPEND);

        return ApiResponse::success(['message' => 'Тег удалён']);
    }

    // ========== Управление шаблонами документов ==========

    public function templateList(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['admin', 'templates.view']); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }
        return ApiResponse::success(\App\Models\DocumentTemplate::all());
    }

    public function templateCreate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['admin', 'templates.create']); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        if (empty($payload['name']) || empty($payload['content'])) {
            return ApiResponse::error('name and content are required.', 400);
        }

        $data = [
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'content' => $payload['content'],
            'signature_level' => $payload['signature_level'] ?? 'simple',
            'requires_file' => isset($payload['requires_file']) ? (bool)$payload['requires_file'] : true,
        ];

        try {
            $id = \App\Models\DocumentTemplate::create($data);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create template: ' . $e->getMessage(), 500);
        }

        $user = $this->getCurrentUser($token);
        $log = date('Y-m-d H:i:s') . " [user_id: {$user['id']}] Создан шаблон ID $id\n";
        file_put_contents(__DIR__ . '/../../storage/logs/documents.log', $log, FILE_APPEND);

        return ApiResponse::success(['template_id' => $id, 'message' => 'Шаблон создан']);
    }

    public function templateUpdate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['admin', 'templates.edit']); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id is required.', 400);

        $template = \App\Models\DocumentTemplate::find($id);
        if (!$template) return ApiResponse::error('Template not found.', 404);

        $updatable = ['name', 'description', 'content', 'signature_level', 'requires_file'];
        $data = [];
        foreach ($updatable as $field) {
            if (array_key_exists($field, $payload)) {
                $data[$field] = $payload[$field];
            }
        }
        if (empty($data)) return ApiResponse::error('No fields to update.', 400);

        try {
            \App\Models\DocumentTemplate::update($id, $data);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update template: ' . $e->getMessage(), 500);
        }

        $user = $this->getCurrentUser($token);
        $log = date('Y-m-d H:i:s') . " [user_id: {$user['id']}] Обновлён шаблон ID $id\n";
        file_put_contents(__DIR__ . '/../../storage/logs/documents.log', $log, FILE_APPEND);

        return ApiResponse::success(['message' => 'Шаблон обновлён']);
    }

    public function templateDelete(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['admin', 'templates.delete']); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id is required.', 400);

        $template = \App\Models\DocumentTemplate::find($id);
        if (!$template) return ApiResponse::error('Template not found.', 404);

        try {
            \App\Models\DocumentTemplate::delete($id);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete template: ' . $e->getMessage(), 500);
        }

        $user = $this->getCurrentUser($token);
        $log = date('Y-m-d H:i:s') . " [user_id: {$user['id']}] Удалён шаблон ID $id\n";
        file_put_contents(__DIR__ . '/../../storage/logs/documents.log', $log, FILE_APPEND);

        return ApiResponse::success(['message' => 'Шаблон удалён']);
    }

    // ========== Управление особыми графиками питания ==========

    public function canteenSpecialAdd(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['admin','canteen.edit']); } catch (RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $studentId = (int)($payload['student_id'] ?? 0);
        if ($studentId <= 0) return ApiResponse::error('student_id is required.', 400);
        if (empty($payload['description'])) return ApiResponse::error('description is required.', 400);

        $student = \App\Models\Student::find($studentId);
        if (!$student) return ApiResponse::error('Student not found.', 404);

        try {
            $id = \App\Models\CanteenSpecialMeal::add($studentId, $payload['description'], $user['id']);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to add special meal: ' . $e->getMessage(), 500);
        }

        file_put_contents(
            __DIR__ . '/../../storage/logs/canteen.log',
            date('Y-m-d H:i:s') . " [admin user_id: {$user['id']}] Добавлен особый график ID $id для студента $studentId\n",
            FILE_APPEND
        );

        return ApiResponse::success(['special_id' => $id, 'message' => 'Особый график добавлен']);
    }

    public function canteenSpecialRemove(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['admin', 'canteen.edit']); } catch (RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id is required.', 400);

        $special = \App\Models\CanteenSpecialMeal::find($id);
        if (!$special) return ApiResponse::error('Special meal record not found.', 404);

        try {
            \App\Models\CanteenSpecialMeal::remove($id);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to remove special meal: ' . $e->getMessage(), 500);
        }

        file_put_contents(
            __DIR__ . '/../../storage/logs/canteen.log',
            date('Y-m-d H:i:s') . " [admin user_id: {$user['id']}] Удалён особый график ID $id\n",
            FILE_APPEND
        );

        return ApiResponse::success(['message' => 'Особый график удалён']);
    }

    public function canteenSpecialList(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['admin', 'canteen', 'canteen.view']); } catch (RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $studentId = isset($payload['student_id']) ? (int)$payload['student_id'] : null;
        if ($studentId) {
            $list = \App\Models\CanteenSpecialMeal::getByStudent($studentId);
        } else {
            $list = \App\Models\CanteenSpecialMeal::getAll();
        }
        return ApiResponse::success($list);
    }

    public function classList(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'classes.view']);

        $sql = "SELECT 
                    c.id, c.name, c.academic_year_id, c.is_archived,
                    ay.name as academic_year_name,
                    u.full_name as teacher_name,
                    (SELECT COUNT(*) FROM students WHERE class_id = c.id AND status = 'active') as student_count
                FROM classes c
                LEFT JOIN academic_years ay ON c.academic_year_id = ay.id
                LEFT JOIN teachers t ON c.teacher_id = t.id
                LEFT JOIN users u ON t.user_id = u.id
                WHERE c.is_archived = 0
                ORDER BY c.name";
        $classes = $db->fetchAll($sql);
        return ApiResponse::success($classes);
    }

    public function getDashboardStats(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->checkAccess($token, ['admin', 'dashboard.view']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        // Общее количество пользователей
        $totalUsers = (int)$db->fetch("SELECT COUNT(*) as cnt FROM users WHERE deleted_at IS NULL")['cnt'];
        $admins = (int)$db->fetch("SELECT COUNT(*) as cnt FROM users WHERE role = 'admin' AND deleted_at IS NULL")['cnt'];
        $teachers = (int)$db->fetch("SELECT COUNT(*) as cnt FROM users WHERE role = 'teacher' AND deleted_at IS NULL")['cnt'];
        $parents = (int)$db->fetch("SELECT COUNT(*) as cnt FROM users WHERE role = 'parent' AND deleted_at IS NULL")['cnt'];
        $students = (int)$db->fetch("SELECT COUNT(*) as cnt FROM users WHERE role = 'student' AND deleted_at IS NULL")['cnt'];
        $moderators = (int)$db->fetch("SELECT COUNT(*) as cnt FROM users WHERE role = 'moderator' AND deleted_at IS NULL")['cnt'];

        // Активные ученики (статус active в таблице students)
        $activeStudents = (int)$db->fetch("SELECT COUNT(*) as cnt FROM students WHERE status = 'active'")['cnt'];

        // Классы
        $classes = (int)$db->fetch("SELECT COUNT(*) as cnt FROM classes WHERE is_archived = 0")['cnt'];

        // Мероприятия (активные)
        $events = (int)$db->fetch("SELECT COUNT(*) as cnt FROM events WHERE status = 'active'")['cnt'];

        // Документы (всего)
        $documents = (int)$db->fetch("SELECT COUNT(*) as cnt FROM documents")['cnt'];

        return ApiResponse::success([
            'total_users' => $totalUsers,
            'admins' => $admins,
            'teachers' => $teachers,
            'parents' => $parents,
            'students' => $students,
            'moderators' => $moderators,
            'active_students' => $activeStudents,
            'classes' => $classes,
            'events' => $events,
            'documents' => $documents,
        ]);
    }

    // ============================================================
    // 1. УПРАВЛЕНИЕ ПОЛЬЗОВАТЕЛЯМИ
    // ============================================================

    /**
     * Список пользователей с пагинацией и фильтрацией.
     */
    public function userList(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'users.view']);

        $page = (int)($payload['page'] ?? 1);
        $limit = (int)($payload['limit'] ?? 20);
        $role = $payload['role'] ?? null;
        $status = $payload['status'] ?? null;
        $search = trim($payload['search'] ?? '');

        $offset = ($page - 1) * $limit;

        $sql = "SELECT SQL_CALC_FOUND_ROWS id, full_name, email, role, created_at, 
                    IF(deleted_at IS NULL, 'active', 'blocked') as status
                FROM users WHERE 1=1";
        $params = [];

        if ($role) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        if ($status === 'active') {
            $sql .= " AND deleted_at IS NULL";
        } elseif ($status === 'blocked') {
            $sql .= " AND deleted_at IS NOT NULL";
        }
        if ($search) {
            $sql .= " AND (full_name LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $users = $db->fetchAll($sql, $params);
        $total = (int)$db->fetch("SELECT FOUND_ROWS() as total")['total'];

        return ApiResponse::success([
            'items' => $users,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * Создание нового пользователя.
     */
    public function userCreate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        error_log('=== userCreate called ===');
        error_log('Payload: ' . print_r($payload, true));
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'users.create']);

        if (empty($payload['full_name']) || empty($payload['email']) || empty($payload['password']) || empty($payload['role'])) {
            return ApiResponse::error('full_name, email, password and role are required.', 400);
        }
        if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            return ApiResponse::error('Invalid email format.', 400);
        }
        if (strlen($payload['password']) < 6) {
            return ApiResponse::error('Password must be at least 6 characters.', 400);
        }

        // Проверка на дубликат email
        $exists = $db->fetch("SELECT id FROM users WHERE email = ?", [$payload['email']]);
        if ($exists) {
            return ApiResponse::error('Email already exists.', 409);
        }

        $userData = [
            'full_name'     => $payload['full_name'],
            'email'         => $payload['email'],
            'password_hash' => password_hash($payload['password'], PASSWORD_DEFAULT),
            'role'          => $payload['role'],
            'created_at'    => date('Y-m-d H:i:s'),
            'first_login'   => 1,
        ];
        $userId = $db->insert('users', $userData);

        // Если роль = parent, создаём запись в parents
        if ($payload['role'] === 'parent') {
            $db->insert('parents', ['user_id' => $userId]);
        } elseif ($payload['role'] === 'teacher') {
            $db->insert('teachers', ['user_id' => $userId]);
        } elseif ($payload['role'] === 'student') {
            // Для студента нужен профиль students – здесь можно создать минимальный
            // но обычно студент создаётся через другую логику. Оставляем заглушку.
        }

        return ApiResponse::success(['id' => $userId, 'message' => 'User created']);
    }

    /**
     * Получить данные пользователя по ID.
     */
    public function userGet(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'users.view']);

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id is required.', 400);

        $user = $db->fetch("SELECT id, full_name, email, role, deleted_at FROM users WHERE id = ?", [$id]);
        if (!$user) return ApiResponse::error('User not found.', 404);

        return ApiResponse::success($user);
    }

    /**
     * Обновление пользователя.
     */
    public function userUpdate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'users.edit']);

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id is required.', 400);

        $user = $db->fetch("SELECT id FROM users WHERE id = ?", [$id]);
        if (!$user) return ApiResponse::error('User not found.', 404);

        $updateData = [];
        if (isset($payload['full_name'])) $updateData['full_name'] = $payload['full_name'];
        if (isset($payload['email'])) {
            if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                return ApiResponse::error('Invalid email format.', 400);
            }
            // Проверка уникальности
            $exists = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$payload['email'], $id]);
            if ($exists) return ApiResponse::error('Email already taken.', 409);
            $updateData['email'] = $payload['email'];
        }
        if (isset($payload['role'])) {
            $allowed = ['admin','moderator','teacher','parent','student','canteen','educator','kpp','custom'];
            if (!in_array($payload['role'], $allowed)) return ApiResponse::error('Invalid role.', 400);
            $updateData['role'] = $payload['role'];
        }

        // Сброс пароля
        if (!empty($payload['reset_password']) && $payload['reset_password'] == 1) {
            $newPassword = bin2hex(random_bytes(4)); // 8 символов
            $updateData['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateData['first_login'] = 1;
            // Возвращаем новый пароль в ответе
            $passwordReturn = $newPassword;
        } else {
            $passwordReturn = null;
        }

        if (!empty($updateData)) {
            $db->update('users', $updateData, 'id = :id', ['id' => $id]);
        }

        return ApiResponse::success([
            'message' => 'User updated',
            'new_password' => $passwordReturn,
        ]);
    }

    /**
     * Блокировка/разблокировка пользователя (soft delete).
     */
    public function userToggleStatus(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'users.block']);

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id is required.', 400);
        $action = $payload['action'] ?? '';
        if (!in_array($action, ['block', 'unblock'])) {
            return ApiResponse::error('action must be "block" or "unblock".', 400);
        }

        $user = $db->fetch("SELECT id FROM users WHERE id = ?", [$id]);
        if (!$user) return ApiResponse::error('User not found.', 404);

        if ($action === 'block') {
            $db->query("UPDATE users SET deleted_at = NOW() WHERE id = ?", [$id]);
        } else {
            $db->query("UPDATE users SET deleted_at = NULL WHERE id = ?", [$id]);
        }

        return ApiResponse::success(['message' => 'Status changed']);
    }

    /**
     * Удаление пользователя (полное удаление).
     */
    public function userDelete(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'users.delete']);

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id is required.', 400);

        // Проверяем, не админ ли
        $user = $db->fetch("SELECT role FROM users WHERE id = ?", [$id]);
        if (!$user) return ApiResponse::error('User not found.', 404);
        if ($user['role'] === 'admin') {
            return ApiResponse::error('Cannot delete admin user.', 403);
        }

        // Каскадное удаление: сначала записи в связанных таблицах (или полагаемся на ON DELETE CASCADE)
        $db->query("DELETE FROM users WHERE id = ?", [$id]);

        return ApiResponse::success(['message' => 'User deleted']);
    }

    /**
     * Предпросмотр импорта CSV.
     */
    public function userImportPreview(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'users.create']);

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return ApiResponse::error('File upload failed.', 400);
        }

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') return ApiResponse::error('Only CSV files allowed.', 400);

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) return ApiResponse::error('Failed to read file.', 500);

        $headers = fgetcsv($handle, 0, ';');
        if (!$headers) return ApiResponse::error('Empty file or missing headers.', 400);

        // Ожидаемые заголовки: full_name, email, role (можно добавить password)
        $data = [];
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) < 3) continue;
            $data[] = [
                'full_name' => $row[0],
                'email'     => $row[1],
                'role'      => $row[2],
            ];
        }
        fclose($handle);

        if (empty($data)) return ApiResponse::error('No valid rows found.', 400);

        // Генерируем HTML-таблицу для предпросмотра (упрощённо)
        $html = '<table class="table table-sm table-bordered"><thead><tr><th>ФИО</th><th>Email</th><th>Роль</th></tr></thead><tbody>';
        foreach ($data as $row) {
            $html .= '<tr><td>' . htmlspecialchars($row['full_name']) . '</td><td>' . htmlspecialchars($row['email']) . '</td><td>' . htmlspecialchars($row['role']) . '</td></tr>';
        }
        $html .= '</tbody></table>';

        return ApiResponse::success([
            'data' => $data,
            'html' => $html,
        ]);
    }

    /**
     * Подтверждение импорта пользователей.
     */
    public function userImport(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'users.create']);

        if (empty($payload['data']) || !is_array($payload['data'])) {
            return ApiResponse::error('data array is required.', 400);
        }

        $created = 0;
        foreach ($payload['data'] as $row) {
            $email = trim($row['email'] ?? '');
            $full_name = trim($row['full_name'] ?? '');
            $role = trim($row['role'] ?? 'student');

            if (!$email || !$full_name || !filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
            if (!in_array($role, ['admin','moderator','teacher','parent','student','canteen','educator','kpp'])) continue;

            // Проверка дубликата
            $exists = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
            if ($exists) continue;

            $password = bin2hex(random_bytes(4));
            $db->insert('users', [
                'full_name'     => $full_name,
                'email'         => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role'          => $role,
                'created_at'    => date('Y-m-d H:i:s'),
                'first_login'   => 1,
            ]);
            $created++;
        }

        return ApiResponse::success(['created' => $created, 'message' => "$created users imported"]);
    }


    // ============================================================
    // 2. УЧЕБНЫЕ ГОДЫ
    // ============================================================

    /**
     * Список учебных годов.
     */
    public function academicYearList(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'classes.view']);

        $years = $db->fetchAll("SELECT * FROM academic_years ORDER BY start_date DESC");
        return ApiResponse::success($years);
    }

    /**
     * Создание учебного года.
     */
    public function academicYearCreate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'classes.create']);

        if (empty($payload['name']) || empty($payload['start_date']) || empty($payload['end_date'])) {
            return ApiResponse::error('name, start_date and end_date are required.', 400);
        }
        if (!strtotime($payload['start_date']) || !strtotime($payload['end_date'])) {
            return ApiResponse::error('Invalid date format.', 400);
        }

        $id = $db->insert('academic_years', [
            'name'       => $payload['name'],
            'start_date' => $payload['start_date'],
            'end_date'   => $payload['end_date'],
            'is_current' => 0,
        ]);

        return ApiResponse::success(['id' => $id, 'message' => 'Academic year created']);
    }

    /**
     * Перевод учеников между классами (массовый).
     */
    public function academicYearTransfer(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'classes.edit']);

        $fromClassId = (int)($payload['from_class_id'] ?? 0);
        $toClassId   = (int)($payload['to_class_id'] ?? 0);

        if ($fromClassId <= 0 || $toClassId <= 0) {
            return ApiResponse::error('from_class_id and to_class_id are required.', 400);
        }

        // Перемещаем всех активных учеников из from в to
        $db->query("UPDATE students SET class_id = ? WHERE class_id = ? AND status = 'active'", [$toClassId, $fromClassId]);

        return ApiResponse::success(['message' => 'Students transferred']);
    }


    // ============================================================
    // 3. КЛАССЫ (дополнительные методы)
    // ============================================================

    /**
     * Создание класса.
     */
    public function classCreate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'classes.create']);

        if (empty($payload['name'])) {
            return ApiResponse::error('name is required.', 400);
        }

        $data = [
            'name' => $payload['name'],
            'academic_year_id' => isset($payload['academic_year_id']) ? (int)$payload['academic_year_id'] : null,
            'teacher_id' => isset($payload['teacher_id']) ? (int)$payload['teacher_id'] : null,
            'is_archived' => 0,
        ];

        $id = $db->insert('classes', $data);
        return ApiResponse::success(['id' => $id, 'message' => 'Class created']);
    }

    /**
     * Обновление класса.
     */
    public function classUpdate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'classes.edit']);

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id is required.', 400);

        $updateData = [];
        if (isset($payload['name'])) $updateData['name'] = $payload['name'];
        if (isset($payload['academic_year_id'])) $updateData['academic_year_id'] = (int)$payload['academic_year_id'];
        if (isset($payload['teacher_id'])) $updateData['teacher_id'] = (int)$payload['teacher_id'];

        if (!empty($updateData)) {
            $db->update('classes', $updateData, 'id = :id', ['id' => $id]);
        }

        return ApiResponse::success(['message' => 'Class updated']);
    }

    /**
     * Архивирование класса.
     */
    public function classArchive(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'classes.archive']);

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id is required.', 400);

        $db->query("UPDATE classes SET is_archived = 1 WHERE id = ?", [$id]);
        return ApiResponse::success(['message' => 'Class archived']);
    }

    /**
     * Получить учеников класса с возможностью изменения статуса.
     */
    public function classStudents(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'classes.view']);

        $classId = (int)($payload['class_id'] ?? 0);
        if ($classId <= 0) return ApiResponse::error('class_id is required.', 400);

        $students = $db->fetchAll("
            SELECT s.id, u.full_name, s.status
            FROM students s
            JOIN users u ON s.user_id = u.id
            WHERE s.class_id = ?
        ", [$classId]);

        return ApiResponse::success($students);
    }

    /**
     * Действия над учениками в классе (перевод, оставлен, выбыл, прибыл).
     * Ожидается: student_id и action (transfer, repeat, left, arrived)
     * Для transfer нужен to_class_id.
     */
    public function classStudentAction(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'classes.edit']);

        $studentId = (int)($payload['student_id'] ?? 0);
        $action = $payload['action'] ?? '';
        if ($studentId <= 0 || !in_array($action, ['transfer', 'repeat', 'left', 'arrived'])) {
            return ApiResponse::error('student_id and valid action are required.', 400);
        }

        switch ($action) {
            case 'transfer':
                $toClassId = (int)($payload['to_class_id'] ?? 0);
                if ($toClassId <= 0) return ApiResponse::error('to_class_id required for transfer.', 400);
                $db->query("UPDATE students SET class_id = ? WHERE id = ?", [$toClassId, $studentId]);
                break;
            case 'repeat':
                // Оставляем на второй год – можно обновить статус или класс на следующий год (зависит от логики)
                $db->query("UPDATE students SET status = 'repeated' WHERE id = ?", [$studentId]);
                break;
            case 'left':
                $db->query("UPDATE students SET status = 'left' WHERE id = ?", [$studentId]);
                break;
            case 'arrived':
                $db->query("UPDATE students SET status = 'active' WHERE id = ?", [$studentId]);
                break;
        }

        return ApiResponse::success(['message' => 'Action performed']);
    }


    // ============================================================
    // 4. ОТЧЁТЫ
    // ============================================================

    /**
     * Генерация отчёта (постановка в очередь).
     */
    public function reportGenerate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'reports.generate']);

        $type = $payload['type'] ?? '';
        if (!$type) return ApiResponse::error('type is required.', 400);

        $jobId = $db->insert('report_jobs', [
            'type'       => $type,
            'params'     => json_encode($payload),
            'status'     => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Здесь можно запустить фоновый процесс (например, через exec) или обработать позже.
        // Для демонстрации просто ставим статус 'ready' и генерируем фиктивный файл.
        // В реальности нужно реализовать асинхронную обработку.

        // Имитация генерации
        $filePath = '/storage/reports/report_' . $jobId . '.csv';
        $db->update('report_jobs', ['status' => 'ready', 'file_path' => $filePath, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$jobId]);

        return ApiResponse::success(['job_id' => $jobId]);
    }

    /**
     * Статус отчёта.
     */
    public function reportStatus(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'reports.view']);

        $jobId = (int)($payload['job_id'] ?? 0);
        if ($jobId <= 0) return ApiResponse::error('job_id is required.', 400);

        $job = $db->fetch("SELECT status, file_path FROM report_jobs WHERE id = ?", [$jobId]);
        if (!$job) return ApiResponse::error('Job not found.', 404);

        return ApiResponse::success([
            'status' => $job['status'],
            'download_url' => $job['status'] === 'ready' && $job['file_path'] ? $job['file_path'] : null,
        ]);
    }

    /**
     * История отчётов.
     */
    public function reportHistory(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'reports.view']);

        $history = $db->fetchAll("SELECT id, type, status, file_path, created_at FROM report_jobs ORDER BY created_at DESC LIMIT 50");
        return ApiResponse::success($history);
    }


    // ============================================================
    // 5. РЕЙТИНГ И ДОСТИЖЕНИЯ (админские)
    // ============================================================

    /**
     * Поиск ученика.
     */
    public function studentFind(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'students.view']);

        $query = trim($payload['query'] ?? '');
        if (strlen($query) < 2) return ApiResponse::success([]);

        $students = $db->fetchAll("
            SELECT s.id, u.full_name, c.name as class_name
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE u.full_name LIKE ? OR c.name LIKE ?
            LIMIT 20
        ", ["%$query%", "%$query%"]);

        return ApiResponse::success($students);
    }

    /**
     * Получить достижения ученика (для админа).
     */
    public function studentAchievements(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'achievements.view']);

        $studentId = (int)($payload['student_id'] ?? 0);
        if ($studentId <= 0) return ApiResponse::error('student_id is required.', 400);

        $achievements = $db->fetchAll("
            SELECT a.*, c.name as category_name, c.weight
            FROM achievements a
            JOIN achievement_categories c ON a.category_id = c.id
            WHERE a.student_id = ?
            ORDER BY a.created_at DESC
        ", [$studentId]);

        return ApiResponse::success($achievements);
    }

    /**
     * Подтвердить достижение (админ).
     */
    public function achievementConfirm(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'achievements.moderate']);

        $achievementId = (int)($payload['achievement_id'] ?? 0);
        if ($achievementId <= 0) return ApiResponse::error('achievement_id is required.', 400);

        $ach = $db->fetch("SELECT student_id, category_id, status FROM achievements WHERE id = ?", [$achievementId]);
        if (!$ach) return ApiResponse::error('Achievement not found.', 404);
        if ($ach['status'] !== 'pending') return ApiResponse::error('Achievement is not pending.', 409);

        // Получаем вес категории
        $cat = $db->fetch("SELECT weight FROM achievement_categories WHERE id = ?", [$ach['category_id']]);
        $weight = $cat ? (int)$cat['weight'] : 0;

        // Обновляем статус
        $db->query("UPDATE achievements SET status = 'approved', moderator_comment = NULL, updated_at = NOW() WHERE id = ?", [$achievementId]);

        // Добавляем баллы ученику
        $db->query("UPDATE students SET total_points = total_points + ? WHERE id = ?", [$weight, $ach['student_id']]);

        return ApiResponse::success(['message' => 'Achievement confirmed', 'points_added' => $weight]);
    }

    /**
     * Отклонить достижение (админ).
     */
    public function achievementReject(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'achievements.moderate']);

        $achievementId = (int)($payload['achievement_id'] ?? 0);
        $reason = trim($payload['reason'] ?? '');
        if ($achievementId <= 0 || !$reason) {
            return ApiResponse::error('achievement_id and reason are required.', 400);
        }

        $ach = $db->fetch("SELECT status FROM achievements WHERE id = ?", [$achievementId]);
        if (!$ach) return ApiResponse::error('Achievement not found.', 404);
        if ($ach['status'] !== 'pending') return ApiResponse::error('Achievement is not pending.', 409);

        $db->query("UPDATE achievements SET status = 'rejected', moderator_comment = ?, updated_at = NOW() WHERE id = ?", [$reason, $achievementId]);

        return ApiResponse::success(['message' => 'Achievement rejected']);
    }

    /**
     * Построить рейтинг.
     */
    public function ratingBuild(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'rating.build']);

        $period = $payload['period'] ?? null; // формат YYYY-MM
        $classIds = isset($payload['class_ids']) && is_array($payload['class_ids']) ? array_map('intval', $payload['class_ids']) : [];
        $categoryIds = isset($payload['category_ids']) && is_array($payload['category_ids']) ? array_map('intval', $payload['category_ids']) : [];

        if (!$period) return ApiResponse::error('period is required (YYYY-MM).', 400);

        // Строим запрос: сумма баллов по достижениям за период, сгруппированная по ученикам
        $sql = "
            SELECT s.id as student_id, u.full_name, c.name as class_name,
                COALESCE(SUM(a.weight), 0) as total_points
            FROM students s
            JOIN users u ON s.user_id = u.id
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN achievements ach ON ach.student_id = s.id
            LEFT JOIN achievement_categories a ON ach.category_id = a.id
            WHERE ach.status = 'approved'
            AND DATE_FORMAT(ach.created_at, '%Y-%m') = ?
        ";
        $params = [$period];

        if (!empty($classIds)) {
            $sql .= " AND s.class_id IN (" . implode(',', array_fill(0, count($classIds), '?')) . ")";
            $params = array_merge($params, $classIds);
        }
        if (!empty($categoryIds)) {
            $sql .= " AND ach.category_id IN (" . implode(',', array_fill(0, count($categoryIds), '?')) . ")";
            $params = array_merge($params, $categoryIds);
        }

        $sql .= " GROUP BY s.id ORDER BY total_points DESC";

        $rows = $db->fetchAll($sql, $params);

        // Формируем результат с местом и идентификатором (маскированным)
        $items = [];
        $place = 1;
        foreach ($rows as $row) {
            $items[] = [
                'student_id'   => $row['student_id'],
                'identifier'   => 'Уч. ' . str_pad($row['student_id'], 4, '0', STR_PAD_LEFT), // маскированный ID
                'full_name'    => $row['full_name'],
                'class_name'   => $row['class_name'],
                'total_points' => (int)$row['total_points'],
                'place'        => $place,
                'comment'      => '',
            ];
            $place++;
        }

        // Сохраняем рейтинг в таблицу ratings (создаём новую запись)
        $ratingId = $db->insert('ratings', [
            'period'       => $period,
            'class_ids'    => json_encode($classIds),
            'category_ids' => json_encode($categoryIds),
            'published'    => 0,
            'data'         => json_encode($items),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return ApiResponse::success([
            'id'    => $ratingId,
            'items' => $items,
        ]);
    }

    /**
     * Опубликовать рейтинг.
     */
    public function ratingPublish(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'rating.publish']);

        $ratingId = (int)($payload['rating_id'] ?? 0);
        if ($ratingId <= 0) return ApiResponse::error('rating_id is required.', 400);

        $rating = $db->fetch("SELECT data FROM ratings WHERE id = ?", [$ratingId]);
        if (!$rating) return ApiResponse::error('Rating not found.', 404);

        // Обновляем комментарии, если переданы
        $comments = $payload['comments'] ?? [];
        if (!empty($comments) && is_array($comments)) {
            $data = json_decode($rating['data'], true);
            foreach ($data as &$item) {
                if (isset($comments[$item['student_id']])) {
                    $item['comment'] = $comments[$item['student_id']];
                }
            }
            $db->query("UPDATE ratings SET data = ?, published = 1, updated_at = NOW() WHERE id = ?", [json_encode($data), $ratingId]);
        } else {
            $db->query("UPDATE ratings SET published = 1, updated_at = NOW() WHERE id = ?", [$ratingId]);
        }

        return ApiResponse::success(['message' => 'Rating published']);
    }

    /**
     * Снять публикацию рейтинга.
     */
    public function ratingUnpublish(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'rating.publish']);

        $ratingId = (int)($payload['rating_id'] ?? 0);
        if ($ratingId <= 0) return ApiResponse::error('rating_id is required.', 400);

        $db->query("UPDATE ratings SET published = 0, updated_at = NOW() WHERE id = ?", [$ratingId]);

        return ApiResponse::success(['message' => 'Rating unpublished']);
    }

    /**
     * Установить видимость места ученику (глобальная настройка).
     */
    public function ratingSetVisibility(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'rating.publish']);

        $show = isset($payload['show']) ? (bool)$payload['show'] : false;

        // Сохраняем в настройках (например, таблица settings)
        $db->query("INSERT INTO settings (key, value) VALUES ('rating_show_place', ?) ON DUPLICATE KEY UPDATE value = ?", [$show ? '1' : '0', $show ? '1' : '0']);

        return ApiResponse::success(['message' => 'Visibility setting updated']);
    }


    // ============================================================
    // 6. АУДИТ
    // ============================================================

    /**
     * Список записей аудита с фильтрацией.
     */
    public function auditList(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'audit.view']);

        $page = (int)($payload['page'] ?? 1);
        $limit = (int)($payload['limit'] ?? 20);
        $dateFrom = $payload['date_from'] ?? null;
        $dateTo = $payload['date_to'] ?? null;
        $user = trim($payload['user'] ?? '');
        $eventType = $payload['event_type'] ?? null;

        $offset = ($page - 1) * $limit;

        $sql = "SELECT SQL_CALC_FOUND_ROWS al.*, u.full_name as user_name
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE 1=1";
        $params = [];

        if ($dateFrom) {
            $sql .= " AND DATE(al.created_at) >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND DATE(al.created_at) <= ?";
            $params[] = $dateTo;
        }
        if ($user) {
            $sql .= " AND u.full_name LIKE ?";
            $params[] = "%$user%";
        }
        if ($eventType) {
            $sql .= " AND al.action = ?";
            $params[] = $eventType;
        }

        $sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $items = $db->fetchAll($sql, $params);
        $total = (int)$db->fetch("SELECT FOUND_ROWS() as total")['total'];

        return ApiResponse::success([
            'items' => $items,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * Экспорт аудита в CSV.
     */
    public function auditExport(DB $db, array $payload): void
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            ApiResponse::error('Token required.', 401)->send();
        }
        try {
            $this->checkAccess($token, ['admin', 'audit.view']);
        } catch (\Exception $e) {
            ApiResponse::error($e->getMessage(), 403)->send();
        }

        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        $user = trim($_GET['user'] ?? '');
        $eventType = $_GET['event_type'] ?? null;

        $sql = "SELECT al.*, u.full_name as user_name
                FROM audit_logs al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE 1=1";
        $params = [];

        if ($dateFrom) {
            $sql .= " AND DATE(al.created_at) >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND DATE(al.created_at) <= ?";
            $params[] = $dateTo;
        }
        if ($user) {
            $sql .= " AND u.full_name LIKE ?";
            $params[] = "%$user%";
        }
        if ($eventType) {
            $sql .= " AND al.action = ?";
            $params[] = $eventType;
        }
        $sql .= " ORDER BY al.created_at DESC";

        $rows = $db->fetchAll($sql, $params);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit_export_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Дата/время', 'Пользователь', 'Действие', 'Сущность', 'ID сущности', 'Старое значение', 'Новое значение']);
        foreach ($rows as $row) {
            fputcsv($output, [
                $row['created_at'],
                $row['user_name'] ?? '—',
                $row['action'],
                $row['entity_type'],
                $row['entity_id'],
                $row['old_value'],
                $row['new_value'],
            ]);
        }
        fclose($output);
        exit;
    }


    // ============================================================
    // 7. ПРАВА ДОСТУПА
    // ============================================================

    /**
     * Получить права пользователя.
     */
    public function permissionsGet(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'permissions.view']);

        $userId = (int)($payload['user_id'] ?? 0);
        if ($userId <= 0) return ApiResponse::error('user_id is required.', 400);

        $user = $db->fetch("SELECT role FROM users WHERE id = ?", [$userId]);
        if (!$user) return ApiResponse::error('User not found.', 404);

        if ($user['role'] === 'custom') {
            $perms = $db->fetchAll("SELECT permission FROM user_permissions WHERE user_id = ?", [$userId]);
            $permissions = array_column($perms, 'permission');
            return ApiResponse::success(['role' => null, 'permissions' => $permissions]);
        } else {
            return ApiResponse::success(['role' => $user['role'], 'permissions' => []]);
        }
    }

    /**
     * Назначить права пользователю.
     */
    public function permissionsSet(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'permissions.edit']);

        $userId = (int)($payload['user_id'] ?? 0);
        if ($userId <= 0) return ApiResponse::error('user_id is required.', 400);

        $type = $payload['type'] ?? 'standard';
        if ($type === 'standard') {
            $role = $payload['role'] ?? null;
            $allowed = ['admin','moderator','teacher','parent','student','canteen','educator','kpp'];
            if (!$role || !in_array($role, $allowed)) return ApiResponse::error('Invalid role.', 400);
            $db->query("UPDATE users SET role = ? WHERE id = ?", [$role, $userId]);
            $db->query("DELETE FROM user_permissions WHERE user_id = ?", [$userId]);
            return ApiResponse::success(['message' => 'Role assigned']);
        } elseif ($type === 'custom') {
            $permissions = $payload['permissions'] ?? [];
            if (!is_array($permissions)) return ApiResponse::error('permissions must be array.', 400);
            $db->query("UPDATE users SET role = 'custom' WHERE id = ?", [$userId]);
            $db->query("DELETE FROM user_permissions WHERE user_id = ?", [$userId]);
            foreach ($permissions as $perm) {
                $db->insert('user_permissions', ['user_id' => $userId, 'permission' => $perm]);
            }
            return ApiResponse::success(['message' => 'Custom permissions saved']);
        } else {
            return ApiResponse::error('Invalid type.', 400);
        }
    }

    /**
     * Список всех возможных разрешений (группированный).
     */
    public function permissionsList(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'permissions.view']);

        $rows = $db->fetchAll("SELECT id, name, group_name, label FROM permissions ORDER BY group_name, name");
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['group_name']][] = [
                'id'    => $row['id'],
                'name'  => $row['name'],
                'label' => $row['label'] ?? $row['name'],
            ];
        }
        return ApiResponse::success($grouped);
    }

    /**
     * Поиск пользователей для назначения прав.
     */
    public function userSearch(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'permissions.view']);

        $query = trim($payload['query'] ?? '');
        if (strlen($query) < 2) return ApiResponse::success([]);

        $users = $db->fetchAll("
            SELECT id, full_name, email, role
            FROM users
            WHERE full_name LIKE ? OR email LIKE ?
            LIMIT 20
        ", ["%$query%", "%$query%"]);

        return ApiResponse::success($users);
    }

    // ============================================================
    // 8. ДОПОЛНИТЕЛЬНЫЕ МЕТОДЫ (GET, назначение тегов, список учеников)
    // ============================================================

    /**
     * Получить одну категорию по ID.
     */
    public function categoryGet(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'categories.view']);

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id is required.', 400);

        $category = $db->fetch("SELECT id, name, weight FROM achievement_categories WHERE id = ?", [$id]);
        if (!$category) return ApiResponse::error('Category not found.', 404);

        return ApiResponse::success($category);
    }

    /**
     * Получить один тег по ID.
     */
    public function tagGet(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'tags.view']);

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id is required.', 400);

        $tag = \App\Models\EventTag::find($id);
        if (!$tag) return ApiResponse::error('Tag not found.', 404);

        return ApiResponse::success($tag);
    }

    /**
     * Получить один шаблон по ID.
     */
    public function templateGet(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'templates.view']);

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id is required.', 400);

        $template = \App\Models\DocumentTemplate::find($id);
        if (!$template) return ApiResponse::error('Template not found.', 404);

        return ApiResponse::success($template);
    }

    /**
     * Получить список учеников по ID классов (для отчётов).
     */
    public function studentList(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'students.view']);

        $classIds = isset($payload['class_ids']) && is_array($payload['class_ids']) 
                    ? array_map('intval', $payload['class_ids']) 
                    : [];

        if (empty($classIds)) {
            return ApiResponse::success([]);
        }

        $placeholders = implode(',', array_fill(0, count($classIds), '?'));
        $sql = "SELECT s.id, u.full_name, c.name as class_name
                FROM students s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN classes c ON s.class_id = c.id
                WHERE s.class_id IN ($placeholders) AND s.status = 'active'
                ORDER BY u.full_name";

        $students = $db->fetchAll($sql, $classIds);
        return ApiResponse::success($students);
    }

    /**
     * Назначить теги ученику.
     */
    public function tagAssign(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'tags.edit']);

        $studentId = (int)($payload['student_id'] ?? 0);
        $tagIds = isset($payload['tag_ids']) && is_array($payload['tag_ids']) 
                ? array_map('intval', $payload['tag_ids']) 
                : [];

        if ($studentId <= 0 || empty($tagIds)) {
            return ApiResponse::error('student_id and tag_ids are required.', 400);
        }

        // Проверяем, существует ли ученик
        $student = $db->fetch("SELECT id FROM students WHERE id = ?", [$studentId]);
        if (!$student) return ApiResponse::error('Student not found.', 404);

        // Удаляем старые связи
        $db->query("DELETE FROM student_tags WHERE student_id = ?", [$studentId]);

        // Вставляем новые
        foreach ($tagIds as $tagId) {
            $db->insert('student_tags', ['student_id' => $studentId, 'tag_id' => $tagId]);
        }

        return ApiResponse::success(['message' => 'Tags assigned']);
    }

    /**
     * Массовое назначение тегов всем ученикам класса.
     */
    public function tagAssignMass(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'tags.edit']);

        $classId = (int)($payload['class_id'] ?? 0);
        $tagIds = isset($payload['tag_ids']) && is_array($payload['tag_ids']) 
                ? array_map('intval', $payload['tag_ids']) 
                : [];

        if ($classId <= 0 || empty($tagIds)) {
            return ApiResponse::error('class_id and tag_ids are required.', 400);
        }

        // Получаем всех активных учеников класса
        $students = $db->fetchAll("SELECT id FROM students WHERE class_id = ? AND status = 'active'", [$classId]);
        if (empty($students)) {
            return ApiResponse::success(['message' => 'No students found in this class']);
        }

        $studentIds = array_column($students, 'id');

        // Удаляем старые связи для этих учеников
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $db->query("DELETE FROM student_tags WHERE student_id IN ($placeholders)", $studentIds);

        // Вставляем новые связи для каждого ученика и каждого тега
        $inserted = 0;
        foreach ($studentIds as $sid) {
            foreach ($tagIds as $tid) {
                $db->insert('student_tags', ['student_id' => $sid, 'tag_id' => $tid]);
                $inserted++;
            }
        }

        return ApiResponse::success(['message' => "Tags assigned to {$inserted} student-tag pairs"]);
    }

    // ============================================================
    // 9. РЕДАКТИРОВАНИЕ КЛАССОВ И УЧЕБНЫХ ГОДОВ
    // ============================================================

    /**
     * Получить данные класса по ID (для редактирования).
     */
    public function classGet(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'classes.view']);

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id is required.', 400);

        $class = $db->fetch("SELECT id, name, academic_year_id, teacher_id FROM classes WHERE id = ?", [$id]);
        if (!$class) return ApiResponse::error('Class not found.', 404);

        return ApiResponse::success($class);
    }

    /**
     * Получить данные учебного года по ID (для редактирования).
     */
    public function academicYearGet(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'classes.view']);

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id required.', 400);

        $year = $db->fetch("SELECT id, name, start_date, end_date, is_current FROM academic_years WHERE id = ?", [$id]);
        if (!$year) return ApiResponse::error('Academic year not found.', 404);

        return ApiResponse::success($year);
    }

    /**
     * Обновить учебный год.
     */
    public function academicYearUpdate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        $this->checkAccess($token, ['admin', 'classes.edit']);

        $id = (int)($payload['id'] ?? 0);
        if ($id <= 0) return ApiResponse::error('id required.', 400);

        $updateData = [];
        if (isset($payload['name'])) $updateData['name'] = $payload['name'];
        if (isset($payload['start_date'])) $updateData['start_date'] = $payload['start_date'];
        if (isset($payload['end_date'])) $updateData['end_date'] = $payload['end_date'];
        if (isset($payload['is_current'])) $updateData['is_current'] = (int)$payload['is_current'];

        if (empty($updateData)) {
            return ApiResponse::error('No fields to update.', 400);
        }

        $updated = $db->update('academic_years', $updateData, 'id = :id', ['id' => $id]);
        if ($updated === 0) {
            return ApiResponse::error('Year not updated (no changes?)', 400);
        }

        return ApiResponse::success(['message' => 'Academic year updated']);
    }
}