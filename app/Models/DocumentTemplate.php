<?php
namespace App\Models;

use App\Core\DB;

class DocumentTemplate
{
    public static function find(int $id): ?array
    {
        $db = DB::getInstance();
        return $db->fetch("SELECT * FROM document_templates WHERE id = :id", ['id' => $id]);
    }

    public static function all(): array
    {
        $db = DB::getInstance();
        return $db->fetchAll("SELECT * FROM document_templates ORDER BY name");
    }

    public static function create(array $data): int
    {
        $db = DB::getInstance();
        return $db->insert('document_templates', $data);
    }

    public static function update(int $id, array $data): bool
    {
        $db = DB::getInstance();
        $sets = [];
        $params = ['id' => $id];
        foreach ($data as $field => $value) {
            $sets[] = "`$field` = :$field";
            $params[$field] = $value;
        }
        $sql = "UPDATE document_templates SET " . implode(', ', $sets) . " WHERE id = :id";
        $stmt = $db->query($sql, $params);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $db = DB::getInstance();
        $stmt = $db->query("DELETE FROM document_templates WHERE id = :id", ['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Заменить плейсхолдеры в содержимом шаблона.
     * @param array $data Ассоциативный массив ['STUDENT_FIO' => ..., 'PARENT_FIO' => ..., 'CLASS' => ..., 'DATE' => ...]
     */
    public static function renderContent(string $content, array $data): string
    {
        $search = array_map(fn($k) => '{' . $k . '}', array_keys($data));
        $replace = array_values($data);
        return str_replace($search, $replace, $content);
    }
}