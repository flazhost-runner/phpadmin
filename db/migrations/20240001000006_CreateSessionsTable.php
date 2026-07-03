<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSessionsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sessions', [
            'id'          => false,
            'primary_key' => ['id'],
        ]);

        $table
            ->addColumn('id',         'string',   ['limit' => 128, 'null' => false])
            ->addColumn('data',       'text',     ['null' => false])
            ->addColumn('expires_at', 'datetime', ['null' => false])
            ->addIndex('expires_at', ['name' => 'idx_sessions_expires_at'])
            ->create();
    }
}
