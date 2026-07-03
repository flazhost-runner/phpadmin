<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Auth\Controllers;

use PHPAdmin\Core\AppConfig;
use PHPAdmin\Core\Middleware\RateLimitMiddleware;
use PHPAdmin\Core\SettingCache;
use PHPAdmin\Core\Themes;
use PHPAdmin\Modules\Auth\Contracts\IAuthService;

/**
 * AuthController — thin controller delegating all business logic to IAuthService.
 *
 * Each public method signature matches the dispatch contract in public/index.php:
 *   $ctrl->{$handlerMethod}($routeVars, $flash, $errors, $oldInput)
 */
class AuthController
{
    public function __construct(
        private readonly IAuthService $authService,
        private readonly AppConfig $config
    ) {
    }

    // ─── Web: show forms ─────────────────────────────────────────────────────

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function showLogin(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->renderAuth('login.php', ['flash' => $flash, 'errors' => $errors], 'Login');
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function showRegister(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->renderAuth('register.php', ['flash' => $flash, 'errors' => $errors], 'Register');
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function showResetReq(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->renderAuth('reset_req.php', ['flash' => $flash, 'errors' => $errors], 'Forgot Password');
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function showResetProc(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $this->renderAuth('reset_proc.php', ['flash' => $flash, 'errors' => $errors], 'Reset Password');
    }

    // ─── Web: handle POST ────────────────────────────────────────────────────

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function login(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        RateLimitMiddleware::checkWeb('auth:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 10, 900);

        $email    = trim((string)($_POST['email']    ?? ''));
        $password = (string)($_POST['password']      ?? '');

        if ($email === '' || $password === '') {
            flash_error('Email and password are required.');
            $_SESSION['_old_input'] = ['email' => $email];
            redirect(route('web.auth.login'));
        }

        try {
            $result = $this->authService->login($email, $password);

            $_SESSION['user_id']          = (string)$result['user']->id;
            $_SESSION['token']            = $result['token'];
            $_SESSION['user_roles']       = $result['roles'];
            $_SESSION['user_permissions'] = $result['permissions'];

            redirect(route('admin.v1.dashboard.index'));
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            $_SESSION['_old_input'] = ['email' => $email];
            redirect(route('web.auth.login'));
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function register(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        RateLimitMiddleware::checkWeb('auth:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 10, 900);

        $data = [
            'code'                  => trim((string)($_POST['code']                  ?? '')),
            'name'                  => trim((string)($_POST['name']                  ?? '')),
            'email'                 => trim((string)($_POST['email']                 ?? '')),
            'password'              => (string)($_POST['password']                   ?? ''),
            'password_confirmation' => (string)($_POST['password_confirmation']      ?? ''),
        ];

        try {
            $this->authService->register($data);
            flash_success('Register Success.');
            redirect(route('web.auth.login'));
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            $safe = $data;
            unset($safe['password'], $safe['password_confirmation']);
            $_SESSION['_old_input'] = $safe;
            redirect(route('web.auth.register'));
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function logout(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $token = (string)($_SESSION['token'] ?? '');
        if ($token !== '') {
            try {
                $payload = $this->authService->verifyJwt($token);
                if ($payload !== null) {
                    $jti = (string)($payload['jti'] ?? '');
                    if ($jti !== '') {
                        $this->authService->logout(
                            (string)($_SESSION['user_id'] ?? ''),
                            $jti,
                            $this->jwtTtlSeconds()
                        );
                    }
                }
            } catch (\Throwable) {
                // Best-effort: clear session even if blacklist fails
            }
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();

        redirect(route('web.auth.login'));
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function resetRequest(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        RateLimitMiddleware::checkWeb('auth:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 10, 900);

        $email = trim((string)($_POST['email'] ?? ''));

        if ($email === '') {
            flash_error('Email is required.');
            redirect(route('admin.v1.auth.reset.req'));
        }

        try {
            $this->authService->requestOtp($email);
            flash_success('OTP Send Success.');
            redirect(route('admin.v1.auth.reset.proc'));
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            redirect(route('admin.v1.auth.reset.req'));
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function resetProcess(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        RateLimitMiddleware::checkWeb('otp:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 5, 900);

        $email    = trim((string)($_POST['email']    ?? ''));
        $otp      = trim((string)($_POST['otp']      ?? ''));
        $password = (string)($_POST['password']      ?? '');
        $confirm  = (string)($_POST['password_confirmation'] ?? '');

        if ($password !== $confirm) {
            flash_error('Passwords do not match.');
            $_SESSION['_old_input'] = ['email' => $email, 'otp' => $otp];
            redirect(route('admin.v1.auth.reset.proc'));
        }

        try {
            $this->authService->verifyOtp($email, $otp, $password);
            flash_success('Reset Password Success.');
            redirect(route('web.auth.login'));
        } catch (\Throwable $e) {
            flash_error($e->getMessage());
            $_SESSION['_old_input'] = ['email' => $email, 'otp' => $otp];
            redirect(route('admin.v1.auth.reset.proc'));
        }
    }

    // ─── API ─────────────────────────────────────────────────────────────────

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiLogin(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        RateLimitMiddleware::check('auth:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 10, 900);

        /** @var array<string,mixed> $body */
        $body     = json_decode((string)file_get_contents('php://input'), true) ?? [];
        $email    = trim((string)($body['email']    ?? ''));
        $password = (string)($body['password']      ?? '');

        if ($email === '' || $password === '') {
            json_response(['status' => false, 'message' => 'Email and password are required.', 'data' => null], 422);
        }

        try {
            $result = $this->authService->login($email, $password);
            $user = $result['user'];
            json_response([
                'status'  => true,
                'message' => 'Login success.',
                'data'    => [
                    'token' => $result['token'],
                    'user'  => $this->safeUser($user),
                ],
            ]);
        } catch (\Throwable $e) {
            json_response(['status' => false, 'message' => $e->getMessage(), 'data' => null], 401);
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiLogout(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $authHeader = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        $token      = str_starts_with($authHeader, 'Bearer ') ? substr($authHeader, 7) : '';

        if ($token !== '') {
            try {
                $payload = $this->authService->verifyJwt($token);
                if ($payload !== null) {
                    $jti = (string)($payload['jti'] ?? '');
                    if ($jti !== '') {
                        $this->authService->logout('', $jti, $this->jwtTtlSeconds());
                    }
                }
            } catch (\Throwable) {
            }
        }

        json_response(['status' => true, 'message' => 'Logged out.', 'data' => null]);
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiMe(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        $authHeader = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        $token      = str_starts_with($authHeader, 'Bearer ') ? substr($authHeader, 7) : '';
        $payload    = $this->authService->verifyJwt($token);

        if ($payload === null) {
            json_response(['status' => false, 'message' => 'Unauthorized.', 'data' => null], 401);
        }

        $user = $this->authService->getUserById((string)($payload['sub'] ?? ''));
        if ($user === null) {
            json_response(['status' => false, 'message' => 'User not found.', 'data' => null], 404);
        }

        json_response(['status' => true, 'message' => 'Success', 'data' => $this->safeUser($user)]);
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiRegister(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        RateLimitMiddleware::check('auth:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 10, 900);

        /** @var array<string,mixed> $body */
        $body = json_decode((string)file_get_contents('php://input'), true) ?? [];

        try {
            $user = $this->authService->register($body);
            json_response(['status' => true, 'message' => 'Success', 'data' => $this->safeUser($user)], 201);
        } catch (\Throwable $e) {
            json_response(['status' => false, 'message' => $e->getMessage(), 'data' => null], 422);
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiResetRequest(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        RateLimitMiddleware::check('auth:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 10, 900);

        /** @var array<string,mixed> $body */
        $body  = json_decode((string)file_get_contents('php://input'), true) ?? [];
        $email = trim((string)($body['email'] ?? ''));

        try {
            $this->authService->requestOtp($email);
            json_response([
                'status'  => true,
                'message' => 'If that email is registered, an OTP has been sent.',
                'data'    => null,
            ]);
        } catch (\Throwable $e) {
            json_response(['status' => false, 'message' => $e->getMessage(), 'data' => null], 422);
        }
    }

    /**
     * @param array<string,string> $routeVars
     * @param array<string,string> $flash
     * @param array<string,string> $errors
     * @param array<string,mixed>  $oldInput
     */
    public function apiResetProcess(array $routeVars, array $flash, array $errors, array $oldInput): void
    {
        RateLimitMiddleware::check('otp:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 5, 900);

        /** @var array<string,mixed> $body */
        $body     = json_decode((string)file_get_contents('php://input'), true) ?? [];
        $email    = trim((string)($body['email']    ?? ''));
        $otp      = trim((string)($body['otp']      ?? ''));
        $password = (string)($body['password']      ?? '');

        try {
            $this->authService->verifyOtp($email, $otp, $password);
            json_response(['status' => true, 'message' => 'Password reset successful.', 'data' => null]);
        } catch (\Throwable $e) {
            json_response(['status' => false, 'message' => $e->getMessage(), 'data' => null], 422);
        }
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function jwtTtlSeconds(): int
    {
        $v = $this->config->jwtExpiresIn;
        if (is_numeric($v)) {
            return (int)$v;
        }
        $unit   = strtolower(substr($v, -1));
        $amount = (int)substr($v, 0, -1);
        return match ($unit) {
            's' => $amount,
            'm' => $amount * 60,
            'h' => $amount * 3600,
            'd' => $amount * 86400,
            default => (int)$v,
        };
    }

    /**
     * Build common view data (theme, setting, CSRF, etc.) for all auth pages.
     *
     * @return array<string, mixed>
     */
    private function commonViewData(): array
    {
        $setting   = SettingCache::get() ?? [];
        $themeName = (string)($setting['theme'] ?? 'Blue');

        try {
            $theme = Themes::get($themeName);
        } catch (\InvalidArgumentException) {
            $theme     = Themes::get('Blue');
            $themeName = 'Blue';
        }

        return [
            'theme'       => $theme,
            'setting'     => $setting,
            'themeName'   => $themeName,
            'themes'      => Themes::all(),
            '_csrf'       => (string)($_SESSION['_csrf'] ?? ''),
            'currentUser' => null,
            'flash'       => [],
            'errors'      => [],
        ];
    }

    /**
     * Buffer an auth view file and render it inside the full_width layout.
     *
     * @param array<string, mixed> $data     Extra variables for the inner view
     * @param string               $title    Browser tab title
     */
    /**
     * Cast a user object (stdClass from Capsule or Eloquent model) to a safe
     * array with sensitive fields removed.
     *
     * @param object|null $user
     * @return array<string,mixed>
     */
    private function safeUser(mixed $user): array
    {
        if ($user === null) {
            return [];
        }
        $arr = method_exists($user, 'toArray') ? $user->toArray() : (array)$user;
        unset($arr['password'], $arr['password_otp'], $arr['password_otp_expires']);
        return $arr;
    }

    private function renderAuth(string $viewFile, array $data = [], string $title = 'PHPAdmin'): void
    {
        $common   = $this->commonViewData();
        $merged   = array_merge($common, $data);
        $pageData = array_merge($merged, ['pageTitle' => $title . ' — ' . $this->config->appName]);

        // Buffer the inner content view
        ob_start();
        extract($pageData, EXTR_SKIP);
        include $this->config->appRoot . '/src/views/auth/' . $viewFile;
        $content = ob_get_clean();

        // Render in the full-width (auth) layout
        render(
            $this->config->appRoot . '/src/views/layouts/full_width.php',
            array_merge($pageData, ['content' => $content])
        );
    }
}
