<?php
namespace App\Models;

use App\Core\DB;

class CanteenSpecialMeal
{
    public static function add(int $studentId, string $description, int $createdBy): int
    {
        $db = DB::getInstance();
        return $db->insert('canteen_special_meals', [
            'student_id'  => $studentId,
            'description' => $description,
            'created_by'  => $createdBy,
        ]);
    }

    public static function remove(int $id): bool
    {
        $db = DB::getInstance();
        $stmt = $db->query("DELETE FROM canteen_special_meals WHERE id = :id", ['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function getByStudent(int $studentId): array
    {
        $db = DB::getInstance();
        return $db->fetchAll(
            "SELECT * FROM canteen_special_meals WHERE student_id = :student_id",
            ['student_id' => $studentId]
        );
    }

    public static function getAll(): array
    {
        $db = DB::getInstance();
        return $db->fetchAll(
            "SELECT sm.*, u.full_name as student_name
             FROM canteen_special_meals sm
             JOIN students s ON sm.student_id = s.id
             JOIN users u ON s.user_id = u.id
             ORDER BY u.full_name"
        );
    }
}