<?php
namespace App\Controllers;

use App\Core\ApiResponse;
use App\Models\Student;
use App\Models\LinkRequest;
use App\Models\SchoolClass;
use App\Models\User;
use App\Core\DB;

class ParentController extends BaseController
{
    public function getChildren($db, $payload): void
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            ApiResponse::error('Требуется авторизация', 401);
        }
        $children = Student::getChildrenByParent($user->id);
        ApiResponse::success($children);
    }

    public function addChild($db, $payload): void
    {
        $user = $this->getCurrentUser();
        if (!$user || $user->role !== 'parent') {
            ApiResponse::error('Доступ запрещен', 403);
        }

        $required = ['snils', 'full_name', 'class_id', 'birth_date'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                ApiResponse::error("Поле $field обязательно", 400);
            }
        }

        $existing = Student::findBySnils($payload['snils']);
        if ($existing) {
            ApiResponse::error('Ученик с таким СНИЛС уже зарегистрирован', 409);
        }

        $classes = SchoolClass::getAll();
        $classExists = false;
        foreach ($classes as $c) {
            if ($c['id'] == $payload['class_id']) {
                $classExists = true;
                break;
            }
        }
        if (!$classExists) {
            ApiResponse::error('Указанный класс не найден', 400);
        }

        $dormitory = isset($payload['dormitory']) && $payload['dormitory'] ? 1 : 0;

        $result = Student::create([
            'snils' => $payload['snils'],
            'full_name' => $payload['full_name'],
            'class_id' => (int)$payload['class_id'],
            'birth_date' => $payload['birth_date'],
            'dormitory' => $dormitory,
        ]);

        Student::linkToParent($result['id'], $user->id);

        ApiResponse::success([
            'student_id' => $result['id'],
            'temporary_password' => $result['password']
        ], 'Ученик добавлен');
    }

    public function linkChild($db, $payload): void
    {
        $user = $this->getCurrentUser();
        if (!$user || $user->role !== 'parent') {
            ApiResponse::error('Доступ запрещен', 403);
        }

        $snils = $payload['snils'] ?? '';
        if (empty($snils)) {
            ApiResponse::error('СНИЛС обязателен', 400);
        }

        $student = Student::findBySnils($snils);
        if (!$student) {
            ApiResponse::error('Ученик с таким СНИЛС не найден', 404);
        }

        $children = Student::getChildrenByParent($user->id);
        foreach ($children as $child) {
            if ($child->id === $student->id) {
                ApiResponse::error('Этот ученик уже привязан к вам', 409);
            }
        }

        $parents = Student::getParents($student->id);
        if (empty($parents)) {
            Student::linkToParent($student->id, $user->id);
            ApiResponse::success(null, 'Ученик успешно привязан');
        } else {
            $toParentId = $parents[0]['id'];
            $requestId = LinkRequest::create($student->id, $user->id, $toParentId);
            ApiResponse::success(['request_id' => $requestId], 'Запрос на привязку отправлен');
        }
    }

    public function getPendingLinks($db, $payload): void
    {
        $user = $this->getCurrentUser();
        if (!$user || $user->role !== 'parent') {
            ApiResponse::error('Доступ запрещен', 403);
        }

        $requests = LinkRequest::getPendingForParent($user->id);
        ApiResponse::success($requests);
    }

    public function confirmLink($db, $payload): void
    {
        $user = $this->getCurrentUser();
        if (!$user || $user->role !== 'parent') {
            ApiResponse::error('Доступ запрещен', 403);
        }

        $requestId = (int)($payload['request_id'] ?? 0);
        $action = $payload['action'] ?? '';

        if ($requestId <= 0 || !in_array($action, ['accepted', 'rejected'])) {
            ApiResponse::error('Неверные параметры', 400);
        }

        $request = DB::fetch("SELECT * FROM link_requests WHERE id = :id", ['id' => $requestId]);
        if (!$request || $request['to_parent_id'] != $user->id) {
            ApiResponse::error('Запрос не найден или не адресован вам', 404);
        }

        $result = LinkRequest::confirm($requestId, $action);
        if (!$result) {
            ApiResponse::error('Не удалось подтвердить запрос', 500);
        }

        ApiResponse::success(null, 'Запрос ' . ($action === 'accepted' ? 'подтвержден' : 'отклонен'));
    }
}