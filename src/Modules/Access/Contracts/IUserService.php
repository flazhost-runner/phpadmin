<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Access\Contracts;

use PHPAdmin\Modules\Access\Models\User;

interface IUserService
{
    /**
     * Paginated list of users with optional filters.
     *
     * Filters: q_code, q_name, q_phone, q_email (LIKE), q_status (exact), q_role (role id).
     *
     * @param  array<string,mixed> $filters
     * @return array{
     *     datas: list<User>,
     *     paginate_data: array{total_data: int, page_size: int, current_page: int, total_page: int}
     * }
     */
    public function index(array $filters, int $perPage, int $page): array;

    /**
     * Create a new user.
     * Required: code, name, email, password.
     * Optional: phone, timezone, status, picture (stored path), blocked, blocked_reason, roles (id[]).
     *
     * @param  array<string,mixed> $data
     * @throws \PHPAdmin\Core\Exceptions\ValidationAppException on invalid input
     */
    public function create(array $data): User;

    /**
     * Find a user by primary key.
     *
     * @throws \PHPAdmin\Core\Exceptions\NotFoundAppException
     */
    public function findById(string $id): User;

    /**
     * Update an existing user.
     * Password is updated only when 'password' key is present and non-empty.
     *
     * @param  array<string,mixed> $data
     * @throws \PHPAdmin\Core\Exceptions\NotFoundAppException
     */
    public function update(string $id, array $data): User;

    /**
     * Delete a user and detach all role assignments.
     *
     * @throws \PHPAdmin\Core\Exceptions\NotFoundAppException
     */
    public function delete(string $id): void;

    /**
     * Delete multiple users by id.
     * Missing ids are silently skipped.
     *
     * @param string[] $ids
     */
    public function deleteSelected(array $ids): void;
}
