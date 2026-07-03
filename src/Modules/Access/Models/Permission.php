<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Access\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permission Eloquent model.
 *
 * Pivot table: roles_permissions (permission_id, role_id).
 * All belongsToMany arguments are explicit.
 *
 * @property string $id
 * @property string $name
 * @property string|null $guard_name
 * @property string|null $method
 * @property string $status
 * @property string|null $desc
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Illuminate\Support\Carbon|string|null $created_at
 * @property \Illuminate\Support\Carbon|string|null $updated_at
 * @property bool $is_assigned Runtime flag set by RoleService::getPermissions().
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Role> $roles
 *
 * @method static static|null find(mixed $id, array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder<static> where(mixed $col, mixed $op = null, mixed $val = null)
 */
class Permission extends Model
{
    protected $table      = 'permissions';
    public $incrementing = false;
    protected $keyType    = 'string';
    public $timestamps = true;

    /** @var list<string> */
    protected $fillable = [
        'id', 'name', 'guard_name', 'method',
        'status', 'desc', 'created_by', 'updated_by',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'roles_permissions', 'permission_id', 'role_id');
    }
}
