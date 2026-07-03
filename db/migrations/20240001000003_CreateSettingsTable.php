<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSettingsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('settings', [
            'id'          => false,
            'primary_key' => ['id'],
            'collation'   => 'utf8mb4_unicode_ci',
        ]);

        $table
            ->addColumn('id',           'string',  ['limit' => 36,  'null' => false])
            ->addColumn('initial',      'string',  ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('name',         'string',  ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('description',  'text',    ['null' => true, 'default' => null])
            ->addColumn('icon',         'string',  ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('logo',         'string',  ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('login_image',  'string',  ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('phone',        'string',  ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('address',      'string',  ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('email',        'string',  ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('copyright',    'string',  ['limit' => 255, 'null' => true, 'default' => null])
            ->addColumn('theme',        'string',  ['limit' => 20,  'null' => false, 'default' => 'Blue'])
            ->addColumn('fe_template',  'string',  ['limit' => 80,  'null' => false, 'default' => 'agency-consulting-002-creative-agency'])
            ->addColumn('created_by',   'string',  ['limit' => 36,  'null' => true, 'default' => null])
            ->addColumn('updated_by',   'string',  ['limit' => 36,  'null' => true, 'default' => null])
            ->addColumn('created_at',   'timestamp', ['null' => true, 'default' => null])
            ->addColumn('updated_at',   'timestamp', ['null' => true, 'default' => null])
            ->create();
    }
}
