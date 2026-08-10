<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

abstract class BaseModel
{
    protected static string $table;
    public function __construct(public array $attributes = []) {}
    public static function table(): string { return static::$table; }
    public static function find(PDO $pdo, int $id): ?static
    {
        $statement = $pdo->prepare('SELECT * FROM ' . static::table() . ' WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return $row ? new static($row) : null;
    }
    public static function all(PDO $pdo, string $where = '1=1', array $params = [], string $orderBy = 'id DESC'): array
    {
        $statement = $pdo->prepare('SELECT * FROM ' . static::table() . ' WHERE ' . $where . ' ORDER BY ' . $orderBy);
        $statement->execute($params);
        return array_map(static fn (array $row): static => new static($row), $statement->fetchAll() ?: []);
    }
    public static function create(PDO $pdo, array $data): static
    {
        $columns = array_keys($data);
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', static::table(), implode(', ', $columns), implode(', ', array_map(static fn (string $column): string => ':' . $column, $columns)));
        $statement = $pdo->prepare($sql);
        $statement->execute($data);
        return static::find($pdo, (int) $pdo->lastInsertId());
    }
    public static function updateById(PDO $pdo, int $id, array $data): void
    {
        $assignments = implode(', ', array_map(static fn (string $column): string => $column . ' = :' . $column, array_keys($data)));
        $data['id'] = $id;
        $statement = $pdo->prepare('UPDATE ' . static::table() . ' SET ' . $assignments . ' WHERE id = :id');
        $statement->execute($data);
    }
}
