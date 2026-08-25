<?php

namespace App\Models;

use App\Core\DB;

class Event
{
    /**
     * Найти мероприятие по ID с полной информацией.
     */
    public static function find(int $id): ?array
    {
        $db = DB::getInstance();
        $event = $db->fetch("
            SELECT e.*, c.name as category_name
            FROM events e
            LEFT JOIN event_categories c ON e.category_id = c.id
            WHERE e.id = :id
        ", ['id' => $id]);
        if (!$event) {
            return null;
        }

        // Получаем теги
        $tags = $db->fetchAll("
            SELECT t.id, t.name
            FROM event_tag_links l
            JOIN event_tags t ON l.tag_id = t.id
            WHERE l.event_id = :event_id
        ", ['event_id' => $id]);
        $event['tags'] = $tags;

        // Получаем доступные классы
        $classes = $db->fetchAll("
            SELECT c.id, c.name
            FROM event_class_access a
            JOIN classes c ON a.class_id = c.id
            WHERE a.event_id = :event_id
        ", ['event_id' => $id]);
        $event['class_access'] = $classes;

        // Получаем типы проживания
        $dormitory = $db->fetchAll("
            SELECT is_dormitory
            FROM event_dormitory_access
            WHERE event_id = :event_id
        ", ['event_id' => $id]);
        $event['dormitory_access'] = array_column($dormitory, 'is_dormitory');

        return $event;
    }

    /**
     * Создать мероприятие с связями.
     */
    public static function create(array $data, array $classIds = [], array $tagIds = [], array $dormitoryAccess = []): int
    {
        $db = DB::getInstance();
        $db->getPdo()->beginTransaction();

        try {
            $eventId = $db->insert('events', $data);

            // Классы
            foreach ($classIds as $classId) {
                $db->insert('event_class_access', ['event_id' => $eventId, 'class_id' => $classId]);
            }

            // Теги
            foreach ($tagIds as $tagId) {
                $db->insert('event_tag_links', ['event_id' => $eventId, 'tag_id' => $tagId]);
            }

            // Проживание
            foreach ($dormitoryAccess as $isDormitory) {
                $db->insert('event_dormitory_access', ['event_id' => $eventId, 'is_dormitory' => (int)$isDormitory]);
            }

            $db->getPdo()->commit();
            return $eventId;
        } catch (\Exception $e) {
            $db->getPdo()->rollBack();
            throw $e;
        }
    }

    /**
     * Обновить мероприятие и связи.
     */
    public static function update(int $id, array $data, array $classIds = null, array $tagIds = null, array $dormitoryAccess = null): bool
    {
        $db = DB::getInstance();
        $db->getPdo()->beginTransaction();

        try {
            // Обновляем основные поля
            if (!empty($data)) {
                $sets = [];
                $params = ['id' => $id];
                foreach ($data as $field => $value) {
                    $sets[] = "`$field` = :$field";
                    $params[$field] = $value;
                }
                $sql = "UPDATE events SET " . implode(', ', $sets) . " WHERE id = :id";
                $db->query($sql, $params);
            }

            // Обновляем связи, если переданы
            if ($classIds !== null) {
                $db->query("DELETE FROM event_class_access WHERE event_id = :event_id", ['event_id' => $id]);
                foreach ($classIds as $classId) {
                    $db->insert('event_class_access', ['event_id' => $id, 'class_id' => $classId]);
                }
            }

            if ($tagIds !== null) {
                $db->query("DELETE FROM event_tag_links WHERE event_id = :event_id", ['event_id' => $id]);
                foreach ($tagIds as $tagId) {
                    $db->insert('event_tag_links', ['event_id' => $id, 'tag_id' => $tagId]);
                }
            }

            if ($dormitoryAccess !== null) {
                $db->query("DELETE FROM event_dormitory_access WHERE event_id = :event_id", ['event_id' => $id]);
                foreach ($dormitoryAccess as $isDormitory) {
                    $db->insert('event_dormitory_access', ['event_id' => $id, 'is_dormitory' => (int)$isDormitory]);
                }
            }

            $db->getPdo()->commit();
            return true;
        } catch (\Exception $e) {
            $db->getPdo()->rollBack();
            throw $e;
        }
    }

    /**
     * Отменить мероприятие (статус cancelled).
     */
    public static function cancel(int $id): bool
    {
        $db = DB::getInstance();
        $stmt = $db->query("UPDATE events SET status = 'cancelled' WHERE id = :id", ['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Атомарно увеличить current_count, если есть места.
     * Возвращает количество затронутых строк (1 – успешно, 0 – нет мест).
     */
    public static function atomicIncrement(int $eventId): int
    {
        $db = DB::getInstance();
        $sql = "UPDATE events SET current_count = current_count + 1 
                WHERE id = :id AND (max_participants IS NULL OR current_count < max_participants)";
        $stmt = $db->query($sql, ['id' => $eventId]);
        return $stmt->rowCount();
    }

    /**
     * Атомарно уменьшить current_count (при отмене/отклонении).
     */
    public static function atomicDecrement(int $eventId): int
    {
        $db = DB::getInstance();
        $sql = "UPDATE events SET current_count = current_count - 1 
                WHERE id = :id AND current_count > 0";
        $stmt = $db->query($sql, ['id' => $eventId]);
        return $stmt->rowCount();
    }

    /**
     * Проверить, доступно ли мероприятие ученику.
     */
    public static function isAvailableForStudent(int $eventId, int $studentId): bool
    {
        $db = DB::getInstance();
        $student = Student::find($studentId);
        if (!$student) {
            return false;
        }

        // Проверяем класс
        $classId = $student['class_id'];
        if ($classId) {
            $access = $db->fetch("SELECT 1 FROM event_class_access WHERE event_id = :event_id AND class_id = :class_id",
                ['event_id' => $eventId, 'class_id' => $classId]);
            if (!$access) {
                // Если есть записи в event_class_access, но данного класса нет – недоступно
                // Если нет записей вообще – доступно всем
                $any = $db->fetch("SELECT 1 FROM event_class_access WHERE event_id = :event_id", ['event_id' => $eventId]);
                if ($any) {
                    return false;
                }
            }
        } else {
            // Если у ученика нет класса, то доступно только если нет ограничений по классам
            $any = $db->fetch("SELECT 1 FROM event_class_access WHERE event_id = :event_id", ['event_id' => $eventId]);
            if ($any) {
                return false;
            }
        }

        // Проверяем проживание
        $isDormitory = (bool)$student['is_dormitory'];
        $accessDorm = $db->fetch("SELECT 1 FROM event_dormitory_access WHERE event_id = :event_id AND is_dormitory = :is_dormitory",
            ['event_id' => $eventId, 'is_dormitory' => (int)$isDormitory]);
        if (!$accessDorm) {
            // Если есть записи по проживанию, но данного типа нет – недоступно
            $any = $db->fetch("SELECT 1 FROM event_dormitory_access WHERE event_id = :event_id", ['event_id' => $eventId]);
            if ($any) {
                return false;
            }
        }

        // Проверка тегов – не реализована, так как теги не влияют на доступность (только для фильтрации)
        return true;
    }

    /**
     * Получить список мероприятий с фильтрацией и учётом прав.
     */
    public static function list(array $filters, ?int $userId = null, ?string $role = null, ?int $studentId = null): array
    {
        $db = DB::getInstance();
        $params = [];
        $sql = "SELECT e.*, c.name as category_name
                FROM events e
                LEFT JOIN event_categories c ON e.category_id = c.id
                WHERE 1=1";

        // Фильтры
        if (!empty($filters['start_date'])) {
            $sql .= " AND e.start_datetime >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND e.start_datetime <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= " AND e.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }
        if (!empty($filters['tag_id'])) {
            $sql .= " AND EXISTS (SELECT 1 FROM event_tag_links l WHERE l.event_id = e.id AND l.tag_id = :tag_id)";
            $params['tag_id'] = $filters['tag_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND e.status = :status";
            $params['status'] = $filters['status'];
        }

        // Ограничение доступности для учеников и родителей
        if ($role === 'student' && $studentId) {
            // Подзапросы для классов и проживания
            $student = Student::find($studentId);
            if ($student) {
                $classId = $student['class_id'];
                $isDormitory = (int)$student['is_dormitory'];
                $sql .= " AND (
                    (NOT EXISTS (SELECT 1 FROM event_class_access WHERE event_id = e.id) 
                     OR EXISTS (SELECT 1 FROM event_class_access WHERE event_id = e.id AND class_id = :class_id))
                    AND
                    (NOT EXISTS (SELECT 1 FROM event_dormitory_access WHERE event_id = e.id)
                     OR EXISTS (SELECT 1 FROM event_dormitory_access WHERE event_id = e.id AND is_dormitory = :is_dormitory))
                )";
                $params['class_id'] = $classId;
                $params['is_dormitory'] = $isDormitory;
            }
        }

        // Сортировка и пагинация
        $sql .= " ORDER BY e.start_datetime ASC";
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = max(1, min(100, (int)($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $events = $db->fetchAll($sql, $params);

        // Если ученик, добавляем флаг is_registered
        if ($role === 'student' && $studentId) {
            foreach ($events as &$event) {
                $reg = $db->fetch("SELECT status FROM event_registrations WHERE event_id = :event_id AND student_id = :student_id",
                    ['event_id' => $event['id'], 'student_id' => $studentId]);
                $event['is_registered'] = $reg ? true : false;
                $event['registration_status'] = $reg ? $reg['status'] : null;
            }
        }

        // Подсчёт общего количества (для пагинации) – упрощённо, можно отдельный запрос
        return $events;
    }
}