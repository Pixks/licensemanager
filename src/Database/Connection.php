<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;

final class Connection
{
    public static function make(array $config): PDO
    {
        try {
            if (($config['driver'] ?? 'mysql') === 'sqlite') {
                $pdo = new PDO('sqlite:' . $config['database']);
            } else {
                $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $config['host'], $config['port'], $config['database'], $config['charset'] ?? 'utf8mb4');
                $pdo = new PDO($dsn, (string) $config['username'], (string) $config['password']);
            }
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            if (($config['driver'] ?? 'mysql') === 'sqlite') {
                $pdo->exec('PRAGMA foreign_keys = ON');
            }
            return $pdo;
        } catch (PDOException $exception) {
            throw new RuntimeException('Database connection failed: ' . $exception->getMessage(), 0, $exception);
        }
    }
}
