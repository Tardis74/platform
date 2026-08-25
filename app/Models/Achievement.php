<?php

namespace App\Models;

use App\Core\DB;

class Achievement
{
    public static function create(array $data): int
    {
        $db = DB::getInstance();
        return $db->insert('achievements', $data);
    }

    public static function find(int $id): ?array
    {
        $db = DB::getInstance();
        return $db->fetch("SELECT a.*, c.name as category_name, c.weight 
                           FROM achievements a
                           JOIN achievement_categories c ON a.category_id = c.id
                           WHERE a.id = :id", ['id' => $id]);
    }

    public static function getByStudent(int $studentId, ?int $categoryId = null, ?int $year = null): array
    {
        $db = DB::getInstance();
        $sql = "SELECT a.*, c.name as category_name 
                FROM achievements a
                JOIN achievement_categories c ON a.category_id = c.id
                WHERE a.student_id = :student_id";
        $params = ['student_id' => $studentId];

        if ($categoryId) {
            $sql .= " AND a.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }
        if ($year) {
            $sql .= " AND YEAR(a.created_at) = :year";
            $params['year'] = $year;
        }
        $sql .= " ORDER BY a.created_at DESC";

        return $db->fetchAll($sql, $params);
    }

    public static function getPending(?int $studentId = null): array
    {
        $db = DB::getInstance();
        $sql = "SELECT a.*, u.full_name as student_name, c.name as class_name, cat.name as category_name
                FROM achievements a
                JOIN students s ON a.student_id = s.id
                JOIN users u ON s.user_id = u.id
                LEFT JOIN classes c ON s.class_id = c.id
                JOIN achievement_categories cat ON a.category_id = cat.id
                WHERE a.status = 'pending'";
        $params = [];
        if ($studentId) {
            $sql .= " AND a.student_id = :student_id";
            $params['student_id'] = $studentId;
        }
        $sql .= " ORDER BY a.created_at ASC";
        return $db->fetchAll($sql, $params);
    }

    public static function updateStatus(int $id, string $status, ?string $comment = null): bool
    {
        $db = DB::getInstance();
        $sql = "UPDATE achievements SET status = :status, moderator_comment = :comment, updated_at = NOW() WHERE id = :id";
        $stmt = $db->query($sql, ['status' => $status, 'comment' => $comment, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function getCategoryWeight(int $achievementId): ?int
    {
        $db = DB::getInstance();
        $row = $db->fetch("SELECT c.weight FROM achievements a JOIN achievement_categories c ON a.category_id = c.id WHERE a.id = :id", ['id' => $achievementId]);
        return $row ? (int)$row['weight'] : null;
    }
}