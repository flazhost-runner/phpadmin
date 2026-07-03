<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

/**
 * InitialSeed — idempotent bootstrap data.
 *
 * Safe to run multiple times: each INSERT is guarded by an existence check.
 *
 * Creates:
 *   1. Default settings row  (name = PHPAdmin, theme = Blue)
 *   2. Administrator role
 *   3. admin@admin.com user  (password = '12345678')
 *   4. Assigns Administrator role to the admin user
 */
final class InitialSeed extends AbstractSeed
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // ─── 1. Settings ──────────────────────────────────────────────────────
        $existingSetting = $this->fetchRow("SELECT id FROM settings WHERE name = 'PHPAdmin' LIMIT 1");

        if ($existingSetting === false || $existingSetting === null) {
            $settingId = $this->generateUuid();
            $this->table('settings')->insert([
                [
                    'id'          => $settingId,
                    'initial'     => 'PA',
                    'name'        => 'PHPAdmin',
                    'description' => 'PHP Admin Panel',
                    'theme'       => 'Blue',
                    'fe_template' => 'agency-consulting-002-creative-agency',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
            ])->saveData();
        }

        // ─── 2. Administrator role ─────────────────────────────────────────────
        $existingRole = $this->fetchRow("SELECT id FROM roles WHERE name = 'Administrator' LIMIT 1");

        $roleId = null;
        if ($existingRole === false || $existingRole === null) {
            $roleId = $this->generateUuid();
            $this->table('roles')->insert([
                [
                    'id'         => $roleId,
                    'name'       => 'Administrator',
                    'guard_name' => 'web',
                    'status'     => 'Active',
                    'desc'       => '',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ])->saveData();
        } else {
            $roleId = (string)$existingRole['id'];
        }

        // ─── 3. Admin user ────────────────────────────────────────────────────
        $existingUser = $this->fetchRow("SELECT id FROM users WHERE email = 'admin@admin.com' LIMIT 1");

        $userId = null;
        if ($existingUser === false || $existingUser === null) {
            $userId       = $this->generateUuid();
            $passwordHash = password_hash('12345678', PASSWORD_BCRYPT);

            $this->table('users')->insert([
                [
                    'id'                => $userId,
                    'code'              => '0000000001',
                    'name'              => 'Administrator',
                    'phone'             => '12345678910',
                    'email'             => 'admin@admin.com',
                    'email_verified_at' => $now,
                    'password'          => $passwordHash,
                    'status'            => 'Active',
                    'blocked'           => 0,
                    'blocked_reason'    => '',
                    'timezone'          => 'Asia/Jakarta',
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ],
            ])->saveData();
        } else {
            $userId = (string)$existingUser['id'];
        }

        // ─── 4. Assign Administrator role to admin user ───────────────────────
        if ($userId !== null && $roleId !== null) {
            $existingAssignment = $this->fetchRow(
                "SELECT user_id FROM users_roles WHERE user_id = '{$userId}' AND role_id = '{$roleId}' LIMIT 1"
            );

            if ($existingAssignment === false || $existingAssignment === null) {
                $this->table('users_roles')->insert([
                    [
                        'user_id' => $userId,
                        'role_id' => $roleId,
                    ],
                ])->saveData();
            }
        }
    }

    /**
     * Generate a RFC4122 v4 UUID (no vendor dependency needed in migrations).
     */
    private function generateUuid(): string
    {
        $bytes    = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return sprintf(
            '%s-%s-%s-%s-%s',
            bin2hex(substr($bytes, 0, 4)),
            bin2hex(substr($bytes, 4, 2)),
            bin2hex(substr($bytes, 6, 2)),
            bin2hex(substr($bytes, 8, 2)),
            bin2hex(substr($bytes, 10, 6))
        );
    }
}
