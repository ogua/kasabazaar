<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\DeviceToken;
use App\Http\Controllers\Api\V1\Customer\CustomerBaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerNotificationController extends CustomerBaseController
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($notifications, fn ($n) => [
            'id'         => $n->id,
            'type'       => $n->data['type'] ?? null,
            'title'      => $n->data['title'] ?? null,
            'body'       => $n->data['body'] ?? null,
            'data'       => $n->data,
            'read_at'    => $n->read_at,
            'created_at' => $n->created_at,
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->success(null, 'Notification marked as read.');
    }

    public function storeDeviceToken(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => 'required|string',
            'platform' => 'nullable|in:ios,android',
        ]);

        DeviceToken::updateOrCreate(
            ['user_id' => auth()->id(), 'token' => $request->token],
            ['platform' => $request->platform]
        );

        return $this->success(null, 'Device token registered.');
    }

    public function removeDeviceToken(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string']);

        DeviceToken::where('user_id', auth()->id())
            ->where('token', $request->token)
            ->delete();

        return $this->success(null, 'Device token removed.');
    }
}
