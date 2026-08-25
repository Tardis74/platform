<?php

namespace App\Models;

use App\Core\DB;

class LeaveRequest
{
    public static function create(array $data): int
    {
        $db = DB::getInstance();
        return $db->insert('leave_requests', $data);
    }

    public static function find(int $id): ?array
    {
        $db = DB::getInstance();
        return $db->fetch("SELECT * FROM leave_requests WHERE id = :id", ['id' => $id]);
    }

    public static function findWithStudent(int $id): ?array
    {
        $db = DB::getInstance();
        return $db->fetch("
            SELECT lr.*, 
                   u.full_name as student_name, 
                   s.snils_masked, 
                   c.name as class_name,
                   s.is_dormitory
            FROM leave_requests lr
            JOIN students s ON lr.student_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE lr.id = :id
        ", ['id' => $id]);
    }

    public static function updateStatus(int $id, string $status, ?string $comment = null): bool
    {
        $db = DB::getInstance();
        $sql = "UPDATE leave_requests SET status = :status, moderator_comment = :comment, updated_at = NOW() WHERE id = :id";
        $stmt = $db->query($sql, ['status' => $status, 'comment' => $comment, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function setQrCode(int $id, string $qrCode): bool
    {
        $db = DB::getInstance();
        $stmt = $db->query("UPDATE leave_requests SET qr_code = :qr_code WHERE id = :id", ['qr_code' => $qrCode, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function setExitTime(int $id, string $time): bool
    {
        $db = DB::getInstance();
        $stmt = $db->query("UPDATE leave_requests SET exit_time = :exit_time WHERE id = :id", ['exit_time' => $time, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function setEntryTime(int $id, string $time): bool
    {
        $db = DB::getInstance();
        $stmt = $db->query("UPDATE leave_requests SET entry_time = :entry_time WHERE id = :id", ['entry_time' => $time, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Список заявлений для родителя (по его детям)
     */
    public static function getByParent(int $parentId, ?string $status = null): array
    {
        $db = DB::getInstance();
        $sql = "
            SELECT lr.*, u.full_name as student_name, c.name as class_name
            FROM leave_requests lr
            JOIN students s ON lr.student_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE s.id IN (SELECT student_id FROM parent_student WHERE parent_id = :parent_id)
        ";
        $params = ['parent_id' => $parentId];
        if ($status) {
            $sql .= " AND lr.status = :status";
            $params['status'] = $status;
        }
        $sql .= " ORDER BY lr.created_at DESC";
        return $db->fetchAll($sql, $params);
    }

    /**
     * Список заявлений ученика
     */
    public static function getByStudent(int $studentId, ?string $status = null): array
    {
        $db = DB::getInstance();
        $sql = "SELECT * FROM leave_requests WHERE student_id = :student_id";
        $params = ['student_id' => $studentId];
        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }
        $sql .= " ORDER BY created_at DESC";
        return $db->fetchAll($sql, $params);
    }

    /**
     * Получить заявления со статусом pending (для воспитателя)
     */
    public static function getPending(array $filters = []): array
    {
        $db = DB::getInstance();
        $sql = "
            SELECT lr.*, u.full_name as student_name, c.name as class_name, 
                   creator.full_name as created_by_name
            FROM leave_requests lr
            JOIN students s ON lr.student_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN classes c ON s.class_id = c.id
            JOIN users creator ON lr.created_by = creator.id
            WHERE lr.status = 'pending'
        ";
        $params = [];
        if (!empty($filters['date_from'])) {
            $sql .= " AND lr.start_time >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND lr.start_time <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        $sql .= " ORDER BY lr.start_time ASC";
        return $db->fetchAll($sql, $params);
    }

    /**
     * Получить подтверждённые заявления на сегодня (для КПП)
     */
    public static function getTodayApproved(): array
    {
        $db = DB::getInstance();
        $today = date('Y-m-d 00:00:00');
        $tomorrow = date('Y-m-d 23:59:59');
        $sql = "
            SELECT lr.*, u.full_name as student_name, s.snils_masked, c.name as class_name
            FROM leave_requests lr
            JOIN students s ON lr.student_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE lr.status IN ('approved', 'exited')
              AND lr.start_time <= :tomorrow
              AND lr.end_time >= :today
            ORDER BY lr.start_time
        ";
        return $db->fetchAll($sql, ['today' => $today, 'tomorrow' => $tomorrow]);
    }

    /**
     * Поиск активных заявлений по фамилии ученика
     */
    public static function searchByStudentName(string $query): array
    {
        $db = DB::getInstance();
        $sql = "
            SELECT lr.*, u.full_name as student_name, s.snils_masked, c.name as class_name
            FROM leave_requests lr
            JOIN students s ON lr.student_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE lr.status IN ('approved', 'exited')
              AND u.full_name LIKE :query
            ORDER BY lr.start_time
        ";
        return $db->fetchAll($sql, ['query' => '%' . $query . '%']);
    }

    /**
     * Проверить просроченные заявления и обновить статус
     */
    public static function markOverdue(): int
    {
        $db = DB::getInstance();
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE leave_requests 
                SET status = 'overdue', updated_at = NOW() 
                WHERE status IN ('approved', 'exited') AND end_time < :now";
        $stmt = $db->query($sql, ['now' => $now]);
        return $stmt->rowCount();
    }
}