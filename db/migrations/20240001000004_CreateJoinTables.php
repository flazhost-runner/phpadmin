<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateJoinTables extends AbstractMigration
{
    public function change(): void
    {
        // ─── users_roles ──────────────────────────────────────────────────────
        $usersRoles = $this->table('users_roles', [
            'id'          => false,
            'primary_key' => ['user_id', 'role_id'],
        ]);

        $usersRoles
            ->addColumn('user_id', 'string', ['limit' => 36, 'null' => false])
            ->addColumn('role_id', 'string', ['limit' => 36, 'null' => false])
            ->addIndex(['user_id', 'role_id'], ['unique' => true, 'name' => 'idx_users_roles_pk'])
            ->create();

        // ─── roles_permissions ────────────────────────────────────────────────
        $rolesPermissions = $this->table('roles_permissions', [
            'id'          => false,
            'primary_key' => ['role_id', 'permission_id'],
        ]);

        $rolesPermissions
            ->addColumn('role_id',       'string', ['limit' => 36, 'null' => false])
            ->addColumn('permission_id', 'string', ['limit' => 36, 'null' => false])
            ->addIndex(['role_id', 'permission_id'], ['unique' => true, 'name' => 'idx_roles_permissions_pk'])
            ->create();
    }
}
