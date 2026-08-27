<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Класс-синглтон для работы с базой данных через PDO.
 * Использует подготовленные запросы для защиты от SQL-инъекций.
 */
class DB
{
    private static ?DB $instance = null;
    private PDO $pdo;

    /**
     * Приватный конструктор, создаёт подключение к БД.
     *
     * @param string $host
     * @param string $dbname
     * @param string $user
     * @param string $pass
     * @param string $charset = 'utf8mb4'
     */
    private function __construct(string $host, string $dbname, string $user, string $pass, string $charset = 'utf8mb4')
    {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Возвращает единственный экземпляр DB.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self(
                Config::get('DB_HOST'),
                Config::get('DB_NAME'),
                Config::get('DB_USER'),
                Config::get('DB_PASS')
            );
        }
        return self::$instance;
    }

    /**
     * Выполняет подготовленный запрос и возвращает PDOStatement.
     *
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Выполняет запрос и возвращает первую строку как ассоциативный массив.
     *
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch() ?: null;
    }

    /**
     * Выполняет запрос и возвращает все строки.
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Удобная обёртка для INSERT.
     *
     * @param string $table
     * @param array $data Ассоциативный массив [поле => значение]
     * @return int ID вставленной записи
     */
    public function insert(string $table, array $data): int
    {
        $fields = array_keys($data);
        $placeholders = array_map(fn($f) => ":$f", $fields);
        $sql = "INSERT INTO `$table` (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->query($sql, $data);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Удобная обёртка для UPDATE.
     *
     * @param string $table
     * @param array $data Ассоциативный массив [поле => значение]
     * @param string $where Условие (например, "id = :id")
     * @param array $whereParams Параметры для условия
     * @return int Количество затронутых строк
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $sets = [];
        $params = [];
        foreach ($data as $field => $value) {
            $sets[] = "`$field` = :$field";
            $params[":$field"] = $value;
        }
        $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE $where";
        // Добавляем параметры WHERE (они уже должны быть с префиксом :)
        foreach ($whereParams as $key => $value) {
            $params[$key] = $value;
        }
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Получить PDO-объект для прямого использования (редко).
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}