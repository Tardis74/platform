<?php

namespace App\Models;

use App\Core\DB;

class KppLog
{
    public static function log(int $requestId, string $action, int $userId, ?string $ip = null): int
    {
        $db = DB::getInstance();
        return $db->insert('kpp_logs', [
            'request_id' => $requestId,
            'action'     => $action,
            'user_id'    => $userId,
            'ip_address' => $ip ?? $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}