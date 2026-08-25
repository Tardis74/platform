<?php

namespace App\Models;

use App\Core\DB;

class EventCategory
{
    public static function find(int $id): ?array
    {
        $db = DB::getInstance();
        return $db->fetch("SELECT * FROM event_categories WHERE id = :id", ['id' => $id]);
    }

    public static function all(): array
    {
        $db = DB::getInstance();
        return $db->fetchAll("SELECT * FROM event_categories ORDER BY name");
    }

    public static function create(string $name): int
    {
        $db = DB::getInstance();
        return $db->insert('event_categories', ['name' => $name]);
    }

    public static function update(int $id, string $name): bool
    {
        $db = DB::getInstance();
        $stmt = $db->query("UPDATE event_categories SET name = :name WHERE id = :id", ['name' => $name, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $db = DB::getInstance();
        $stmt = $db->query("DELETE FROM event_categories WHERE id = :id", ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}