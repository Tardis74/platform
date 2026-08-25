<?php

namespace App\Helpers;

use App\Core\Config;

class QRHelper
{
    /**
     * Генерирует строку для QR-кода: request_id|hmac
     */
    public static function generate(int $requestId): string
    {
        $secret = Config::get('QR_SECRET', 'default_qr_secret');
        $hmac = hash_hmac('sha256', (string)$requestId, $secret);
        return $requestId . '|' . $hmac;
    }

    /**
     * Проверяет подпись и возвращает request_id, если подпись верна, иначе null
     */
    public static function verify(string $qrData): ?int
    {
        $parts = explode('|', $qrData);
        if (count($parts) !== 2) {
            return null;
        }
        $requestId = (int)$parts[0];
        $hmac = $parts[1];
        $secret = Config::get('QR_SECRET', 'default_qr_secret');
        $expected = hash_hmac('sha256', (string)$requestId, $secret);
        return hash_equals($expected, $hmac) ? $requestId : null;
    }
}