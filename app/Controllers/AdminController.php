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

    // Категории
    public function categoryList(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->requireRole($token, ['admin', 'moderator']); } catch (RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }
        return ApiResponse::success(\App\Models\EventCategory::all());
    }

    /**
     * Создать новую категорию (только admin).
     */
    public function categoryCreate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->requireRole($token, ['admin']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (empty($payload['name'])) {
            return ApiResponse::error('name is required.', 400);
        }

        try {
            $id = \App\Models\EventCategory::create($payload['name']);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to create category: ' . $e->getMessage(), 500);
        }

        $log = date('Y-m-d H:i:s') . " Category created ID $id by user {$user['id']}\n";
        file_put_contents(__DIR__ . '/../../storage/logs/events.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'category_id' => $id,
            'message'     => 'Категория создана',
        ]);
    }

    /**
     * Обновить категорию (только admin).
     */
    public function categoryUpdate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->requireRole($token, ['admin']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $categoryId = (int)($payload['id'] ?? 0);
        if ($categoryId <= 0) {
            return ApiResponse::error('id is required and must be positive.', 400);
        }
        if (empty($payload['name'])) {
            return ApiResponse::error('name is required.', 400);
        }

        $category = \App\Models\EventCategory::find($categoryId);
        if (!$category) {
            return ApiResponse::error('Category not found.', 404);
        }

        try {
            $updated = \App\Models\EventCategory::update($categoryId, $payload['name']);
            if (!$updated) {
                return ApiResponse::error('Category not updated (no changes?)', 400);
            }
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update category: ' . $e->getMessage(), 500);
        }

        $user = $this->getCurrentUser($token);
        $log = date('Y-m-d H:i:s') . " Category updated ID $categoryId by user {$user['id']}\n";
        file_put_contents(__DIR__ . '/../../storage/logs/events.log', $log, FILE_APPEND);

        return ApiResponse::success(['message' => 'Категория обновлена']);
    }

    /**
     * Удалить категорию (только admin).
     */
    public function categoryDelete(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->requireRole($token, ['admin']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $categoryId = (int)($payload['id'] ?? 0);
        if ($categoryId <= 0) {
            return ApiResponse::error('id is required and must be positive.', 400);
        }

        $category = \App\Models\EventCategory::find($categoryId);
        if (!$category) {
            return ApiResponse::error('Category not found.', 404);
        }

        try {
            $deleted = \App\Models\EventCategory::delete($categoryId);
            if (!$deleted) {
                return ApiResponse::error('Category not deleted.', 400);
            }
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete category: ' . $e->getMessage(), 500);
        }

        $user = $this->getCurrentUser($token);
        $log = date('Y-m-d H:i:s') . " Category deleted ID $categoryId by user {$user['id']}\n";
        file_put_contents(__DIR__ . '/../../storage/logs/events.log', $log, FILE_APPEND);

        return ApiResponse::success(['message' => 'Категория удалена']);
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
            $this->requireRole($token, ['admin']);
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
            $this->requireRole($token, ['admin']);
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
            $this->requireRole($token, ['admin']);
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
        try { $this->requireRole($token, 'admin'); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }
        return ApiResponse::success(\App\Models\DocumentTemplate::all());
    }

    public function templateCreate(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->requireRole($token, 'admin'); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

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
        try { $this->requireRole($token, 'admin'); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

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
        try { $this->requireRole($token, 'admin'); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

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
}