<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use Auditable;

    protected static string $auditLog = 'access';

    /** @var list<string> */
    protected static array $auditHighRisk = ['name', 'guard_name'];

    /**
     * The role's permission names when it was last saved — the "before" side of the audit diff
     * App\Listeners\AuditPermissionChanges records once Shield re-syncs the permissions.
     *
     * @var list<string>|null
     */
    public ?array $permissionSnapshot = null;

    protected static function booted(): void
    {
        static::saving(function (Role $role): void {
            $role->permissionSnapshot = $role->exists
                ? $role->permissions()->pluck('name')->all()
                : [];
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
