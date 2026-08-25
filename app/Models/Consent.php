<?php
namespace App\Models;

use App\Core\DB;

class Consent
{
    public static function create(array $data): int
    {
        $db = DB::getInstance();
        return $db->insert('consents', $data);
    }

    public static function find(int $id): ?array
    {
        $db = DB::getInstance();
        return $db->fetch("SELECT * FROM consents WHERE id = :id", ['id' => $id]);
    }

    public static function getActiveByStudentAndType(int $studentId, string $type): ?array
    {
        $db = DB::getInstance();
        return $db->fetch(
            "SELECT * FROM consents WHERE student_id = :student_id AND type = :type AND status = 'active' ORDER BY given_at DESC LIMIT 1",
            ['student_id' => $studentId, 'type' => $type]
        );
    }

    public static function getByStudent(int $studentId, ?string $type = null): array
    {
        $db = DB::getInstance();
        $sql = "SELECT * FROM consents WHERE student_id = :student_id";
        $params = ['student_id' => $studentId];
        if ($type) {
            $sql .= " AND type = :type";
            $params['type'] = $type;
        }
        $sql .= " ORDER BY given_at DESC";
        return $db->fetchAll($sql, $params);
    }

    public static function revoke(int $id, ?string $ip = null): bool
    {
        $db = DB::getInstance();
        $sql = "UPDATE consents SET status = 'revoked', revoked_at = NOW(), ip_address = COALESCE(:ip, ip_address) WHERE id = :id";
        $stmt = $db->query($sql, ['id' => $id, 'ip' => $ip]);
        return $stmt->rowCount() > 0;
    }

    public static function deactivatePrevious(int $studentId, string $type): bool
    {
        $db = DB::getInstance();
        $sql = "UPDATE consents SET status = 'revoked', revoked_at = NOW() 
                WHERE student_id = :student_id AND type = :type AND status = 'active'";
        $stmt = $db->query($sql, ['student_id' => $studentId, 'type' => $type]);
        return $stmt->rowCount() > 0;
    }
}