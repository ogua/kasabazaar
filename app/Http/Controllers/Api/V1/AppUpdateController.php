<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\AppUpdatePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public on purpose (no token): a signed-out or deactivated device must still
 * be told it has to update. Never errors — an unknown app, platform or version
 * just gets a policy that prompts nothing.
 */
class AppUpdateController extends BaseApiController
{
    public function __invoke(Request $request, AppUpdatePolicy $policy): JsonResponse
    {
        return $this->success($policy->resolve(
            $request->string('app')->toString() ?: null,
            $request->string('platform')->toString() ?: null,
            $request->string('version')->toString() ?: null,
        ));
    }
}
