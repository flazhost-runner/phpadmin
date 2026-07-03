<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUsersTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users', [
            'id'          => false,
            'primary_key' => ['id'],
            'collation'   => 'utf8mb4_unicode_ci',
        ]);

        $table
            ->addColumn('id',                    'string',     ['limit' => 36,  'null' => false])
            ->addColumn('code',                  'string',     ['limit' => 20,  'null' => false])
            ->addColumn('name',                  'string',     ['limit' => 50,  'null' => false])
            ->addColumn('phone',                 'string',     ['limit' => 15,  'null' => true,  'default' => null])
            ->addColumn('email',                 'string',     ['limit' => 255, 'null' => false])
            ->addColumn('email_verified_at',     'timestamp',  ['null' => true,  'default' => null])
            ->addColumn('password',              'string',     ['limit' => 255, 'null' => false])
            ->addColumn('password_otp',          'string',     ['limit' => 255, 'null' => true,  'default' => null])
            ->addColumn('password_otp_expires',  'biginteger', ['null' => true,  'default' => null])
            ->addColumn('status',                'string',     ['limit' => 20,  'null' => false, 'default' => 'Active'])
            ->addColumn('picture',               'string',     ['limit' => 255, 'null' => true,  'default' => null])
            ->addColumn('blocked',               'boolean',    ['null' => false, 'default' => false])
            ->addColumn('blocked_reason',        'string',     ['limit' => 255, 'null' => true,  'default' => null])
            ->addColumn('timezone',              'string',     ['limit' => 255, 'null' => false, 'default' => 'UTC'])
            ->addColumn('created_by',            'string',     ['limit' => 36,  'null' => true,  'default' => null])
            ->addColumn('updated_by',            'string',     ['limit' => 36,  'null' => true,  'default' => null])
            ->addColumn('created_at',            'timestamp',  ['null' => true,  'default' => null])
            ->addColumn('updated_at',            'timestamp',  ['null' => true,  'default' => null])
            ->addIndex('code',  ['unique' => true,  'name' => 'idx_users_code'])
            ->addIndex('email', ['unique' => true,  'name' => 'idx_users_email'])
            ->create();
    }
}
