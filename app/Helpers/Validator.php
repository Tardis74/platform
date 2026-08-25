<?php

namespace App\Helpers;

class Validator
{
    /**
     * Очищает и проверяет СНИЛС.
     * @param string $snils Исходный СНИЛС (может содержать дефисы и пробелы)
     * @return string Очищенный СНИЛС (11 цифр)
     * @throws \InvalidArgumentException Если СНИЛС невалидный
     */
    public static function validateSnils(string $snils): string
    {
        // Удаляем все нецифровые символы
        $cleaned = preg_replace('/\D/', '', $snils);
        
        if (strlen($cleaned) !== 11) {
            throw new \InvalidArgumentException('СНИЛС должен содержать 11 цифр.');
        }
        
        // Здесь можно добавить проверку контрольной суммы СНИЛС, если требуется
        
        return $cleaned;
    }
}