<?php

declare(strict_types=1);

namespace PHPAdmin\Core;

use Predis\Client as PredisClient;

/**
 * PHP session handler backed by Redis via Predis.
 */
class RedisSessionHandler implements \SessionHandlerInterface
{
    private const KEY_PREFIX = 'session:';

    public function __construct(
        private readonly PredisClient $redis,
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
        $value = $this->redis->get(self::KEY_PREFIX . $id);
        return $value === null ? '' : (string)$value;
    }

    public function write(string $id, string $data): bool
    {
        $result = $this->redis->setex(self::KEY_PREFIX . $id, $this->ttl, $data);
        return $result !== null;
    }

    public function destroy(string $id): bool
    {
        $this->redis->del([self::KEY_PREFIX . $id]);
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        // Redis handles TTL-based expiry natively; nothing to do here.
        return 0;
    }
}
