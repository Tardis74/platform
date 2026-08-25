<?php
namespace App\Controllers;

use App\Core\DB;
use App\Core\ApiResponse;
use App\Models\Document;
use RuntimeException;

class SystemController extends BaseController
{
    /**
     * Проверка истекших документов (вызывается по крону или администратором).
     */
    public function checkExpiredDocuments(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->requireRole($token, 'admin');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $count = Document::expireApprovedDocuments();

        $log = date('Y-m-d H:i:s') . " [system] Проверка сроков: истекло $count документов\n";
        file_put_contents(__DIR__ . '/../../storage/logs/documents.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'expired_count' => $count,
            'message' => "Обновлено $count документов со статусом expired"
        ]);
    }

    /**
     * Проверка просроченных выходов (вызывается по крону)
     */
    public function checkOverdueLeaves(DB $db, array $payload): ApiResponse
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return ApiResponse::error('Token required.', 401);
        }
        try {
            $this->requireRole($token, 'admin');
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 403);
        }

        $count = LeaveRequest::markOverdue();

        $log = date('Y-m-d H:i:s') . " [system] Checked overdue leaves: $count marked as overdue\n";
        file_put_contents(__DIR__ . '/../../storage/logs/kpp.log', $log, FILE_APPEND);

        return ApiResponse::success([
            'overdue_count' => $count,
            'message' => "Обновлено $count заявлений со статусом overdue"
        ]);
    }
}