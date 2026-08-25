<?php

namespace App\Helpers;

class FileHelper
{
    private const UPLOAD_DIR = __DIR__ . '/../../storage/uploads/achievements/';
    private const DOCUMENT_UPLOAD_DIR = __DIR__ . '/../../storage/uploads/documents/';

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


    /**
     * Сохраняет загруженный файл документа.
     */
    public static function saveDocument(array $file, int $studentId): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) return null;

        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'odt'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) return null;
        if ($file['size'] > 10 * 1024 * 1024) return null;

        if (!is_dir(self::DOCUMENT_UPLOAD_DIR)) {
            mkdir(self::DOCUMENT_UPLOAD_DIR, 0755, true);
        }

        $filename = uniqid() . '_' . time() . '.' . $ext;
        $destination = self::DOCUMENT_UPLOAD_DIR . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) return null;

        return $filename;
    }

    public static function getDocumentPath(string $relativePath): string
    {
        return self::DOCUMENT_UPLOAD_DIR . $relativePath;
    }
}