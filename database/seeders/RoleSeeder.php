<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application;
use App\Models\Permission;
use App\Models\Role;

final class RoleSeeder
{
    public function __construct(private readonly Application $app) {}
    public function run(): void
    {
        $pdo = $this->app->db();
        $roles = ['superadmin' => 'Pełny dostęp do systemu', 'admin' => 'Zarządzanie produktami, licencjami i aktualizacjami', 'support' => 'Tylko odczyt danych operacyjnych'];
        $permissions = ['products.manage', 'licenses.manage', 'downloads.view', 'logs.view', 'users.manage', 'settings.manage'];
        foreach ($roles as $slug => $description) {
            $statement = $pdo->prepare('SELECT id FROM roles WHERE slug = :slug LIMIT 1'); $statement->execute(['slug' => $slug]);
            if (!$statement->fetchColumn()) Role::create($pdo, ['name' => ucfirst($slug), 'slug' => $slug, 'description' => $description, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'deleted_at' => null]);
        }
        foreach ($permissions as $slug) {
            $statement = $pdo->prepare('SELECT id FROM permissions WHERE slug = :slug LIMIT 1'); $statement->execute(['slug' => $slug]);
            if (!$statement->fetchColumn()) Permission::create($pdo, ['name' => $slug, 'slug' => $slug, 'description' => $slug, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), 'deleted_at' => null]);
        }
        $roleMap = []; foreach ($pdo->query('SELECT id, slug FROM roles')->fetchAll() ?: [] as $role) $roleMap[$role['slug']] = (int) $role['id'];
        $permissionMap = [];
        foreach ($pdo->query('SELECT id, slug FROM permissions')->fetchAll() ?: [] as $permission) {
            $permissionMap[$permission['slug']] = (int) $permission['id'];
        }
        foreach (['superadmin', 'admin', 'support'] as $slug) {
            $pdo->prepare('DELETE FROM permission_role WHERE role_id = :role_id')->execute(['role_id' => $roleMap[$slug]]);
            $assign = $slug === 'support'
                ? array_values(array_intersect_key($permissionMap, array_flip(['downloads.view', 'logs.view'])))
                : array_values($permissionMap);
            foreach ($assign as $permissionId) $pdo->prepare('INSERT INTO permission_role (permission_id, role_id) VALUES (:permission_id, :role_id)')->execute(['permission_id' => $permissionId, 'role_id' => $roleMap[$slug]]);
        }
    }
}
