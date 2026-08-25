<?php

namespace App\Helpers;

class Validator
{
    /**
     * Очищает и проверяет СНИЛС на валидность (11 цифр).
     * Возвращает очищенную строку из 11 цифр.
     * @throws \InvalidArgumentException
     */
    public static function validateSnils(string $snils): string
    {
        $snils = preg_replace('/[^0-9]/', '', $snils);
        if (strlen($snils) !== 11) {
            throw new \InvalidArgumentException('СНИЛС должен содержать ровно 11 цифр.');
        }
        return $snils;
    }
}