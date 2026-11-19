<?php

namespace App\Services\Audit;

use App\Models\Branch;
use App\Models\User;
use Closure;
use Illuminate\Support\Collection;

/**
 * Before/after diff of a user's roles and branches, for the places that change them with a
 * plain relationship sync (Filament's relationship selects, the users API) — those fire no
 * model or package events, so nothing else would notice.
 *
 *     $snapshot = AccessChangeRecorder::snapshot($user);
 *     ... save the form / sync ...
 *     AccessChangeRecorder::record($user, $snapshot);
 */
class AccessChangeRecorder
{
    private static int $depth = 0;

    /**
     * Whether a summarised access change is in progress — the per-grant package-event
     * listener stays quiet meanwhile, so one staff-role change is one entry, not thirty.
     */
    public static function isRecording(): bool
    {
        return self::$depth > 0;
    }

    /**
     * Run $callback (assignRole / givePermissionTo / syncRoles …) and record the net change
     * to the user's roles, direct permissions and branches as a single entry.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function around(User $user, Closure $callback): mixed
    {
        if (self::$depth > 0) {
            return $callback();
        }

        $before = self::snapshot($user);
        self::$depth++;

        try {
            $result = $callback();
        } finally {
            self::$depth--;
        }

        self::record($user, $before);

        return $result;
    }

    /**
     * @return array{roles: list<string>, permissions: list<string>, branches: list<string>}
     */
    public static function snapshot(?User $user): array
    {
        if ($user === null || ! $user->exists) {
            return ['roles' => [], 'permissions' => [], 'branches' => []];
        }

        return [
            'roles' => $user->roles()->pluck('name')->sort()->values()->all(),
            'permissions' => $user->permissions()->pluck('name')->sort()->values()->all(),
            'branches' => $user->branches()->pluck('branches.id')->map(fn ($id) => (string) $id)->sort()->values()->all(),
        ];
    }

    /**
     * @param  array{roles: list<string>, permissions?: list<string>, branches: list<string>}  $before
     */
    public static function record(User $user, array $before): void
    {
        $after = self::snapshot($user);

        self::recordDiff(
            $user,
            collect($before['permissions'] ?? []),
            collect($after['permissions']),
            'permissions_changed',
            fn (Collection $added, Collection $removed) => "Direct permissions of {$user->name} changed (+{$added->count()} / -{$removed->count()})",
        );

        self::recordDiff(
            $user,
            collect($before['roles']),
            collect($after['roles']),
            'roles_changed',
            fn (Collection $added, Collection $removed) => "Roles of {$user->name} changed".self::summary($added, $removed),
        );

        $branchNames = Branch::query()
            ->whereIn('id', array_merge($before['branches'], $after['branches']))
            ->pluck('name', 'id');

        self::recordDiff(
            $user,
            collect($before['branches'])->map(fn ($id) => $branchNames[$id] ?? $id),
            collect($after['branches'])->map(fn ($id) => $branchNames[$id] ?? $id),
            'branches_changed',
            fn (Collection $added, Collection $removed) => "Branch access of {$user->name} changed".self::summary($added, $removed),
        );
    }

    private static function recordDiff(User $user, Collection $before, Collection $after, string $event, callable $describe): void
    {
        $added = $after->diff($before)->values();
        $removed = $before->diff($after)->values();

        if ($added->isEmpty() && $removed->isEmpty()) {
            return;
        }

        Audit::security($event, $describe($added, $removed), $user, [
            'added' => $added->all(),
            'removed' => $removed->all(),
        ], true);
    }

    private static function summary(Collection $added, Collection $removed): string
    {
        $parts = [];

        if ($added->isNotEmpty()) {
            $parts[] = 'added '.$added->implode(', ');
        }

        if ($removed->isNotEmpty()) {
            $parts[] = 'removed '.$removed->implode(', ');
        }

        return ': '.implode('; ', $parts);
    }
}
