<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Access\Services;

use PHPAdmin\Core\Exceptions\NotFoundAppException;
use PHPAdmin\Modules\Access\Contracts\IRoleService;
use PHPAdmin\Modules\Access\Models\Permission;
use PHPAdmin\Modules\Access\Models\Role;

class RoleService implements IRoleService
{
    // ─── IRoleService ─────────────────────────────────────────────────────────

    public function index(array $filters, int $perPage, int $page): array
    {
        $query = Role::query();

        if (!empty($filters['q_name'])) {
            $query->where('name', 'like', ci_like((string)$filters['q_name']));
        }
        if (!empty($filters['q_status'])) {
            $query->where('status', (string)$filters['q_status']);
        }
        if (!empty($filters['q_desc'])) {
            $query->where('desc', 'like', ci_like((string)$filters['q_desc']));
        }

        $total = $query->count();
        /** @var list<Role> $items */
        $items = $query->orderBy('name')->forPage($page, $perPage)->get()->all();

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

    public function create(array $data): Role
    {
        $role             = new Role();
        $role->id         = uuid();
        $role->name       = trim((string)($data['name']       ?? ''));
        $role->status     = (string)($data['status']          ?? 'Active');
        $role->desc       = trim((string)($data['desc']       ?? '')) ?: null;
        $role->created_by = (string)($data['created_by']      ?? '');
        $role->updated_by = (string)($data['updated_by']      ?? '');
        $role->save();
        return $role;
    }

    public function findById(string $id): Role
    {
        /** @var Role|null $role */
        $role = Role::with('permissions')->find($id);
        if ($role === null) {
            throw new NotFoundAppException("Role not found: {$id}");
        }
        return $role;
    }

    public function update(string $id, array $data): Role
    {
        $role             = $this->findById($id);
        $role->name       = trim((string)($data['name']       ?? $role->name));
        $role->status     = (string)($data['status']          ?? $role->status);
        $role->desc       = trim((string)($data['desc']       ?? '')) ?: null;
        $role->updated_by = (string)($data['updated_by']      ?? '');
        $role->save();
        return $role;
    }

    public function delete(string $id): void
    {
        $role = $this->findById($id);
        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();
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

    public function getPermissions(string $roleId, array $filters, int $perPage, int $page): array
    {
        // Load role with all its current permissions (for is_assigned check)
        $role = $this->findById($roleId);

        // Collect assigned permission ids into a plain array for fast lookup
        $assignedIds = $role->permissions->pluck('id')->all();

        $query = Permission::query();

        if (!empty($filters['q_name'])) {
            $query->where('name', 'like', ci_like((string)$filters['q_name']));
        }
        if (!empty($filters['q_status'])) {
            $query->where('status', (string)$filters['q_status']);
        }
        if (!empty($filters['q_desc'])) {
            $query->where('desc', 'like', ci_like((string)$filters['q_desc']));
        }

        $total = $query->count();

        /** @var \Illuminate\Database\Eloquent\Collection<int, Permission> $paged */
        $paged = $query->orderBy('name')->forPage($page, $perPage)->get();

        /** @var list<Permission> $items */
        $items = $paged
            ->map(static function (Permission $perm) use ($assignedIds): Permission {
                $perm->is_assigned = in_array($perm->id, $assignedIds, true);
                return $perm;
            })
            ->all();

        return [
            'datas'        => $items,
            'paginate_data' => [
                'total_data'   => $total,
                'page_size'    => $perPage,
                'current_page' => $page,
                'total_page'   => (int)ceil($total / max(1, $perPage)),
            ],
            'role'         => $role,
        ];
    }

    public function assignPermission(string $roleId, string $permId): void
    {
        $role = $this->findById($roleId);
        // Idempotent: attach only if not already present
        if (!$role->permissions()->where('permissions.id', $permId)->exists()) {
            $role->permissions()->attach($permId);
        }
    }

    public function unassignPermission(string $roleId, string $permId): void
    {
        $role = $this->findById($roleId);
        $role->permissions()->detach($permId);
    }

    public function assignSelected(string $roleId, array $permIds): void
    {
        if ($permIds === []) {
            return;
        }
        $role      = $this->findById($roleId);
        $existing  = $role->permissions()->pluck('permissions.id')->all();
        $toAttach  = array_values(array_diff($permIds, $existing));
        if ($toAttach !== []) {
            $role->permissions()->attach($toAttach);
        }
    }

    public function unassignSelected(string $roleId, array $permIds): void
    {
        if ($permIds === []) {
            return;
        }
        $role = $this->findById($roleId);
        $role->permissions()->detach($permIds);
    }
}
