<?php

declare(strict_types=1);

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private ?PDOStatement $stmt = null;

    private function __construct()
    {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function query(string $sql): self
    {
        $this->stmt = $this->pdo->prepare($sql);
        return $this;
    }

    public function bind(string|int $param, mixed $value, int $type = null): self
    {
        if ($this->stmt === null) {
            throw new RuntimeException('No prepared statement available');
        }

        if ($type === null) {
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
        }

        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }

    public function execute(?array $params = null): bool
    {
        if ($this->stmt === null) {
            throw new RuntimeException('No prepared statement available');
        }

        if ($params === null) {
            return $this->stmt->execute();
        }

        return $this->stmt->execute($params);
    }

    public function resultSet(): array
    {
        if ($this->stmt === null) {
            return [];
        }

        return $this->stmt->fetchAll();
    }

    public function single(): array|false
    {
        if ($this->stmt === null) {
            return false;
        }

        return $this->stmt->fetch();
    }

    public function rowCount(): int
    {
        return $this->stmt?->rowCount() ?? 0;
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}
