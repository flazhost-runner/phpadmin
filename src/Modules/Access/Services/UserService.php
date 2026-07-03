<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Access\Services;

use PHPAdmin\Core\Exceptions\NotFoundAppException;
use PHPAdmin\Core\Exceptions\ValidationAppException;
use PHPAdmin\Modules\Access\Contracts\IUserService;
use PHPAdmin\Modules\Access\Models\User;

class UserService implements IUserService
{
    // ─── IUserService ─────────────────────────────────────────────────────────

    public function index(array $filters, int $perPage, int $page): array
    {
        $query = User::with('roles');

        if (!empty($filters['q_code'])) {
            $query->where('code', 'like', ci_like((string)$filters['q_code']));
        }
        if (!empty($filters['q_name'])) {
            $query->where('name', 'like', ci_like((string)$filters['q_name']));
        }
        if (!empty($filters['q_phone'])) {
            $query->where('phone', 'like', ci_like((string)$filters['q_phone']));
        }
        if (!empty($filters['q_email'])) {
            $query->where('email', 'like', ci_like((string)$filters['q_email']));
        }
        if (!empty($filters['q_status'])) {
            $query->where('status', (string)$filters['q_status']);
        }
        if (!empty($filters['q_role'])) {
            $query->whereHas(
                'roles',
                static fn($q) => $q->where('roles.id', (string)$filters['q_role'])
            );
        }

        $total = $query->count();
        /** @var list<User> $items */
        $items = $query->orderBy('code')->forPage($page, $perPage)->get()->all();

        return [
            'datas'        => $items,
            'paginate_data' => [
                'total_data'   => $total,
                'page_size'    => $perPage,
                'current_page' => $page,
                'total_page'   => (int)ceil($total / max(1, $perPage)),
            ],
        ];
    }

    public function create(array $data): User
    {
        $password = (string)($data['password'] ?? '');
        if ($password === '') {
            throw new ValidationAppException('Password is required.', ['password' => 'Password is required.']);
        }

        $user               = new User();
        $user->id           = uuid();
        $user->code         = trim((string)($data['code']          ?? ''));
        $user->name         = trim((string)($data['name']          ?? ''));
        $user->phone        = trim((string)($data['phone']         ?? '')) ?: null;
        $user->email        = trim((string)($data['email']         ?? ''));
        $user->password     = password_hash($password, PASSWORD_BCRYPT);
        $user->status       = (string)($data['status']             ?? 'Active');
        $user->timezone     = (string)($data['timezone']           ?? 'UTC');
        $user->blocked      = (bool)($data['blocked']              ?? false);
        $user->blocked_reason = !empty($data['blocked_reason'])
            ? trim((string)$data['blocked_reason'])
            : null;
        $user->created_by   = (string)($data['created_by']         ?? '');
        $user->updated_by   = (string)($data['updated_by']         ?? '');

        if (!empty($data['picture'])) {
            $user->picture = (string)$data['picture'];
        }

        $user->save();

        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->roles()->sync(array_filter($data['roles']));
        }

        return $user->load('roles');
    }

    public function findById(string $id): User
    {
        /** @var User|null $user */
        $user = User::with('roles')->find($id);
        if ($user === null) {
            throw new NotFoundAppException("User not found: {$id}");
        }
        return $user;
    }

    public function update(string $id, array $data): User
    {
        $user               = $this->findById($id);
        $user->code         = trim((string)($data['code']          ?? $user->code));
        $user->name         = trim((string)($data['name']          ?? $user->name));
        $user->phone        = trim((string)($data['phone']         ?? '')) ?: null;
        $user->email        = trim((string)($data['email']         ?? $user->email));
        $user->status       = (string)($data['status']             ?? $user->status);
        $user->timezone     = (string)($data['timezone']           ?? $user->timezone);
        $user->blocked      = (bool)($data['blocked']              ?? false);
        $user->blocked_reason = !empty($data['blocked_reason'])
            ? trim((string)$data['blocked_reason'])
            : null;
        $user->updated_by   = (string)($data['updated_by']         ?? '');

        $password = (string)($data['password'] ?? '');
        if ($password !== '') {
            $user->password = password_hash($password, PASSWORD_BCRYPT);
        }

        if (array_key_exists('picture', $data) && $data['picture'] !== null && $data['picture'] !== '') {
            $user->picture = (string)$data['picture'];
        }

        $user->save();

        if (isset($data['roles']) && is_array($data['roles'])) {
            $user->roles()->sync(array_filter($data['roles']));
        } elseif (array_key_exists('roles', $data)) {
            // Explicitly passed empty → remove all roles
            $user->roles()->detach();
        }

        return $user->load('roles');
    }

    public function delete(string $id): void
    {
        $user = $this->findById($id);
        $user->roles()->detach();
        $user->delete();
    }

    public function deleteSelected(array $ids): void
    {
        foreach ($ids as $id) {
            try {
                $this->delete((string)$id);
            } catch (NotFoundAppException) {
                // Skip missing records silently
            }
        }
    }
}
