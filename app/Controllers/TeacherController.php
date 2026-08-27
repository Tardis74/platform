<?php

namespace App\Controllers;

use App\Core\DB;
use App\Core\ApiResponse;
use App\Models\Student;
use App\Models\LinkRequest;
use App\Models\ParentStudent;
use RuntimeException;

class TeacherController extends BaseController
{
    public function getPendingStudents(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->checkAccess($token, ['teacher', 'students.view']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $teacher = $db->fetch(
            "SELECT id, class_id FROM teachers WHERE user_id = :user_id",
            ['user_id' => $user['id']]
        );
        if (!$teacher || empty($teacher['class_id'])) {
            return ApiResponse::error('У вас нет привязанного класса.', 404);
        }

        $classId = (int)$teacher['class_id'];
        $students = Student::findPendingByClass($classId);

        // Поле snils_masked уже содержит маску, дополнительных преобразований не нужно
        return ApiResponse::success($students);
    }

    public function confirmStudent(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->checkAccess($token, ['teacher', 'students.edit']);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $teacher = $db->fetch(
            "SELECT id, class_id FROM teachers WHERE user_id = :user_id",
            ['user_id' => $user['id']]
        );
        if (!$teacher || empty($teacher['class_id'])) {
            return ApiResponse::error('У вас нет привязанного класса.', 404);
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

        if ((int)$student['class_id'] !== (int)$teacher['class_id']) {
            return ApiResponse::error('Ученик не из вашего класса.', 403);
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
            date('Y-m-d H:i:s') . " Teacher (user_id: {$user['id']}) confirmed student ID $studentId\n",
            FILE_APPEND
        );

        return ApiResponse::success([
            'student_id' => $studentId,
            'status'     => 'active',
            'message'    => 'Ученик подтверждён'
        ]);
    }

    public function rejectStudent(DB $db, array $payload): ApiResponse
    {
        // аналогично, без маскировки
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }

        try {
            $this->checkAccess($token, ['teacher', checkAccess]);
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $user = $this->getCurrentUser($token);
        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $teacher = $db->fetch(
            "SELECT id, class_id FROM teachers WHERE user_id = :user_id",
            ['user_id' => $user['id']]
        );
        if (!$teacher || empty($teacher['class_id'])) {
            return ApiResponse::error('У вас нет привязанного класса.', 404);
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

        if ((int)$student['class_id'] !== (int)$teacher['class_id']) {
            return ApiResponse::error('Ученик не из вашего класса.', 403);
        }

        $reason = $payload['reason'] ?? null;
        Student::reject($studentId, $reason);

        file_put_contents(
            __DIR__ . '/../../storage/logs/confirmations.log',
            date('Y-m-d H:i:s') . " Teacher (user_id: {$user['id']}) rejected student ID $studentId, reason: " . ($reason ?: 'не указана') . "\n",
            FILE_APPEND
        );

        return ApiResponse::success([
            'student_id' => $studentId,
            'status'     => 'rejected',
            'message'    => 'Ученик отклонён'
        ]);
    }

    /**
     * Получить рассадку для класса.
     */
    public function seatingGet(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['teacher', 'admin', 'canteen.view']); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $classId = isset($payload['class_id']) ? (int)$payload['class_id'] : null;

        if ($user['role'] === 'teacher') {
            $teacher = $db->fetch("SELECT class_id FROM teachers WHERE user_id = :user_id", ['user_id' => $user['id']]);
            if (!$teacher || !$teacher['class_id']) {
                return ApiResponse::error('У вас нет привязанного класса.', 404);
            }
            if ($classId === null) {
                $classId = (int)$teacher['class_id'];
            } elseif ((int)$classId !== (int)$teacher['class_id']) {
                return ApiResponse::error('Вы можете управлять только своим классом.', 403);
            }
        } else {
            // admin может запросить любой класс
            if ($classId === null) {
                return ApiResponse::error('class_id is required for admin.', 400);
            }
        }

        $seating = \App\Models\CanteenSeating::getByClass($classId);
        return ApiResponse::success($seating);
    }

    /**
     * Установить/обновить рассадку для класса (массово).
     */
    public function seatingSet(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['teacher', 'admin', 'canteen.edit']); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $classId = isset($payload['class_id']) ? (int)$payload['class_id'] : null;

        if ($user['role'] === 'teacher') {
            $teacher = $db->fetch("SELECT class_id FROM teachers WHERE user_id = :user_id", ['user_id' => $user['id']]);
            if (!$teacher || !$teacher['class_id']) {
                return ApiResponse::error('У вас нет привязанного класса.', 404);
            }
            if ($classId === null) {
                $classId = (int)$teacher['class_id'];
            } elseif ((int)$classId !== (int)$teacher['class_id']) {
                return ApiResponse::error('Вы можете управлять только своим классом.', 403);
            }
        } else {
            if ($classId === null) {
                return ApiResponse::error('class_id is required for admin.', 400);
            }
        }

        if (empty($payload['seats']) || !is_array($payload['seats'])) {
            return ApiResponse::error('seats array is required.', 400);
        }

        try {
            \App\Models\CanteenSeating::setForClass($classId, $payload['seats'], $user['id']);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to set seating: ' . $e->getMessage(), 500);
        }

        // Логирование
        file_put_contents(
            __DIR__ . '/../../storage/logs/canteen.log',
            date('Y-m-d H:i:s') . " [user_id: {$user['id']}] Обновлена рассадка для класса $classId\n",
            FILE_APPEND
        );

        return ApiResponse::success(['message' => 'Рассадка обновлена']);
    }

    /**
     * Очистить рассадку для класса.
     */
    public function seatingClear(DB $db, array $payload): ApiResponse
    {
        // Аналогичная проверка прав, как в seatingSet
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['teacher', 'admin', 'canteen.edit']); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $classId = isset($payload['class_id']) ? (int)$payload['class_id'] : null;

        if ($user['role'] === 'teacher') {
            $teacher = $db->fetch("SELECT class_id FROM teachers WHERE user_id = :user_id", ['user_id' => $user['id']]);
            if (!$teacher || !$teacher['class_id']) {
                return ApiResponse::error('У вас нет привязанного класса.', 404);
            }
            if ($classId === null) {
                $classId = (int)$teacher['class_id'];
            } elseif ((int)$classId !== (int)$teacher['class_id']) {
                return ApiResponse::error('Вы можете управлять только своим классом.', 403);
            }
        } else {
            if ($classId === null) {
                return ApiResponse::error('class_id is required for admin.', 400);
            }
        }

        \App\Models\CanteenSeating::clearForClass($classId);

        file_put_contents(
            __DIR__ . '/../../storage/logs/canteen.log',
            date('Y-m-d H:i:s') . " [user_id: {$user['id']}] Очищена рассадка для класса $classId\n",
            FILE_APPEND
        );

        return ApiResponse::success(['message' => 'Рассадка очищена']);
    }

    /**
     * Отметить присутствие учеников на обеде.
     */
    public function attendanceMark(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['teacher', 'admin', 'canteen.edit']); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $date = $payload['date'] ?? date('Y-m-d');
        if (!strtotime($date)) {
            return ApiResponse::error('Invalid date format. Use YYYY-MM-DD.', 400);
        }

        // Получаем список student_ids для отметки
        $studentIds = [];
        if (isset($payload['student_ids']) && is_array($payload['student_ids'])) {
            $studentIds = array_map('intval', $payload['student_ids']);
        } elseif (isset($payload['class_id'])) {
            // Отметить весь класс (все ученики класса)
            $classId = (int)$payload['class_id'];
            // Проверка прав на класс
            if ($user['role'] === 'teacher') {
                $teacher = $db->fetch("SELECT class_id FROM teachers WHERE user_id = :user_id", ['user_id' => $user['id']]);
                if (!$teacher || !$teacher['class_id'] || (int)$teacher['class_id'] !== $classId) {
                    return ApiResponse::error('Вы можете отмечать только свой класс.', 403);
                }
            }
            $students = \App\Models\Student::getByClass($classId);
            $studentIds = array_column($students, 'id');
        } else {
            return ApiResponse::error('Either student_ids or class_id must be provided.', 400);
        }

        if (empty($studentIds)) {
            return ApiResponse::error('No students to mark.', 400);
        }

        $count = \App\Models\CanteenAttendance::mark($studentIds, $date, $user['id']);

        file_put_contents(
            __DIR__ . '/../../storage/logs/canteen.log',
            date('Y-m-d H:i:s') . " [user_id: {$user['id']}] Отмечено $count учеников на дату $date\n",
            FILE_APPEND
        );

        return ApiResponse::success(['marked_count' => $count, 'date' => $date]);
    }

    /**
     * Получить отметки для класса за период.
     */
    public function attendanceGet(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['teacher', 'admin', 'canteen.view']); } catch (\RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $classId = isset($payload['class_id']) ? (int)$payload['class_id'] : null;

        if ($user['role'] === 'teacher') {
            $teacher = $db->fetch("SELECT class_id FROM teachers WHERE user_id = :user_id", ['user_id' => $user['id']]);
            if (!$teacher || !$teacher['class_id']) {
                return ApiResponse::error('У вас нет привязанного класса.', 404);
            }
            if ($classId === null) {
                $classId = (int)$teacher['class_id'];
            } elseif ((int)$classId !== (int)$teacher['class_id']) {
                return ApiResponse::error('Вы можете просматривать только свой класс.', 403);
            }
        } else {
            if ($classId === null) {
                return ApiResponse::error('class_id is required for admin.', 400);
            }
        }

        $dateFrom = $payload['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
        $dateTo = $payload['date_to'] ?? date('Y-m-d');
        if (!strtotime($dateFrom) || !strtotime($dateTo)) {
            return ApiResponse::error('Invalid date format.', 400);
        }

        $attendance = \App\Models\CanteenAttendance::getForClass($classId, $dateFrom, $dateTo);
        return ApiResponse::success($attendance);
    }

    /**
     * Получить статистику для дашборда.
     */
    public function getDashboardStats(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['teacher', 'dashboard.view']); } catch (RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $teacher = $db->fetch("SELECT class_id FROM teachers WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$teacher || !$teacher['class_id']) {
            return ApiResponse::error('У вас нет привязанного класса.', 404);
        }
        $classId = (int)$teacher['class_id'];

        // Количество учеников
        $studentsCount = (int)$db->fetch("SELECT COUNT(*) as cnt FROM students WHERE class_id = :class_id AND status = 'active'", ['class_id' => $classId])['cnt'];

        // Ожидающие подтверждения
        $pendingCount = (int)$db->fetch("SELECT COUNT(*) as cnt FROM students WHERE class_id = :class_id AND status = 'awaiting_confirmation'", ['class_id' => $classId])['cnt'];

        // Заявления на выход сегодня
        $today = date('Y-m-d');
        $leaveToday = (int)$db->fetch("
            SELECT COUNT(*) as cnt 
            FROM leave_requests lr
            JOIN students s ON lr.student_id = s.id
            WHERE s.class_id = :class_id AND DATE(lr.start_time) = :today AND lr.status = 'pending'
        ", ['class_id' => $classId, 'today' => $today])['cnt'];

        // Мероприятия на текущую неделю
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd = date('Y-m-d', strtotime('sunday this week'));
        $eventsThisWeek = (int)$db->fetch("
            SELECT COUNT(*) as cnt 
            FROM event_class ec
            JOIN events e ON ec.event_id = e.id
            WHERE ec.class_id = :class_id AND DATE(e.start_datetime) BETWEEN :week_start AND :week_end AND e.status = 'active'
        ", ['class_id' => $classId, 'week_start' => $weekStart, 'week_end' => $weekEnd])['cnt'];

        // Название класса
        $class = $db->fetch("SELECT name FROM classes WHERE id = :id", ['id' => $classId]);

        return ApiResponse::success([
            'class_name' => $class['name'] ?? '—',
            'students_count' => $studentsCount,
            'pending_count' => $pendingCount,
            'leave_today' => $leaveToday,
            'events_this_week' => $eventsThisWeek,
        ]);
    }

    /**
     * Получить список учеников класса (без подтверждения).
     */
    public function getStudents(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['teacher', 'students.view']); } catch (RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $teacher = $db->fetch("SELECT class_id FROM teachers WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$teacher || !$teacher['class_id']) {
            return ApiResponse::error('У вас нет привязанного класса.', 404);
        }
        $classId = (int)$teacher['class_id'];

        $students = Student::getByClass($classId);
        return ApiResponse::success($students);
    }

    /**
     * Получить достижения ученика с возможностью модерации (для учителя).
     */
    public function getStudentAchievements(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['teacher', 'achievements.moderate']); } catch (RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $teacher = $db->fetch("SELECT class_id FROM teachers WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$teacher || !$teacher['class_id']) {
            return ApiResponse::error('У вас нет привязанного класса.', 404);
        }
        $classId = (int)$teacher['class_id'];

        $studentId = (int)($payload['student_id'] ?? 0);
        if ($studentId <= 0) {
            return ApiResponse::error('student_id is required.', 400);
        }

        // Проверка, что ученик из класса учителя
        $student = Student::find($studentId);
        if (!$student || (int)$student['class_id'] !== $classId) {
            return ApiResponse::error('Ученик не из вашего класса.', 403);
        }

        $categoryId = isset($payload['category_id']) ? (int)$payload['category_id'] : null;
        $year = isset($payload['year']) ? (int)$payload['year'] : null;

        $achievements = Achievement::getByStudent($studentId, $categoryId, $year);
        return ApiResponse::success($achievements);
    }

    /**
     * Получить заявления на выход учеников класса.
     */
    public function getLeaveRequests(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) return ApiResponse::error('Token required.', 401);
        try { $this->checkAccess($token, ['teacher', 'leave.view']); } catch (RuntimeException $e) { return ApiResponse::error($e->getMessage(), 403); }

        $user = $this->getCurrentUser($token);
        $teacher = $db->fetch("SELECT class_id FROM teachers WHERE user_id = :user_id", ['user_id' => $user['id']]);
        if (!$teacher || !$teacher['class_id']) {
            return ApiResponse::error('У вас нет привязанного класса.', 404);
        }
        $classId = (int)$teacher['class_id'];

        $status = $payload['status'] ?? null;
        $requests = LeaveRequest::getByClass($classId, $status);
        return ApiResponse::success($requests);
    }
}