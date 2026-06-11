<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseApiController extends Controller
{
    protected function success(mixed $data, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    protected function paginated(LengthAwarePaginator $paginated, callable $transformer = null): JsonResponse
    {
        $items = $transformer
            ? $paginated->getCollection()->map($transformer)->values()
            : $paginated->items();

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    protected function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    protected function resolveBranch(Request $request): string
    {
        $user     = auth()->user();
        $branchId = $request->header('X-Branch-Id') ?? $user->branch_id;

        abort_unless(
            $user->branches()->where('branches.id', $branchId)->exists(),
            403,
            'Access to this branch is not allowed.'
        );

        return $branchId;
    }

    /**
     * All branch ids the authenticated user belongs to.
     * Use for id-based lookups so records in the user's other branches
     * don't 404 just because a different branch is currently active.
     */
    protected function userBranchIds(): array
    {
        return auth()->user()->branches()->pluck('branches.id')->all();
    }

    protected function isDriver(): bool
    {
        $user  = auth()->user();
        $staff = \App\Models\Staff::where('user_id', $user->id)->with('role')->first();
        return $staff && $staff->isDriver();
    }

    protected function driverStaff(): ?\App\Models\Staff
    {
        return \App\Models\Staff::where('user_id', auth()->id())->with('role')->first();
    }
}
