<?php
namespace App\Models;

use App\Core\DB;

class CanteenAttendance
{
    public static function mark(array $studentIds, string $date, int $markedBy): int
    {
        $db = DB::getInstance();
        $count = 0;
        foreach ($studentIds as $studentId) {
            $sql = "INSERT INTO canteen_attendance (student_id, date, is_present, marked_by, marked_at)
                    VALUES (:student_id, :date, 1, :marked_by, NOW())
                    ON DUPLICATE KEY UPDATE is_present = 1, marked_by = :marked_by, marked_at = NOW()";
            $stmt = $db->query($sql, [
                'student_id' => (int)$studentId,
                'date'       => $date,
                'marked_by'  => $markedBy
            ]);
            $count += $stmt->rowCount();
        }
        return $count;
    }

    /**
     * Получить отметки для класса за период.
     */
    public static function getForClass(int $classId, string $dateFrom, string $dateTo): array
    {
        $db = DB::getInstance();
        return $db->fetchAll(
            "SELECT s.id as student_id, u.full_name as student_name,
                    ca.date, ca.is_present
             FROM students s
             JOIN users u ON s.user_id = u.id
             LEFT JOIN canteen_attendance ca ON ca.student_id = s.id AND ca.date BETWEEN :date_from AND :date_to
             WHERE s.class_id = :class_id
             ORDER BY u.full_name, ca.date",
            ['class_id' => $classId, 'date_from' => $dateFrom, 'date_to' => $dateTo]
        );
    }

    /**
     * Получить количество присутствующих на конкретную дату.
     */
    public static function countPresentOnDate(string $date): int
    {
        $db = DB::getInstance();
        $row = $db->fetch(
            "SELECT COUNT(*) as cnt FROM canteen_attendance WHERE date = :date AND is_present = 1",
            ['date' => $date]
        );
        return $row ? (int)$row['cnt'] : 0;
    }
}