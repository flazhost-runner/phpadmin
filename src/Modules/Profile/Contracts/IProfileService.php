<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Profile\Contracts;

/**
 * IProfileService — contract for the profile self-service operations.
 *
 * Mirrors NodeAdmin's ProfileService interface:
 *   getProfile(userId): array
 *   updateProfile(userId, data): array
 */
interface IProfileService
{
    /**
     * Fetch the user's own profile data as an associative array.
     *
     * @return array<string, mixed>
     * @throws \PHPAdmin\Core\Exceptions\AppException when user not found.
     */
    public function getProfile(string $userId): array;

    /**
     * Update the current user's own profile.
     *
     * @param  array<string, mixed> $data  Fields to update.
     * @return array<string, mixed>        Updated profile data.
     * @throws \PHPAdmin\Core\Exceptions\AppException on validation or DB error.
     */
    public function updateProfile(string $userId, array $data): array;
}
