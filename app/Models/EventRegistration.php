<?php

namespace App\Models;

use App\Core\DB;

class EventRegistration
{
    public static function create(array $data): int
    {
        $db = DB::getInstance();
        return $db->insert('event_registrations', $data);
    }

    public static function find(int $id): ?array
    {
        $db = DB::getInstance();
        return $db->fetch("SELECT * FROM event_registrations WHERE id = :id", ['id' => $id]);
    }

    public static function findByEventAndStudent(int $eventId, int $studentId): ?array
    {
        $db = DB::getInstance();
        return $db->fetch(
            "SELECT * FROM event_registrations WHERE event_id = :event_id AND student_id = :student_id",
            ['event_id' => $eventId, 'student_id' => $studentId]
        );
    }

    public static function updateStatus(int $id, string $status, ?string $comment = null): bool
    {
        $db = DB::getInstance();
        $sql = "UPDATE event_registrations SET status = :status, comment = :comment, updated_at = NOW() WHERE id = :id";
        $stmt = $db->query($sql, ['status' => $status, 'comment' => $comment, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Получить заявки с фильтрацией (для модератора).
     */
    public static function getPending(array $filters = [], ?int $classId = null): array
    {
        $db = DB::getInstance();
        $params = [];
        $sql = "SELECT r.id as registration_id, r.status, r.comment, r.registered_at,
                       s.id as student_id, u.full_name as student_name, c.name as class_name,
                       e.id as event_id, e.title as event_title
                FROM event_registrations r
                JOIN students s ON r.student_id = s.id
                JOIN users u ON s.user_id = u.id
                LEFT JOIN classes c ON s.class_id = c.id
                JOIN events e ON r.event_id = e.id
                WHERE 1=1";

        if (!empty($filters['event_id'])) {
            $sql .= " AND r.event_id = :event_id";
            $params['event_id'] = $filters['event_id'];
        }
        if (!empty($filters['student_id'])) {
            $sql .= " AND r.student_id = :student_id";
            $params['student_id'] = $filters['student_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND r.status = :status";
            $params['status'] = $filters['status'];
        } else {
            // По умолчанию только pending
            $sql .= " AND r.status = 'pending'";
        }
        if ($classId !== null) {
            $sql .= " AND s.class_id = :class_id";
            $params['class_id'] = $classId;
        }

        $sql .= " ORDER BY r.registered_at ASC";
        return $db->fetchAll($sql, $params);
    }

    /**
     * Получить заявки ученика.
     */
    public static function getByStudent(int $studentId, array $filters = []): array
    {
        $db = DB::getInstance();
        $params = ['student_id' => $studentId];
        $sql = "SELECT r.*, e.title, e.start_datetime, e.end_datetime, e.location, e.status as event_status
                FROM event_registrations r
                JOIN events e ON r.event_id = e.id
                WHERE r.student_id = :student_id";

        if (!empty($filters['status'])) {
            $sql .= " AND r.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= " AND e.start_datetime >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND e.start_datetime <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }

        $sql .= " ORDER BY e.start_datetime DESC";
        return $db->fetchAll($sql, $params);
    }
}       