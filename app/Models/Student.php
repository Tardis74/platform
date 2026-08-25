<?php

namespace App\Models;

use App\Core\DB;

/**
 * Модель ученика (расширяет пользователя с ролью 'student').
 */
class Student
{
    /**
     * Найти ученика по ID.
     *
     * @param int $id
     * @return array|null
     */
    public static function find(int $id): ?array
    {
        $db = DB::getInstance();
        return $db->fetch(
            "SELECT s.*, u.email, u.full_name, u.role, c.name as class_name
             FROM students s
             JOIN users u ON s.user_id = u.id
             LEFT JOIN classes c ON s.class_id = c.id
             WHERE s.id = :id",
            ['id' => $id]
        );
    }

    /**
     * Найти ученика по user_id.
     *
     * @param int $userId
     * @return array|null
     */
    public static function findByUserId(int $userId): ?array
    {
        $db = DB::getInstance();
        return $db->fetch(
            "SELECT * FROM students WHERE user_id = :user_id",
            ['user_id' => $userId]
        );
    }

    /**
     * Найти ученика по хэшу СНИЛС (для проверки дубликатов).
     *
     * @param string $snilsHash
     * @return array|null
     */
    public static function findBySnilsHash(string $snilsHash): ?array
    {
        $db = DB::getInstance();
        return $db->fetch(
            "SELECT * FROM students WHERE snils_hash = :hash",
            ['hash' => $snilsHash]
        );
    }

    /**
     * Создать запись ученика (привязка к существующему пользователю).
     *
     * @param array $data Должен содержать user_id, snils_hash, class_id (опционально), birth_date (опционально)
     * @return int ID записи students
     */
    public static function create(array $data): int
    {
        $db = DB::getInstance();
        return $db->insert('students', $data);
    }

    /**
     * Обновить рейтинг (total_points) инкрементально.
     *
     * @param int $studentId
     * @param int $pointsChange (может быть отрицательным)
     * @return bool
     */
    public static function updatePoints(int $studentId, int $pointsChange): bool
    {
        $db = DB::getInstance();
        $sql = "UPDATE students SET total_points = total_points + :change WHERE id = :id";
        $stmt = $db->query($sql, ['change' => $pointsChange, 'id' => $studentId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Получить всех учеников класса.
     *
     * @param int $classId
     * @return array
     */
    public static function getByClass(int $classId): array
    {
        $db = DB::getInstance();
        return $db->fetchAll(
            "SELECT s.*, u.full_name, u.email
             FROM students s
             JOIN users u ON s.user_id = u.id
             WHERE s.class_id = :class_id",
            ['class_id' => $classId]
        );
    }


    /**
     * Найти учеников класса, ожидающих подтверждения.
     *
     * @param int $classId
     * @return array
     */
    public static function findPendingByClass(int $classId): array
    {
        $db = DB::getInstance();
        return $db->fetchAll(
            "SELECT s.id, u.full_name, s.birth_date, s.snils_masked, c.name as class_name, s.created_at
             FROM students s
             JOIN users u ON s.user_id = u.id
             LEFT JOIN classes c ON s.class_id = c.id
             WHERE s.class_id = :class_id AND s.status = 'awaiting_confirmation'
             ORDER BY u.full_name",
            ['class_id' => $classId]
        );
    }

    /**
     * Найти всех учеников, ожидающих подтверждения (для администратора).
     *
     * @return array
     */
    public static function findAllPending(): array
    {
        $db = DB::getInstance();
        return $db->fetchAll(
            "SELECT s.id, u.full_name, s.birth_date, s.snils_masked, c.name as class_name, s.class_id, s.created_at
             FROM students s
             JOIN users u ON s.user_id = u.id
             LEFT JOIN classes c ON s.class_id = c.id
             WHERE s.status = 'awaiting_confirmation'
             ORDER BY u.full_name"
        );
    }

    /**
     * Подтвердить ученика (изменить статус на active).
     */
    public static function confirm(int $studentId): bool
    {
        $db = DB::getInstance();
        $sql = "UPDATE students SET status = 'active' WHERE id = :id AND status = 'awaiting_confirmation'";
        $stmt = $db->query($sql, ['id' => $studentId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Отклонить ученика (статус rejected, причина).
     */
    public static function reject(int $studentId, ?string $reason = null): bool
    {
        $db = DB::getInstance();
        $sql = "UPDATE students SET status = 'rejected', rejection_reason = :reason WHERE id = :id AND status = 'awaiting_confirmation'";
        $stmt = $db->query($sql, ['id' => $studentId, 'reason' => $reason]);
        return $stmt->rowCount() > 0;
    }
}