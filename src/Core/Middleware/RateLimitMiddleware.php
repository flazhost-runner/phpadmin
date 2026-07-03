<?php

declare(strict_types=1);

namespace PHPAdmin\Core\Middleware;

/**
 * In-memory / APCu sliding-window rate limiter.
 *
 * Falls back to a simple in-process array when APCu is unavailable
 * (e.g. CLI / test environments) — not suitable for multi-process prod without APCu.
 *
 * Usage:
 *   RateLimitMiddleware::check('auth:' . $ip, 10, 900);   // authLimiter: 10/15 min
 *   RateLimitMiddleware::check('otp:' . $ip,  5, 900);   // otpLimiter:  5/15 min
 */
class RateLimitMiddleware
{
    /** @var array<string, array{count:int, reset:int}> */
    private static array $store = [];

    /**
     * Check rate limit. Returns false when limit exceeded.
     * Sends 429 JSON response and exits when limit is exceeded.
     *
     * @param string $key       Unique key (e.g. "auth:127.0.0.1")
     * @param int    $maxReq    Max requests allowed in window
     * @param int    $windowSec Window size in seconds
     */
    public static function check(string $key, int $maxReq, int $windowSec): void
    {
        if (self::isExceeded($key, $maxReq, $windowSec)) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => false,
                'message' => 'Too many requests. Please try again later.',
                'data'    => null,
            ]);
            exit;
        }
    }

    /**
     * Same as check() but redirects to referrer with flash instead of JSON (for web routes).
     */
    public static function checkWeb(string $key, int $maxReq, int $windowSec): void
    {
        if (self::isExceeded($key, $maxReq, $windowSec)) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['flash'] = ['error' => 'Too many requests. Please try again later.'];
            }
            $ref = (string)($_SERVER['HTTP_REFERER'] ?? '/');
            header('Location: ' . $ref);
            exit;
        }
    }

    private static function isExceeded(string $key, int $maxReq, int $windowSec): bool
    {
        $cacheKey = 'rl_' . $key;
        $now      = time();

        if (function_exists('apcu_fetch') && ini_get('apc.enabled')) {
            $data = apcu_fetch($cacheKey);
            if ($data === false || $now > (int)$data['reset']) {
                $data = ['count' => 0, 'reset' => $now + $windowSec];
            }
            if ((int)$data['count'] >= $maxReq) {
                return true;
            }
            $data['count']++;
            apcu_store($cacheKey, $data, $windowSec);
            return false;
        }

        // Fallback: in-process array (single worker only)
        if (!isset(self::$store[$cacheKey]) || $now > self::$store[$cacheKey]['reset']) {
            self::$store[$cacheKey] = ['count' => 0, 'reset' => $now + $windowSec];
        }
        if (self::$store[$cacheKey]['count'] >= $maxReq) {
            return true;
        }
        self::$store[$cacheKey]['count']++;
        return false;
    }
}
