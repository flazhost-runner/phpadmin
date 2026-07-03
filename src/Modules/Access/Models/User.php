<?php

declare(strict_types=1);

namespace PHPAdmin\Modules\Access\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * User Eloquent model.
 *
 * Pivot tables: users_roles (user_id, role_id).
 * All belongsToMany arguments are explicit to avoid magic resolution.
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string|null $phone
 * @property string $email
 * @property string|null $email_verified_at
 * @property string $password
 * @property string|null $password_otp
 * @property int|null $password_otp_expires
 * @property string $status
 * @property string|null $picture
 * @property bool|int $blocked
 * @property string|null $blocked_reason
 * @property string $timezone
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Role> $roles
 *
 * @method static static|null find(mixed $id, array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Builder<static> where(mixed $col, mixed $op = null, mixed $val = null)
 */
class User extends Model
{
    protected $table      = 'users';
    public $incrementing = false;
    protected $keyType    = 'string';
    public $timestamps = true;

    /** @var list<string> */
    protected $fillable = [
        'id', 'code', 'name', 'phone', 'email',
        'email_verified_at', 'password', 'password_otp',
        'password_otp_expires', 'status', 'picture',
        'blocked', 'blocked_reason', 'timezone',
        'created_by', 'updated_by',
    ];

    /** @var list<string> */
    protected $hidden = ['password', 'password_otp'];

    // ─── Relations ───────────────────────────────────────────────────────────

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'users_roles', 'user_id', 'role_id');
    }

    // ─── Business logic ──────────────────────────────────────────────────────

    /**
     * Check if this user has access to a named route + HTTP method.
     * Administrator role bypasses all checks.
     */
    public function hasAccess(string $routeName, string $method): bool
    {
        $this->loadMissing('roles.permissions');

        foreach ($this->roles as $role) {
            // Administrator bypasses everything
            if (strtolower((string)$role->name) === 'administrator') {
                return true;
            }
            foreach ($role->permissions as $permission) {
                if (
                    $permission->name === $routeName
                    && strtoupper((string)$permission->method) === strtoupper($method)
                    && strtolower((string)$permission->status) === 'active'
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}
