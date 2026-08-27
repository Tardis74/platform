<?php
namespace App\Models;

use App\Core\DB;

class UserPermission
{
    public static function getPermissions(int $userId): array
    {
        $db = DB::getInstance();
        $rows = $db->fetchAll("SELECT permission FROM user_permissions WHERE user_id = ?", [$userId]);
        return array_column($rows, 'permission');
    }

    public static function setPermissions(int $userId, array $permissions): void
    {
        $db = DB::getInstance();
        $db->query("DELETE FROM user_permissions WHERE user_id = ?", [$userId]);
        foreach ($permissions as $perm) {
            $db->query("INSERT INTO user_permissions (user_id, permission) VALUES (?, ?)", [$userId, $perm]);
        }
    }

    public static function hasPermission(int $userId, string $permission): bool
    {
        $db = DB::getInstance();
        $row = $db->fetch("SELECT 1 FROM user_permissions WHERE user_id = ? AND permission = ?", [$userId, $permission]);
        return $row !== null;
    }
}