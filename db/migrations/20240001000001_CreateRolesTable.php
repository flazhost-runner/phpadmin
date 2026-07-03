<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRolesTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('roles', [
            'id'          => false,
            'primary_key' => ['id'],
            'collation'   => 'utf8mb4_unicode_ci',
        ]);

        $table
            ->addColumn('id',         'string',  ['limit' => 36,  'null' => false])
            ->addColumn('name',       'string',  ['limit' => 255, 'null' => false])
            ->addColumn('status',     'string',  ['limit' => 20,  'null' => false, 'default' => 'Active'])
            ->addColumn('desc',       'string',  ['limit' => 255, 'null' => true,  'default' => null])
            ->addColumn('created_by', 'string',  ['limit' => 36,  'null' => true,  'default' => null])
            ->addColumn('updated_by', 'string',  ['limit' => 36,  'null' => true,  'default' => null])
            ->addColumn('created_at', 'timestamp', ['null' => true, 'default' => null])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => null])
            ->addIndex('name', ['unique' => true, 'name' => 'idx_roles_name'])
            ->create();
    }
}
