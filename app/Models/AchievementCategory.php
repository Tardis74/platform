<?php

namespace App\Models;

use App\Core\DB;

class AchievementCategory
{
    public static function find(int $id): ?array
    {
        $db = DB::getInstance();
        return $db->fetch("SELECT * FROM achievement_categories WHERE id = :id", ['id' => $id]);
    }

    public static function all(): array
    {
        $db = DB::getInstance();
        return $db->fetchAll("SELECT * FROM achievement_categories ORDER BY name");
    }
}