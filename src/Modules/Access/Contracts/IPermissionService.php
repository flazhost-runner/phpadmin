<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Access\Contracts;

use PHPAdmin\Modules\Access\Models\Permission;

interface IPermissionService
{
    /**
     * Paginated list of permissions with optional filters.
     *
     * Filters: q_name, q_guard, q_method, q_status, q_desc.
     *
     * @param  array<string,mixed> $filters
     * @return array{
     *     datas: list<Permission>,
     *     paginate_data: array{total_data: int, page_size: int, current_page: int, total_page: int}
     * }
     */
    public function index(array $filters, int $perPage, int $page): array;

    /**
     * @param  array<string,mixed> $data  Keys: name, guard_name, method, status, desc, created_by, updated_by
     */
    public function create(array $data): Permission;

    /**
     * @throws \PHPAdmin\Core\Exceptions\NotFoundAppException
     */
    public function findById(string $id): Permission;

    /**
     * @param  array<string,mixed> $data
     * @throws \PHPAdmin\Core\Exceptions\NotFoundAppException
     */
    public function update(string $id, array $data): Permission;

    /**
     * @throws \PHPAdmin\Core\Exceptions\NotFoundAppException
     */
    public function delete(string $id): void;

    /**
     * @param string[] $ids
     */
    public function deleteSelected(array $ids): void;

    /**
     * Upsert permissions from the RouteRegistry.
     * name = route name, method = HTTP method, guard_name = 'api' if name starts with 'api.' else 'web'.
     * Idempotent — existing records with matching (name, guard_name) are updated, not duplicated.
     */
    public function syncFromRoutes(): void;

    /**
     * @return array<string, array{method: string, path: string}>
     */
    public function getAllRegisteredRoutes(): array;
}
