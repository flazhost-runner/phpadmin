<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * SeedInitialData — runs InitialSeed as part of `phinx migrate`, so a plain
 * migrate produces a fully usable database (default settings, Administrator
 * role, admin@admin.com / 12345678 user + role assignment) without requiring
 * a separate `phinx seed:run`.
 *
 * InitialSeed is fully idempotent (every insert is guarded by an existence
 * check), so this migration is safe when:
 *   - the database was already seeded earlier via `phinx seed:run`, and
 *   - `phinx seed:run` is executed again after this migration
 *     (the Docker entrypoint still calls it separately).
 */
final class SeedInitialData extends AbstractMigration
{
    public function up(): void
    {
        require_once __DIR__ . '/../seeds/InitialSeed.php';

        $seed = new InitialSeed();
        $seed->setEnvironment($this->getEnvironment());
        $seed->setAdapter($this->getAdapter());

        $input = $this->getInput();
        if ($input !== null) {
            $seed->setInput($input);
        }

        $output = $this->getOutput();
        if ($output !== null) {
            $seed->setOutput($output);
        }

        $seed->run();
    }

    public function down(): void
    {
        // Data seed — intentionally a no-op on rollback. Rolling back the
        // schema must never delete the admin account from a live database.
    }
}
