<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ClearingAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClearingAgentController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $agents = ClearingAgent::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'staff_id'])
            ->map(fn (ClearingAgent $agent) => [
                'id' => $agent->id,
                'name' => $agent->name,
                'phone' => $agent->phone,
                'is_staff' => (bool) $agent->staff_id,
            ]);

        return $this->success($agents);
    }
}
