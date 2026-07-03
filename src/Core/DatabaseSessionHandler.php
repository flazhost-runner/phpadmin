<?php

declare(strict_types=1);

namespace PHPAdmin\Core;

/**
 * PHP session handler backed by a PDO database table `sessions`.
 * Schema: id VARCHAR(128) PK, data TEXT, expires_at DATETIME.
 *
 * Persists sessions across container restarts — unlike the Redis handler
 * which loses sessions when the Redis process is restarted.
 */
class DatabaseSessionHandler implements \SessionHandlerInterface
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly int $ttl = 7200
    ) {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT data FROM sessions WHERE id = :id AND expires_at > :now'
        );
        $stmt->execute([':id' => $id, ':now' => date('Y-m-d H:i:s')]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row !== false ? (string)$row['data'] : '';
    }

    public function write(string $id, string $data): bool
    {
        $expiresAt = date('Y-m-d H:i:s', time() + $this->ttl);
        $stmt = $this->pdo->prepare(
            'REPLACE INTO sessions (id, data, expires_at) VALUES (:id, :data, :expires_at)'
        );
        return $stmt->execute([':id' => $id, ':data' => $data, ':expires_at' => $expiresAt]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE expires_at < :now');
        $stmt->execute([':now' => date('Y-m-d H:i:s')]);
        return $stmt->rowCount();
    }
}
