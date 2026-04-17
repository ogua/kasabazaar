<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Branch;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $branches = $user->branches()->orderBy('name')->get()
            ->map(fn ($b) => $this->formatBranch($b));

        return $this->success($branches);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $user   = auth()->user();
        $branch = Branch::whereIn('id', $user->branches()->pluck('branches.id'))->findOrFail($id);

        return $this->success($this->formatBranch($branch));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_branch'), 403);

        $user   = auth()->user();
        $branch = Branch::whereIn('id', $user->branches()->pluck('branches.id'))->findOrFail($id);

        $request->validate([
            'name'    => 'sometimes|string|max:255',
            'country' => 'nullable|string',
            'state'   => 'nullable|string',
            'address' => 'nullable|string',
            'email'   => 'nullable|email',
            'phone'   => 'nullable|string|max:30',
        ]);

        $branch->update($request->only(['name', 'country', 'state', 'address', 'email', 'phone']));

        return $this->success($this->formatBranch($branch->fresh()));
    }

    private function formatBranch(Branch $b): array
    {
        return [
            'id'      => $b->id,
            'name'    => $b->name,
            'slug'    => $b->slug,
            'country' => $b->country,
            'state'   => $b->state,
            'address' => $b->address,
            'email'   => $b->email,
            'phone'   => $b->phone,
        ];
    }
}
