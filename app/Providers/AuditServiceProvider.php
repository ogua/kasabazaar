<?php

namespace App\Providers;

use App\Listeners\AuditAuthEvents;
use App\Listeners\AuditPermissionChanges;
use App\Services\Audit\AuditContext;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\ActivityLogStatus;
use Spatie\Activitylog\LogBatch;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;

/**
 * Wires the audit trail into the parts of the app no single model sees:
 *
 *  - queued jobs carry the user, impersonator, branch and batch of the request that queued them,
 *    so a bulk status job is attributed to the staff member who clicked, not "System";
 *  - artisan commands get a "console" context named after the command;
 *  - sign-ins / failed sign-ins / sign-outs and role & permission grants are recorded.
 */
class AuditServiceProvider extends ServiceProvider
{
    /** Long-running commands that host other work; they must not hold one batch open forever. */
    private const HOST_COMMANDS = ['queue:work', 'queue:listen', 'schedule:work', 'schedule:run', 'horizon', 'serve', 'pail', 'test'];

    public function register(): void
    {
        $this->app->singleton(AuditContext::class);
    }

    public function boot(): void
    {
        $this->propagateContextToJobs();
        $this->trackConsoleCommands();

        // Data written by migrations is schema housekeeping, not a user action — and older
        // migrations run before the activity_log table exists on a fresh database.
        Event::listen(MigrationsStarted::class, fn () => app(ActivityLogStatus::class)->disable());
        Event::listen(MigrationsEnded::class, fn () => app(ActivityLogStatus::class)->enable());

        Event::listen(Login::class, [AuditAuthEvents::class, 'handleLogin']);
        Event::listen(Failed::class, [AuditAuthEvents::class, 'handleFailed']);
        Event::listen(Logout::class, [AuditAuthEvents::class, 'handleLogout']);
        Event::listen(PasswordResetLinkSent::class, [AuditAuthEvents::class, 'handlePasswordResetLinkSent']);

        Event::listen(PermissionAttached::class, [AuditPermissionChanges::class, 'handlePermissionAttached']);
        Event::listen(PermissionDetached::class, [AuditPermissionChanges::class, 'handlePermissionDetached']);
        Event::listen(RoleAttached::class, [AuditPermissionChanges::class, 'handleRoleAttached']);
        Event::listen(RoleDetached::class, [AuditPermissionChanges::class, 'handleRoleDetached']);
    }

    private function propagateContextToJobs(): void
    {
        Queue::createPayloadUsing(function (): array {
            $batch = app(LogBatch::class);

            return ['audit' => AuditContext::instance()->payloadForJob($batch->isOpen() ? $batch->getUuid() : null)];
        });

        Queue::before(function (JobProcessing $event): void {
            $context = AuditContext::instance();
            $batch = app(LogBatch::class);
            $audit = (array) ($event->job->payload()['audit'] ?? []);
            $openedBatch = false;

            if (! $batch->isOpen()) {
                $batch->setBatch($audit['batch_uuid'] ?? (string) \Illuminate\Support\Str::uuid());
                $openedBatch = true;
            }

            $context->push([
                'channel' => 'queue',
                'source' => class_basename($event->job->resolveName()),
                'causer_id' => $audit['causer_id'] ?? null,
                'impersonator_id' => $audit['impersonator_id'] ?? null,
                'branch_id' => $audit['branch_id'] ?? null,
                'origin_channel' => $audit['origin_channel'] ?? null,
                'opened_batch' => $openedBatch,
                'job_id' => $this->jobKey($event->job),
            ]);
        });

        $leave = function (Job $job): void {
            $context = AuditContext::instance();
            $frame = $context->current();

            if (($frame['job_id'] ?? null) !== $this->jobKey($job)) {
                return;
            }

            $context->pop();

            if ($frame['opened_batch']) {
                app(LogBatch::class)->endBatch();
            }
        };

        Queue::after(fn (JobProcessed $event) => $leave($event->job));
        Event::listen(JobExceptionOccurred::class, fn (JobExceptionOccurred $event) => $leave($event->job));
        Queue::failing(fn (JobFailed $event) => $leave($event->job));
    }

    private function trackConsoleCommands(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            $hosts = in_array($event->command, self::HOST_COMMANDS, true);
            $batch = app(LogBatch::class);

            if (! $hosts) {
                $batch->startBatch();
            }

            AuditContext::instance()->push([
                'channel' => 'console',
                'source' => $event->command,
                'opened_batch' => ! $hosts,
                'command' => $event->command,
            ]);
        });

        Event::listen(CommandFinished::class, function (CommandFinished $event): void {
            $context = AuditContext::instance();
            $frame = $context->current();

            if (($frame['command'] ?? null) !== $event->command) {
                return;
            }

            $context->pop();

            if ($frame['opened_batch']) {
                app(LogBatch::class)->endBatch();
            }
        });
    }

    private function jobKey(Job $job): string
    {
        return (string) ($job->uuid() ?? $job->getJobId() ?? spl_object_id($job));
    }
}
