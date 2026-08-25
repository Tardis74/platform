<?php

namespace App\Helpers;

class FileHelper
{
    private const UPLOAD_DIR = __DIR__ . '/../../storage/uploads/achievements/';

    /**
     * Сохраняет загруженный файл в защищённую директорию.
     *
     * @param array $file $_FILES['file']
     * @param int $studentId
     * @return string|null Относительный путь к файлу (например, "student_1_1234567890.pdf") или null при ошибке
     */
    public static function saveUploadedFile(array $file, int $studentId): ?string
    {
        // Проверка ошибок загрузки
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Проверка расширения
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            return null;
        }

        // Проверка размера (10 МБ)
        if ($file['size'] > 10 * 1024 * 1024) {
            return null;
        }

        // Создание директории, если нет
        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        // Генерация уникального имени
        $filename = 'student_' . $studentId . '_' . time() . '.' . $ext;
        $destination = self::UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return null;
        }

        return $filename; // относительный путь
    }

    /**
     * Возвращает полный путь к файлу по относительному пути.
     */
    public static function getFilePath(string $relativePath): string
    {
        return self::UPLOAD_DIR . $relativePath;
    }

    /**
     * Проверяет существование файла.
     */
    public static function fileExists(string $relativePath): bool
    {
        return file_exists(self::getFilePath($relativePath));
    }
}