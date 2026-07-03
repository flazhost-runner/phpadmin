<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Access\Services;

use PHPAdmin\Core\Exceptions\NotFoundAppException;
use PHPAdmin\Core\RouteRegistry;
use PHPAdmin\Modules\Access\Contracts\IPermissionService;
use PHPAdmin\Modules\Access\Models\Permission;

class PermissionService implements IPermissionService
{
    public function __construct(
        private readonly RouteRegistry $routeRegistry
    ) {
    }

    // ─── IPermissionService ───────────────────────────────────────────────────

    public function index(array $filters, int $perPage, int $page): array
    {
        $query = Permission::query();

        if (!empty($filters['q_name'])) {
            $query->where('name', 'like', ci_like((string)$filters['q_name']));
        }
        if (!empty($filters['q_guard'])) {
            $query->where('guard_name', (string)$filters['q_guard']);
        }
        if (!empty($filters['q_method'])) {
            $query->where('method', (string)$filters['q_method']);
        }
        if (!empty($filters['q_status'])) {
            $query->where('status', (string)$filters['q_status']);
        }
        if (!empty($filters['q_desc'])) {
            $query->where('desc', 'like', ci_like((string)$filters['q_desc']));
        }

        $total = $query->count();
        /** @var list<Permission> $items */
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

    public function create(array $data): Permission
    {
        $perm             = new Permission();
        $perm->id         = uuid();
        $perm->name       = trim((string)($data['name']       ?? ''));
        $perm->guard_name = (string)($data['guard_name']      ?? 'web');
        $method           = strtoupper(trim((string)($data['method'] ?? '')));
        $perm->method     = $method !== '' ? $method : null;
        $perm->status     = (string)($data['status']          ?? 'Active');
        $perm->desc       = trim((string)($data['desc']       ?? '')) ?: null;
        $perm->created_by = (string)($data['created_by']      ?? '');
        $perm->updated_by = (string)($data['updated_by']      ?? '');
        $perm->save();
        return $perm;
    }

    public function findById(string $id): Permission
    {
        /** @var Permission|null $perm */
        $perm = Permission::find($id);
        if ($perm === null) {
            throw new NotFoundAppException("Permission not found: {$id}");
        }
        return $perm;
    }

    public function update(string $id, array $data): Permission
    {
        $perm             = $this->findById($id);
        $perm->name       = trim((string)($data['name']       ?? $perm->name));
        $perm->guard_name = (string)($data['guard_name']      ?? $perm->guard_name);
        $method           = strtoupper(trim((string)($data['method'] ?? '')));
        if ($method !== '') {
            $perm->method = $method;
        }
        $perm->status     = (string)($data['status']          ?? $perm->status);
        $perm->desc       = trim((string)($data['desc']       ?? '')) ?: null;
        $perm->updated_by = (string)($data['updated_by']      ?? '');
        $perm->save();
        return $perm;
    }

    public function delete(string $id): void
    {
        $perm = $this->findById($id);
        $perm->roles()->detach();
        $perm->delete();
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

    public function syncFromRoutes(): void
    {
        $routes = $this->routeRegistry->all();
        $now    = date('Y-m-d H:i:s');

        foreach ($routes as $name => $route) {
            $guardName = str_starts_with($name, 'api.') ? 'api' : 'web';
            $method    = strtoupper($route['method']);

            /** @var Permission|null $existing */
            $existing = Permission::where('name', $name)
                ->where('guard_name', $guardName)
                ->first();

            if ($existing === null) {
                $perm             = new Permission();
                $perm->id         = uuid();
                $perm->name       = $name;
                $perm->guard_name = $guardName;
                $perm->method     = $method;
                $perm->status     = 'Active';
                $perm->created_at = $now;
                $perm->updated_at = $now;
                $perm->save();
            } elseif ($existing->method !== $method) {
                $existing->method     = $method;
                $existing->updated_at = $now;
                $existing->save();
            }
        }
    }

    public function getAllRegisteredRoutes(): array
    {
        return $this->routeRegistry->all();
    }
}
