<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\User;
use PDO;

final class AuthManager
{
    private ?array $user = null;
    public function __construct(private readonly PDO $pdo) {}
    public function attempt(string $email, string $password): bool
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['email' => mb_strtolower($email)]);
        $user = $statement->fetch();
        if (!$user || !password_verify($password, (string) $user['password_hash']) || !(bool) $user['is_active']) return false;
        $_SESSION['user_id'] = (int) $user['id'];
        $this->user = $user;
        return true;
    }
    public function user(): ?array
    {
        if ($this->user !== null) return $this->user;
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) return null;
        return $this->user = User::find($this->pdo, (int) $userId)?->attributes;
    }
    public function logout(): void { unset($_SESSION['user_id']); $this->user = null; }
    public function roles(): array
    {
        $user = $this->user();
        if (!$user) return [];
        $statement = $this->pdo->prepare('SELECT r.slug FROM roles r INNER JOIN role_user ru ON ru.role_id = r.id WHERE ru.user_id = :user_id');
        $statement->execute(['user_id' => $user['id']]);
        return $statement->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
    public function hasRole(array $roles): bool { return array_intersect($roles, $this->roles()) !== []; }
}
