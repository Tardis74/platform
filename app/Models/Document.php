<?php
namespace App\Models;

use App\Core\DB;

class Document
{
    public static function create(array $data): int
    {
        $db = DB::getInstance();
        return $db->insert('documents', $data);
    }

    public static function find(int $id): ?array
    {
        $db = DB::getInstance();
        return $db->fetch("SELECT d.*, t.name as template_name, s.snils_masked 
                           FROM documents d
                           LEFT JOIN document_templates t ON d.template_id = t.id
                           LEFT JOIN students s ON d.student_id = s.id
                           WHERE d.id = :id", ['id' => $id]);
    }

    public static function getByStudent(int $studentId, ?string $status = null): array
    {
        $db = DB::getInstance();
        $sql = "SELECT d.*, t.name as template_name 
                FROM documents d
                LEFT JOIN document_templates t ON d.template_id = t.id
                WHERE d.student_id = :student_id";
        $params = ['student_id' => $studentId];
        if ($status) {
            $sql .= " AND d.status = :status";
            $params['status'] = $status;
        }
        $sql .= " ORDER BY d.created_at DESC";
        return $db->fetchAll($sql, $params);
    }

    public static function getPending(?int $classId = null): array
    {
        $db = DB::getInstance();
        $sql = "SELECT d.*, u.full_name as student_name, c.name as class_name, t.name as template_name
                FROM documents d
                JOIN students s ON d.student_id = s.id
                JOIN users u ON s.user_id = u.id
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN document_templates t ON d.template_id = t.id
                WHERE d.status = 'pending'";
        $params = [];
        if ($classId) {
            $sql .= " AND s.class_id = :class_id";
            $params['class_id'] = $classId;
        }
        $sql .= " ORDER BY d.created_at ASC";
        return $db->fetchAll($sql, $params);
    }

    public static function updateStatus(int $id, string $status, ?string $comment = null): bool
    {
        $db = DB::getInstance();
        $sql = "UPDATE documents SET status = :status, moderator_comment = :comment, updated_at = NOW() WHERE id = :id";
        $stmt = $db->query($sql, ['status' => $status, 'comment' => $comment, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function expireApprovedDocuments(): int
    {
        $db = DB::getInstance();
        $sql = "UPDATE documents SET status = 'expired' 
                WHERE status = 'approved' AND expiry_date IS NOT NULL AND expiry_date < CURDATE()";
        $stmt = $db->query($sql);
        return $stmt->rowCount();
    }
}