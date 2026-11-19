<?php

namespace App\Listeners;

use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AccessChangeRecorder;
use App\Services\Audit\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;
use Spatie\Permission\Models\Permission;

/**
 * Role and permission grants made through spatie/laravel-permission (assignRole,
 * givePermissionTo, syncPermissions, ...).
 *
 * A role's permissions are compared with the snapshot App\Models\Role takes when it is saved,
 * because Shield's role editor replaces them with syncPermissions(), which detaches silently and
 * re-attaches the whole set. Filament relationship selects (a user's roles on the user form)
 * fire no package events at all and are diffed by App\Services\Audit\AccessChangeRecorder.
 */
class AuditPermissionChanges
{
    public function handlePermissionAttached(PermissionAttached $event): void
    {
        if ($event->model instanceof Role) {
            $this->recordRolePermissionDiff($event->model);

            return;
        }

        if (AccessChangeRecorder::isRecording()) {
            return;
        }

        $names = $this->permissionNames($event->permissionsOrIds);

        if ($names->isNotEmpty() && $event->model instanceof User) {
            Audit::security('permissions_granted', 'Direct permissions granted to '.$event->model->name, $event->model, ['added' => $names->all()], true);
        }
    }

    public function handlePermissionDetached(PermissionDetached $event): void
    {
        if ($event->model instanceof Role) {
            $this->recordRolePermissionDiff($event->model);

            return;
        }

        if (AccessChangeRecorder::isRecording()) {
            return;
        }

        $names = $this->permissionNames($event->permissionsOrIds);

        if ($names->isNotEmpty() && $event->model instanceof User) {
            Audit::security('permissions_revoked', 'Direct permissions revoked from '.$event->model->name, $event->model, ['removed' => $names->all()], true);
        }
    }

    public function handleRoleAttached(RoleAttached $event): void
    {
        if (AccessChangeRecorder::isRecording()) {
            return;
        }

        $names = $this->roleNames($event->rolesOrIds);

        if ($names->isNotEmpty() && $event->model instanceof Model) {
            Audit::security('roles_assigned', 'Roles assigned to '.($event->model->getAttribute('name') ?? 'user').': '.$names->implode(', '), $event->model, ['added' => $names->all()], true);
        }
    }

    public function handleRoleDetached(RoleDetached $event): void
    {
        if (AccessChangeRecorder::isRecording()) {
            return;
        }

        $names = $this->roleNames($event->rolesOrIds);

        if ($names->isNotEmpty() && $event->model instanceof Model) {
            Audit::security('roles_removed', 'Roles removed from '.($event->model->getAttribute('name') ?? 'user').': '.$names->implode(', '), $event->model, ['removed' => $names->all()], true);
        }
    }

    private function recordRolePermissionDiff(Role $role): void
    {
        $before = collect($role->permissionSnapshot ?? []);
        $after = $role->permissions()->pluck('name');

        $added = $after->diff($before)->values();
        $removed = $role->permissionSnapshot === null ? collect() : $before->diff($after)->values();

        $role->permissionSnapshot = $after->all();

        if ($added->isEmpty() && $removed->isEmpty()) {
            return;
        }

        Audit::security(
            'permissions_changed',
            "Permissions of role {$role->name} changed (+{$added->count()} / -{$removed->count()})",
            $role,
            ['added' => $added->all(), 'removed' => $removed->all()],
            true,
        );
    }

    private function permissionNames(mixed $permissionsOrIds): Collection
    {
        $items = collect($permissionsOrIds instanceof Model ? [$permissionsOrIds] : $permissionsOrIds);
        $models = $items->filter(fn ($item) => $item instanceof Model);
        $ids = $items->reject(fn ($item) => $item instanceof Model);

        return $models->pluck('name')
            ->merge($ids->isEmpty() ? [] : Permission::query()->whereIn('id', $ids->all())->pluck('name'))
            ->unique()
            ->values();
    }

    private function roleNames(mixed $rolesOrIds): Collection
    {
        $items = collect($rolesOrIds instanceof Model ? [$rolesOrIds] : $rolesOrIds);
        $models = $items->filter(fn ($item) => $item instanceof Model);
        $ids = $items->reject(fn ($item) => $item instanceof Model);

        return $models->pluck('name')
            ->merge($ids->isEmpty() ? [] : Role::query()->whereIn('id', $ids->all())->pluck('name'))
            ->unique()
            ->values();
    }
}
