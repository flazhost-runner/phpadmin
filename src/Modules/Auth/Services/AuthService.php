<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Auth\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPAdmin\Core\AppConfig;
use PHPAdmin\Modules\Auth\Contracts\IAuthService;
use Predis\Client as Redis;

class AuthService implements IAuthService
{
    public function __construct(
        private readonly AppConfig $config,
        private readonly Redis $redis
    ) {
    }

    // ─── IAuthService ────────────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     */
    public function login(string $email, string $password): array
    {
        /** @var \stdClass|null $user */
        $user = Capsule::table('users')
            ->where('email', $email)
            ->first();

        if ($user === null || !password_verify($password, (string)$user->password)) {
            throw new \RuntimeException('Wrong email or password.');
        }

        if (strtolower((string)$user->status) !== 'active') {
            throw new \RuntimeException('Your account is inactive. Please contact the administrator.');
        }

        if ((bool)$user->blocked) {
            $reason = (string)$user->blocked_reason;
            throw new \RuntimeException('Your account has been blocked' . ($reason !== '' ? ': ' . $reason : '.'));
        }

        // Load roles
        $roleRows = Capsule::table('users_roles')
            ->join('roles', 'roles.id', '=', 'users_roles.role_id')
            ->where('users_roles.user_id', $user->id)
            ->select('roles.id', 'roles.name')
            ->get();

        $roleNames = [];
        $roleIds   = [];
        foreach ($roleRows as $r) {
            $roleNames[] = (string)$r->name;
            $roleIds[]   = (string)$r->id;
        }

        // Load permissions for those roles
        $permRows = [];
        if ($roleIds !== []) {
            $permRows = Capsule::table('roles_permissions')
                ->join('permissions', 'permissions.id', '=', 'roles_permissions.permission_id')
                ->whereIn('roles_permissions.role_id', $roleIds)
                ->select('permissions.name', 'permissions.method', 'permissions.guard_name')
                ->get()
                ->toArray();
        }

        $permissions = array_map(static fn (\stdClass $p): array => [
            'name'       => (string)$p->name,
            'method'     => (string)$p->method,
            'guard_name' => (string)$p->guard_name,
        ], $permRows);

        // Build JWT
        $jti     = uuid();
        $now     = time();
        $payload = [
            'sub' => (string)$user->id,
            'jti' => $jti,
            'iat' => $now,
            'exp' => $now + $this->parseDuration($this->config->jwtExpiresIn),
        ];
        $token = JWT::encode($payload, $this->config->jwtSecret, 'HS256');

        return [
            'user'        => $user,
            'token'       => $token,
            'roles'       => $roleNames,
            'permissions' => $permissions,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function register(array $data): \stdClass
    {
        $code     = trim((string)($data['code']                  ?? ''));
        $name     = trim((string)($data['name']                  ?? ''));
        $email    = trim((string)($data['email']                 ?? ''));
        $password = (string)($data['password']                   ?? '');
        $confirm  = (string)($data['password_confirmation']      ?? '');

        if ($code === '' || $name === '' || $email === '' || $password === '') {
            throw new \RuntimeException('All fields are required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Invalid email address.');
        }
        if ($password !== $confirm) {
            throw new \RuntimeException('Passwords do not match.');
        }
        if (strlen($password) < 8) {
            throw new \RuntimeException('Password must be at least 8 characters.');
        }

        $existsEmail = Capsule::table('users')->where('email', $email)->exists();
        if ($existsEmail) {
            throw new \RuntimeException('Email already exists.');
        }

        $existsCode = Capsule::table('users')->where('code', $code)->exists();
        if ($existsCode) {
            throw new \RuntimeException('User code is already taken.');
        }

        $id   = uuid();
        $now  = date('Y-m-d H:i:s');
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->config->bcryptRounds]);

        Capsule::table('users')->insert([
            'id'         => $id,
            'code'       => $code,
            'name'       => $name,
            'email'      => $email,
            'password'   => $hash,
            'status'     => 'Active',
            'blocked'    => false,
            'timezone'   => 'UTC',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        /** @var \stdClass $user */
        $user = Capsule::table('users')->where('id', $id)->first();
        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function logout(string $userId, string $jti, int $ttl): void
    {
        if ($jti === '') {
            return;
        }
        // ttl may be derived from parseDuration already, but ensure positive
        $this->redis->setex('blacklist:' . $jti, max(1, $ttl), '1');
    }

    /**
     * {@inheritdoc}
     */
    public function requestOtp(string $email): void
    {
        /** @var \stdClass|null $user */
        $user = Capsule::table('users')->where('email', $email)->first();
        if ($user === null) {
            // Return silently to avoid email enumeration
            return;
        }

        // 6-digit numeric OTP hashed with bcrypt
        $otp     = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hash    = password_hash($otp, PASSWORD_BCRYPT, ['cost' => $this->config->bcryptRounds]);
        $expires = time() + ($this->config->otpExpiryMinutes * 60);

        Capsule::table('users')
            ->where('id', $user->id)
            ->update([
                'password_otp'         => $hash,
                'password_otp_expires' => $expires,
                'updated_at'           => date('Y-m-d H:i:s'),
            ]);

        // Send OTP via email (requires mail config; fail silently in dev)
        $this->sendOtpEmail((string)$user->email, (string)$user->name, $otp);
    }

    /**
     * {@inheritdoc}
     */
    public function verifyOtp(string $email, string $otp, string $newPassword): void
    {
        if ($email === '' || $otp === '' || $newPassword === '') {
            throw new \RuntimeException('Email, OTP, and new password are required.');
        }

        /** @var \stdClass|null $user */
        $user = Capsule::table('users')->where('email', $email)->first();
        if ($user === null) {
            throw new \RuntimeException('Email not found.');
        }

        $expires = (int)($user->password_otp_expires ?? 0);
        if ($expires === 0 || time() > $expires) {
            throw new \RuntimeException('OTP has expired.');
        }

        if (!password_verify($otp, (string)($user->password_otp ?? ''))) {
            throw new \RuntimeException('OTP is invalid.');
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => $this->config->bcryptRounds]);

        Capsule::table('users')
            ->where('id', $user->id)
            ->update([
                'password'             => $hash,
                'password_otp'         => null,
                'password_otp_expires' => null,
                'updated_at'           => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserById(string $id): ?\stdClass
    {
        /** @var \stdClass|null $user */
        $user = Capsule::table('users')->where('id', $id)->first();
        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function verifyJwt(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        try {
            $decoded = JWT::decode($token, new Key($this->config->jwtSecret, 'HS256'));
            /** @var array<string, mixed> $payload */
            $payload = (array)$decoded;

            // Check Redis blacklist
            $jti = (string)($payload['jti'] ?? '');
            if ($jti !== '' && $this->redis->get('blacklist:' . $jti) !== null) {
                return null;
            }

            return $payload;
        } catch (\Throwable) {
            return null;
        }
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Parse duration string (e.g. '1h', '30m', '7d', '3600') into seconds.
     */
    private function parseDuration(string $value): int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        $unit   = strtolower(substr($value, -1));
        $amount = (int)substr($value, 0, -1);
        return match ($unit) {
            's' => $amount,
            'm' => $amount * 60,
            'h' => $amount * 3600,
            'd' => $amount * 86400,
            default => (int)$value,
        };
    }

    /**
     * Dispatch a password-reset OTP email via PHPMailer.
     * Fails silently when mail is not configured.
     */
    private function sendOtpEmail(string $toEmail, string $toName, string $otp): void
    {
        if ($this->config->mailHost === '' || $this->config->mailUsername === '') {
            return;
        }

        try {
            $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host       = $this->config->mailHost;
            $mailer->SMTPAuth   = true;
            $mailer->Username   = $this->config->mailUsername;
            $mailer->Password   = $this->config->mailPassword;
            $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->Port       = $this->config->mailPort;

            $mailer->setFrom($this->config->mailFromAddress, $this->config->mailFromName);
            $mailer->addAddress($toEmail, $toName);
            $mailer->isHTML(true);
            $mailer->Subject = 'Your Password Reset OTP';
            $mailer->Body    = '<p>Dear ' . htmlspecialchars($toName, ENT_QUOTES, 'UTF-8') . ',</p>'
                . '<p>Your OTP code is: <strong>' . htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') . '</strong></p>'
                . '<p>This code expires in 10 minutes.</p>';

            $mailer->send();
        } catch (\Throwable) {
            // Fail silently — OTP is stored in DB even if email fails
        }
    }
}
