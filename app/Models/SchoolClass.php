<?php

namespace App\Models;

use App\Core\DB;

/**
 * Модель класса.
 */
class SchoolClass
{
    /**
     * Найти класс по ID.
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $db = DB::getInstance();
        return $db->fetch("SELECT * FROM classes WHERE id = :id", ['id' => $id]);
    }

    /**
     * Получить все классы.
     *
     * @return array
     */
    public static function all(): array
    {
        $db = DB::getInstance();
        return $db->fetchAll("SELECT * FROM classes ORDER BY year DESC, name");
    }

    /**
     * Создать новый класс.
     *
     * @param array $data (name, year, teacher_id)
     * @return int
     */
    public static function create(array $data): int
    {
        $db = DB::getInstance();
        return $db->insert('classes', $data);
    }

    /**
     * Получить классного руководителя.
     *
     * @param int $classId
     * @return array|null
     */
    public static function getTeacher(int $classId): ?array
    {
        $db = DB::getInstance();
        return $db->fetch(
            "SELECT t.*, u.full_name, u.email
             FROM classes c
             JOIN teachers t ON c.teacher_id = t.id
             JOIN users u ON t.user_id = u.id
             WHERE c.id = :id",
            ['id' => $classId]
        );
    }
}