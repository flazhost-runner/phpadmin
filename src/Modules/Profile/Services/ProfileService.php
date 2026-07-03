<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Profile\Services;

use PHPAdmin\Core\Exceptions\NotFoundAppException;
use PHPAdmin\Core\Exceptions\ValidationAppException;
use PHPAdmin\Modules\Access\Models\User;
use PHPAdmin\Modules\Profile\Contracts\IProfileService;

/**
 * ProfileService — self-service profile read/update.
 *
 * Only the currently-authenticated user may update their own record.
 * Password change is optional: leave blank to keep current.
 */
class ProfileService implements IProfileService
{
    // ─── IProfileService ──────────────────────────────────────────────────────

    public function getProfile(string $userId): array
    {
        /** @var User|null $user */
        $user = User::with('roles')->find($userId);

        if ($user === null) {
            throw new NotFoundAppException("User not found: {$userId}");
        }

        return $this->toArray($user);
    }

    public function updateProfile(string $userId, array $data): array
    {
        /** @var User|null $user */
        $user = User::find($userId);

        if ($user === null) {
            throw new NotFoundAppException("User not found: {$userId}");
        }

        // Basic field updates
        if (isset($data['name']) && trim((string)$data['name']) !== '') {
            $user->name = trim((string)$data['name']);
        }
        if (array_key_exists('phone', $data)) {
            $user->phone = trim((string)$data['phone']) ?: null;
        }
        if (isset($data['email']) && trim((string)$data['email']) !== '') {
            $user->email = trim((string)$data['email']);
        }
        if (isset($data['timezone']) && trim((string)$data['timezone']) !== '') {
            $user->timezone = trim((string)$data['timezone']);
        }
        if (isset($data['status']) && in_array($data['status'], ['Active', 'Inactive'], true)) {
            $user->status = (string)$data['status'];
        }

        // Password — optional, only update if provided
        $password = (string)($data['password'] ?? '');
        if ($password !== '') {
            $confirm = (string)($data['password_confirmation'] ?? '');
            if ($password !== $confirm) {
                throw new ValidationAppException(
                    'Password confirmation does not match.',
                    ['password_confirmation' => 'Password confirmation does not match.']
                );
            }
            $user->password = password_hash($password, PASSWORD_BCRYPT);
        }

        // Picture — optional, only update if provided
        if (!empty($data['picture'])) {
            $user->picture = (string)$data['picture'];
        }

        $user->updated_by = $userId;
        $user->save();

        return $this->toArray($user->load('roles'));
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function toArray(User $user): array
    {
        return [
            'id'       => $user->id,
            'code'     => $user->code,
            'name'     => $user->name,
            'phone'    => $user->phone ?? '',
            'email'    => $user->email,
            'timezone' => $user->timezone ?? 'UTC',
            'status'   => $user->status,
            'picture'  => $user->picture ?? '',
            'roles'    => $user->roles->pluck('name')->all(),
        ];
    }
}
