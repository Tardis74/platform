<?php

namespace App\Models;

use App\Core\DB;

class EventTag
{
    public static function find(int $id): ?array
    {
        $db = DB::getInstance();
        return $db->fetch("SELECT * FROM event_tags WHERE id = :id", ['id' => $id]);
    }

    public static function findByName(string $name): ?array
    {
        $db = DB::getInstance();
        return $db->fetch("SELECT * FROM event_tags WHERE name = :name", ['name' => $name]);
    }

    public static function all(): array
    {
        $db = DB::getInstance();
        return $db->fetchAll("SELECT * FROM event_tags ORDER BY name");
    }

    public static function create(string $name): int
    {
        $db = DB::getInstance();
        return $db->insert('event_tags', ['name' => $name]);
    }

    public static function update(int $id, string $name): bool
    {
        $db = DB::getInstance();
        $stmt = $db->query("UPDATE event_tags SET name = :name WHERE id = :id", ['name' => $name, 'id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $db = DB::getInstance();
        $stmt = $db->query("DELETE FROM event_tags WHERE id = :id", ['id' => $id]);
        return $stmt->rowCount() > 0;
    }
}