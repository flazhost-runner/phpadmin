<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Access\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Role Eloquent model.
 *
 * Pivot tables:
 *   roles_permissions (role_id, permission_id)
 *   users_roles       (role_id, user_id)
 *
 * All belongsToMany arguments are explicit.
 *
 * @property string $id
 * @property string $name
 * @property string $status
 * @property string|null $desc
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Permission> $permissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 *
 * @method static static|null find(mixed $id, array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder<static> where(mixed $col, mixed $op = null, mixed $val = null)
 */
class Role extends Model
{
    protected $table      = 'roles';
    public $incrementing = false;
    protected $keyType    = 'string';
    public $timestamps = true;

    /** @var list<string> */
    protected $fillable = ['id', 'name', 'status', 'desc', 'created_by', 'updated_by'];

    // ─── Relations ───────────────────────────────────────────────────────────

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'roles_permissions', 'role_id', 'permission_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_roles', 'role_id', 'user_id');
    }
}
