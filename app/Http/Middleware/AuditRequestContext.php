<?php

namespace App\Http\Middleware;

use App\Services\Audit\AuditContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Activitylog\LogBatch;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opens the audit context for one HTTP request (Filament page, Livewire action, API call,
 * webhook) so every audit entry it causes knows its IP, agent, URL and panel, and groups them
 * in one batch — "everything this click changed".
 */
class AuditRequestContext
{
    public function __construct(private AuditContext $context, private LogBatch $batch) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->enterHttp($request);
        $this->batch->startBatch();

        try {
            return $next($request);
        } finally {
            $this->batch->endBatch();
            $this->context->pop();
        }
    }
}
