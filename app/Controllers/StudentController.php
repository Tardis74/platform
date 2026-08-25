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
}