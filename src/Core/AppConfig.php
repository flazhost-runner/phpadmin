<?php

declare(strict_types=1);

namespace PHPAdmin\Core;

/**
 * Immutable application configuration sourced from environment variables.
 */
readonly class AppConfig
{
    public string $appName;
    public string $appEnv;
    public string $appMode;
    public string $appUrl;
    public string $appRoot;

    // Database
    public string $dbDriver;
    public string $dbHost;
    public int $dbPort;
    public string $dbDatabase;
    public string $dbUsername;
    public string $dbPassword;
    public string $dbCharset;

    // Redis
    public string $redisHost;
    public int $redisPort;
    public string $redisPassword;
    public bool $redisTls;

    // Session & Auth
    public string $sessionDriver;
    public string $sessionSecret;
    public int $sessionTtlHours;
    public string $jwtSecret;
    public string $jwtExpiresIn;
    public int $bcryptRounds;
    public int $otpExpiryMinutes;
    public int $defaultPageSize;

    // Mail
    public string $mailHost;
    public int $mailPort;
    public bool $mailSecure;
    public string $mailUsername;
    public string $mailPassword;
    public string $mailFromAddress;
    public string $mailFromName;

    // Storage (STORAGE_* — local / OSS / S3-compatible)
    public string $storageDriver;
    public string $storageBasePath;
    public string $storageAccessKeyId;
    public string $storageSecretAccessKey;
    public string $storageEndpoint;
    public string $storageBucket;
    public string $storageRegion;
    public bool $storageSsl;
    public bool $storagePathStyle;
    public string $storageBaseUrl;

    // Timezone
    public string $tz;

    public function __construct()
    {
        $this->appName    = $_ENV['APP_NAME']    ?? 'PHPAdmin';
        $this->appEnv     = $_ENV['APP_ENV']     ?? 'development';
        $this->appMode    = $_ENV['APP_MODE']    ?? 'full';
        $this->appUrl     = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8000', '/');
        $this->appRoot    = defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 2);

        $this->dbDriver   = $_ENV['DB_DRIVER']   ?? 'mysql';
        $this->dbHost     = $_ENV['DB_HOST']     ?? '127.0.0.1';
        $this->dbPort     = (int)($_ENV['DB_PORT'] ?? 3306);
        $this->dbDatabase = $_ENV['DB_DATABASE'] ?? 'phpadmin';
        $this->dbUsername = $_ENV['DB_USERNAME'] ?? 'root';
        $this->dbPassword = $_ENV['DB_PASSWORD'] ?? '';
        $this->dbCharset  = $_ENV['DB_CHARSET']  ?? 'utf8mb4';

        // REDIS_URL=redis://[:password@]host[:port] takes priority over individual vars.
        // A rediss:// (or tls://) scheme enables TLS — required by managed Redis behind
        // an SNI-routing proxy. REDIS_TLS=true forces TLS when using discrete vars.
        $redisUrl = $_ENV['REDIS_URL'] ?? '';
        if ($redisUrl !== '') {
            $parsed              = parse_url($redisUrl) ?: [];
            $scheme              = strtolower((string)($parsed['scheme'] ?? 'redis'));
            $this->redisHost     = (string)($parsed['host'] ?? '127.0.0.1');
            $this->redisPort     = (int)($parsed['port'] ?? 6379);
            $this->redisPassword = isset($parsed['pass']) ? urldecode($parsed['pass']) : '';
            $this->redisTls      = in_array($scheme, ['rediss', 'tls', 'ssl'], true);
        } else {
            $this->redisHost     = $_ENV['REDIS_HOST']     ?? '127.0.0.1';
            $this->redisPort     = (int)($_ENV['REDIS_PORT'] ?? 6379);
            $this->redisPassword = $_ENV['REDIS_PASSWORD'] ?? '';
            $this->redisTls      = filter_var($_ENV['REDIS_TLS'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
        }

        $this->sessionDriver    = $_ENV['SESSION_DRIVER']    ?? 'database';
        $this->sessionSecret    = $_ENV['SESSION_SECRET']    ?? '';
        $this->sessionTtlHours  = (int)($_ENV['SESSION_TTL_HOURS'] ?? 6);
        $this->jwtSecret        = $_ENV['JWT_SECRET']        ?? '';
        $this->jwtExpiresIn     = $_ENV['JWT_EXPIRES_IN']    ?? '1h';
        $this->bcryptRounds     = (int)($_ENV['BCRYPT_ROUNDS'] ?? 10);
        $this->otpExpiryMinutes = (int)($_ENV['OTP_EXPIRY_MINUTES'] ?? 10);
        $this->defaultPageSize  = (int)($_ENV['DEFAULT_PAGE_SIZE']  ?? 10);

        $this->mailHost        = $_ENV['MAIL_HOST']         ?? 'localhost';
        $this->mailPort        = (int)($_ENV['MAIL_PORT']   ?? 587);
        $this->mailSecure      = filter_var($_ENV['MAIL_SECURE'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
        $this->mailUsername    = $_ENV['MAIL_USERNAME']     ?? '';
        $this->mailPassword    = $_ENV['MAIL_PASSWORD']     ?? '';
        $this->mailFromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com';
        $this->mailFromName    = $_ENV['MAIL_FROM_NAME']    ?? $this->appName;

        // STORAGE_* (standard naming, replaces old AWS_*)
        $this->storageDriver          = $_ENV['STORAGE_DRIVER']           ?? 'local';
        $this->storageBasePath        = rtrim($_ENV['STORAGE_BASE_PATH']  ?? 'storage/uploads', '/');
        $this->storageAccessKeyId     = $_ENV['STORAGE_ACCESS_KEY_ID']    ?? ($_ENV['AWS_KEY']    ?? '');
        $this->storageSecretAccessKey = $_ENV['STORAGE_SECRET_ACCESS_KEY'] ?? ($_ENV['AWS_SECRET'] ?? '');
        $this->storageEndpoint        = rtrim($_ENV['STORAGE_ENDPOINT']   ?? ($_ENV['AWS_ENDPOINT'] ?? ''), '/');
        $this->storageBucket          = $_ENV['STORAGE_BUCKET']           ?? ($_ENV['AWS_BUCKET'] ?? '');
        $this->storageRegion          = $_ENV['STORAGE_REGION']           ?? ($_ENV['AWS_REGION'] ?? 'us-east-1');
        $this->storageSsl             = filter_var($_ENV['STORAGE_SSL']   ?? 'true', FILTER_VALIDATE_BOOLEAN);
        $this->storagePathStyle       = filter_var(
            $_ENV['STORAGE_PATH_STYLE'] ?? ($_ENV['AWS_PATH_STYLE'] ?? 'false'),
            FILTER_VALIDATE_BOOLEAN
        );
        $this->storageBaseUrl         = rtrim($_ENV['STORAGE_BASE_URL'] ?? '', '/');

        $this->tz = $_ENV['TZ'] ?? 'UTC';

        if ($this->isProduction()) {
            $this->validateSecrets();
        }
    }

    public function isProduction(): bool
    {
        return in_array($this->appEnv, ['production', 'prod'], true);
    }

    public function isFullMode(): bool
    {
        return strtolower($this->appMode) === 'full';
    }

    /**
     * Build the Predis connection parameters.
     *
     * When TLS is enabled (rediss:// / tls://) the SSL context sets `peer_name`
     * to the Redis host. This is required so the TLS ClientHello carries an SNI
     * servername: managed Redis behind an SNI-routing proxy closes connections
     * that omit it. Predis opens a plain tcp socket then upgrades via
     * stream_socket_enable_crypto(), where SNI is taken solely from `peer_name`.
     *
     * @return array<string, mixed>
     */
    public function redisParameters(): array
    {
        $params = [
            'scheme' => $this->redisTls ? 'tls' : 'tcp',
            'host'   => $this->redisHost,
            'port'   => $this->redisPort,
        ];
        if ($this->redisPassword !== '') {
            $params['password'] = $this->redisPassword;
        }
        if ($this->redisTls) {
            $params['ssl'] = [
                'peer_name'         => $this->redisHost,
                'SNI_enabled'       => true,
                'verify_peer'       => true,
                'verify_peer_name'  => true,
            ];
        }
        return $params;
    }

    /**
     * Validate that secrets are set and strong enough in production.
     *
     * @throws \RuntimeException
     */
    private function validateSecrets(): void
    {
        if (strlen($this->sessionSecret) < 32) {
            throw new \RuntimeException(
                'SESSION_SECRET must be at least 32 characters long in production.'
            );
        }
        if (strlen($this->jwtSecret) < 32) {
            throw new \RuntimeException(
                'JWT_SECRET must be at least 32 characters long in production.'
            );
        }
    }
}
