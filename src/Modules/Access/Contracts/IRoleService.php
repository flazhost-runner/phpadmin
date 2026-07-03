<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Access\Contracts;

use PHPAdmin\Modules\Access\Models\Role;

interface IRoleService
{
    /**
     * Paginated list of roles with optional filters.
     *
     * Filters: q_name, q_status, q_desc.
     *
     * @param  array<string,mixed> $filters
     * @return array{
     *     datas: list<Role>,
     *     paginate_data: array{total_data: int, page_size: int, current_page: int, total_page: int}
     * }
     */
    public function index(array $filters, int $perPage, int $page): array;

    /**
     * @param  array<string,mixed> $data  Keys: name, status, desc, created_by, updated_by
     * @throws \PHPAdmin\Core\Exceptions\ValidationAppException
     */
    public function create(array $data): Role;

    /**
     * @throws \PHPAdmin\Core\Exceptions\NotFoundAppException
     */
    public function findById(string $id): Role;

    /**
     * @param  array<string,mixed> $data
     * @throws \PHPAdmin\Core\Exceptions\NotFoundAppException
     */
    public function update(string $id, array $data): Role;

    /**
     * Delete role and detach all permission + user assignments.
     *
     * @throws \PHPAdmin\Core\Exceptions\NotFoundAppException
     */
    public function delete(string $id): void;

    /**
     * @param string[] $ids
     */
    public function deleteSelected(array $ids): void;

    /**
     * Paginated list of ALL permissions, with an `is_assigned` attribute on each item
     * indicating whether the given role currently has that permission.
     *
     * Filters: q_name, q_status, q_desc.
     *
     * @param  array<string,mixed> $filters
     * @return array{
     *     datas: list<\PHPAdmin\Modules\Access\Models\Permission>,
     *     paginate_data: array{total_data: int, page_size: int, current_page: int, total_page: int},
     *     role: Role
     * }
     */
    public function getPermissions(string $roleId, array $filters, int $perPage, int $page): array;

    /**
     * Attach a permission to a role (idempotent).
     *
     * @throws \PHPAdmin\Core\Exceptions\NotFoundAppException
     */
    public function assignPermission(string $roleId, string $permId): void;

    /**
     * Detach a permission from a role.
     *
     * @throws \PHPAdmin\Core\Exceptions\NotFoundAppException
     */
    public function unassignPermission(string $roleId, string $permId): void;

    /**
     * Attach multiple permissions to a role (idempotent).
     *
     * @param string[] $permIds
     */
    public function assignSelected(string $roleId, array $permIds): void;

    /**
     * Detach multiple permissions from a role.
     *
     * @param string[] $permIds
     */
    public function unassignSelected(string $roleId, array $permIds): void;
}
