<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Add missing standard columns:
 *   - roles.guard_name  (VARCHAR 50, NOT NULL DEFAULT 'web')
 *   - settings.favicon  (VARCHAR 255, nullable)
 */
final class AddMissingColumns extends AbstractMigration
{
    public function change(): void
    {
        // roles.guard_name
        $roles = $this->table('roles');
        if (!$roles->hasColumn('guard_name')) {
            $roles->addColumn('guard_name', 'string', [
                'limit'   => 50,
                'null'    => false,
                'default' => 'web',
                'after'   => 'name',
            ])->update();
        }

        // settings.favicon
        $settings = $this->table('settings');
        if (!$settings->hasColumn('favicon')) {
            $settings->addColumn('favicon', 'string', [
                'limit' => 255,
                'null'  => true,
                'default' => null,
                'after' => 'logo',
            ])->update();
        }
    }
}
