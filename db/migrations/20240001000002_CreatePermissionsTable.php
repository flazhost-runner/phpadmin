<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePermissionsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('permissions', [
            'id'          => false,
            'primary_key' => ['id'],
            'collation'   => 'utf8mb4_unicode_ci',
        ]);

        $table
            ->addColumn('id',         'string',  ['limit' => 36,  'null' => false])
            // name is NOT unique — same route name can appear for different guard_names
            ->addColumn('name',       'string',  ['limit' => 255, 'null' => false])
            ->addColumn('guard_name', 'string',  ['limit' => 20,  'null' => false, 'default' => 'web'])
            ->addColumn('method',     'string',  ['limit' => 255, 'null' => true,  'default' => null])
            ->addColumn('status',     'string',  ['limit' => 20,  'null' => false, 'default' => 'Active'])
            ->addColumn('desc',       'string',  ['limit' => 255, 'null' => true,  'default' => null])
            ->addColumn('created_by', 'string',  ['limit' => 36,  'null' => true,  'default' => null])
            ->addColumn('updated_by', 'string',  ['limit' => 36,  'null' => true,  'default' => null])
            ->addColumn('created_at', 'timestamp', ['null' => true, 'default' => null])
            ->addColumn('updated_at', 'timestamp', ['null' => true, 'default' => null])
            ->addIndex('name',       ['unique' => false, 'name' => 'idx_permissions_name'])
            ->addIndex('guard_name', ['unique' => false, 'name' => 'idx_permissions_guard_name'])
            ->create();
    }
}
