<?php
namespace App\Models;

use App\Core\DB;

class CanteenSeating
{
    public static function getByClass(int $classId): array
    {
        $db = DB::getInstance();
        return $db->fetchAll(
            "SELECT cs.*, u.full_name as student_name
             FROM canteen_seating cs
             JOIN students s ON cs.student_id = s.id
             JOIN users u ON s.user_id = u.id
             WHERE cs.class_id = :class_id
             ORDER BY cs.table_number, cs.seat_number",
            ['class_id' => $classId]
        );
    }

    public static function getByStudent(int $studentId): ?array
    {
        $db = DB::getInstance();
        return $db->fetch(
            "SELECT * FROM canteen_seating WHERE student_id = :student_id",
            ['student_id' => $studentId]
        );
    }

    /**
     * Массовое обновление рассадки для класса.
     * Удаляет старые записи и вставляет новые.
     */
    public static function setForClass(int $classId, array $seats, int $updatedBy): bool
    {
        $db = DB::getInstance();
        $db->getPdo()->beginTransaction();

        try {
            // Удаляем старые записи
            $db->query("DELETE FROM canteen_seating WHERE class_id = :class_id", ['class_id' => $classId]);

            // Вставляем новые
            foreach ($seats as $seat) {
                if (empty($seat['student_id']) || !isset($seat['table_number']) || !isset($seat['seat_number'])) {
                    continue;
                }
                $db->insert('canteen_seating', [
                    'class_id'     => $classId,
                    'student_id'   => (int)$seat['student_id'],
                    'table_number' => (int)$seat['table_number'],
                    'seat_number'  => (int)$seat['seat_number'],
                    'updated_by'   => $updatedBy,
                ]);
            }

            $db->getPdo()->commit();
            return true;
        } catch (\Exception $e) {
            $db->getPdo()->rollBack();
            throw $e;
        }
    }

    public static function clearForClass(int $classId): bool
    {
        $db = DB::getInstance();
        $stmt = $db->query("DELETE FROM canteen_seating WHERE class_id = :class_id", ['class_id' => $classId]);
        return $stmt->rowCount() >= 0;
    }

    /**
     * Получить рассадку для сегодняшнего дня с учётом отметок.
     * Возвращает структурированный массив по потокам.
     */
    public static function getTodayWithAttendance(string $date): array
    {
        $db = DB::getInstance();

        // Получаем учеников, которые сегодня присутствуют (есть запись is_present = 1)
        // и имеют место в рассадке
        $sql = "SELECT cs.table_number, cs.seat_number,
                       s.id as student_id, u.full_name as student_name,
                       c.id as class_id, c.name as class_name,
                       s.is_dormitory
                FROM canteen_seating cs
                JOIN students s ON cs.student_id = s.id
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class_id = c.id
                JOIN canteen_attendance ca ON ca.student_id = s.id AND ca.date = :date
                WHERE ca.is_present = 1
                ORDER BY c.name, cs.table_number, cs.seat_number";

        $rows = $db->fetchAll($sql, ['date' => $date]);

        // Группируем по потокам (8-9 классы – первый, 10-11 – второй)
        $result = [
            'first_flow'  => [], // классы 8,9
            'second_flow' => [], // классы 10,11
        ];

        foreach ($rows as $row) {
            $classId = (int)$row['class_id'];
            // Определяем поток по номеру класса (извлекаем из названия класса, например "10А")
            // Для простоты парсим номер из имени класса (первая цифра)
            $classNumber = (int)filter_var($row['class_name'], FILTER_SANITIZE_NUMBER_INT);
            if ($classNumber >= 8 && $classNumber <= 9) {
                $result['first_flow'][] = $row;
            } elseif ($classNumber >= 10 && $classNumber <= 11) {
                $result['second_flow'][] = $row;
            } else {
                // Если класс не входит в указанные диапазоны, помещаем в первый (можно настроить)
                $result['first_flow'][] = $row;
            }
        }

        return $result;
    }

    /**
     * Получить все записи рассадки для выгрузки (без фильтра по отметкам).
     */
    public static function getAllForExport(?string $date = null): array
    {
        $db = DB::getInstance();
        $sql = "SELECT cs.table_number, cs.seat_number,
                       u.full_name as student_name, c.name as class_name,
                       ca.is_present
                FROM canteen_seating cs
                JOIN students s ON cs.student_id = s.id
                JOIN users u ON s.user_id = u.id
                JOIN classes c ON s.class_id = c.id
                LEFT JOIN canteen_attendance ca ON ca.student_id = s.id AND ca.date = :date
                ORDER BY c.name, cs.table_number, cs.seat_number";
        return $db->fetchAll($sql, ['date' => $date ?: date('Y-m-d')]);
    }
}