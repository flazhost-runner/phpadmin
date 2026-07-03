<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Auth\Contracts;

interface IAuthService
{
    /**
     * Authenticate a user by email and password.
     *
     * Returns an array with keys:
     *   'user'        stdClass    the user row
     *   'token'       string      signed JWT
     *   'roles'       string[]    role names
     *   'permissions' array[]     permission rows (name, method, guard_name)
     *
     * @return array{user: \stdClass, token: string, roles: string[], permissions: array<int, array<string, string>>}
     * @throws \RuntimeException on invalid credentials or inactive account
     */
    public function login(string $email, string $password): array;

    /**
     * Register a new user account.
     *
     * @param  array<string, mixed> $data  Keys: code, name, email, password, password_confirmation
     * @return \stdClass                   the newly created user row
     * @throws \RuntimeException on validation failure
     */
    public function register(array $data): \stdClass;

    /**
     * Blacklist a JWT by its jti in Redis so verifyJwt() rejects it.
     *
     * @param string $userId  User id (for logging purposes; may be empty for API logout)
     * @param string $jti     The JWT id claim
     * @param int    $ttl     Seconds until the blacklist entry expires
     */
    public function logout(string $userId, string $jti, int $ttl): void;

    /**
     * Generate a 6-character OTP, hash it, store in the user row with a 10-minute expiry,
     * and dispatch a password-reset email.
     *
     * @throws \RuntimeException if the email is not found
     */
    public function requestOtp(string $email): void;

    /**
     * Verify the OTP and set a new password, then clear OTP fields.
     *
     * @throws \RuntimeException on expired/invalid OTP or mismatched email
     */
    public function verifyOtp(string $email, string $otp, string $newPassword): void;

    /**
     * Retrieve a user by primary key.
     *
     * @return \stdClass|null  null when not found
     */
    public function getUserById(string $id): ?\stdClass;

    /**
     * Decode and validate a JWT.
     *
     * Returns the payload as an associative array, or null when the token is
     * invalid, expired, or blacklisted in Redis.
     *
     * @return array<string, mixed>|null
     */
    public function verifyJwt(string $token): ?array;
}
