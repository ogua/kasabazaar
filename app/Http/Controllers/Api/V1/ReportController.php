<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Report;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_shipment'), 403);

        $branchId  = $this->resolveBranch($request);
        $paginated = Report::where('branch_id', $branchId)
            ->latest('generated_at')
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($r) => [
            'id'           => $r->id,
            'branch_id'    => $r->branch_id,
            'report_type'  => $r->report_type,
            'title'        => $r->title,
            'period_start' => $r->period_start,
            'period_end'   => $r->period_end,
            'generated_at' => $r->generated_at,
            'generated_by' => $r->generated_by,
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_shipment'), 403);

        $branchId = $this->resolveBranch($request);
        $report   = Report::where('branch_id', $branchId)->findOrFail($id);

        return $this->success([
            'id'           => $report->id,
            'branch_id'    => $report->branch_id,
            'report_type'  => $report->report_type,
            'title'        => $report->title,
            'parameters'   => $report->parameters,
            'data'         => $report->data,
            'period_start' => $report->period_start,
            'period_end'   => $report->period_end,
            'file_path'    => $report->file_path ? asset('storage/' . $report->file_path) : null,
            'generated_at' => $report->generated_at,
            'generated_by' => $report->generated_by,
        ]);
    }
}
