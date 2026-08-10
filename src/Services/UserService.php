<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use PDO;

final class UserService
{
    public function __construct(private readonly PDO $pdo) {}
    public function all(): array
    {
        return $this->pdo->query('SELECT u.*, GROUP_CONCAT(r.name, ", ") AS roles FROM users u LEFT JOIN role_user ru ON ru.user_id = u.id LEFT JOIN roles r ON r.id = ru.role_id WHERE u.deleted_at IS NULL GROUP BY u.id ORDER BY u.id DESC')->fetchAll() ?: [];
    }
    public function createOrUpdateAdmin(string $name, string $email, string $password, string $roleSlug = 'superadmin'): void
    {
        $email = mb_strtolower(trim($email));
        $statement = $this->pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $userId = $statement->fetchColumn();
        $payload = ['name' => $name, 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'is_active' => 1, 'updated_at' => date('Y-m-d H:i:s')];
        if ($userId) {
            User::updateById($this->pdo, (int) $userId, $payload);
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s'); $payload['deleted_at'] = null; $userId = User::create($this->pdo, $payload)->attributes['id'];
        }
        $roleStatement = $this->pdo->prepare('SELECT id FROM roles WHERE slug = :slug LIMIT 1');
        $roleStatement->execute(['slug' => $roleSlug]);
        $roleId = $roleStatement->fetchColumn();
        if ($roleId) {
            $this->pdo->prepare('DELETE FROM role_user WHERE user_id = :user_id')->execute(['user_id' => $userId]);
            $this->pdo->prepare('INSERT INTO role_user (role_id, user_id) VALUES (:role_id, :user_id)')->execute(['role_id' => $roleId, 'user_id' => $userId]);
        }
    }
}
