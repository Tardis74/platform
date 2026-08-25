<?php

namespace App\Models;

use App\Core\DB;

/**
 * Модель заявки на привязку родителя к ученику.
 */
class LinkRequest
{
    /**
     * Создать заявку.
     *
     * @param int $parentId
     * @param int $studentId
     * @param string $status = 'pending'
     * @return int
     */
    public static function create(int $parentId, int $studentId, string $status = 'pending'): int
    {
        $db = DB::getInstance();
        return $db->insert('link_requests', [
            'parent_id'   => $parentId,
            'student_id'  => $studentId,
            'status'      => $status,
            'created_at'  => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Обновить статус заявки.
     *
     * @param int $requestId
     * @param string $status (approved, rejected)
     * @return bool
     */
    public static function updateStatus(int $requestId, string $status): bool
    {
        $db = DB::getInstance();
        $sql = "UPDATE link_requests SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $db->query($sql, ['status' => $status, 'id' => $requestId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Получить заявки для ученика.
     *
     * @param int $studentId
     * @param string|null $status
     * @return array
     */
    public static function getByStudent(int $studentId, ?string $status = null): array
    {
        $db = DB::getInstance();
        $sql = "SELECT * FROM link_requests WHERE student_id = :student_id";
        $params = ['student_id' => $studentId];
        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }
        return $db->fetchAll($sql, $params);
    }

    /**
     * Одобрить все заявки для ученика.
     */
    public static function approveByStudent(int $studentId): void
    {
        $db = DB::getInstance();
        $sql = "UPDATE link_requests SET status = 'approved', updated_at = NOW() 
                WHERE student_id = :student_id AND status = 'pending'";
        $db->query($sql, ['student_id' => $studentId]);
    }
}