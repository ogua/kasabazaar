<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ContactMessage;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactMessageController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_contact_message'), 403);

        $query = ContactMessage::query();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($m) => [
            'id'         => $m->id,
            'name'       => $m->name,
            'email'      => $m->email,
            'phone'      => $m->phone,
            'subject'    => $m->subject,
            'status'     => $m->status,
            'created_at' => $m->created_at,
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_contact_message'), 403);

        $message = ContactMessage::findOrFail($id);

        return $this->success([
            'id'         => $message->id,
            'name'       => $message->name,
            'email'      => $message->email,
            'phone'      => $message->phone,
            'subject'    => $message->subject,
            'message'    => $message->message,
            'status'     => $message->status,
            'reply'      => $message->reply,
            'replied_by' => $message->replied_by,
            'replied_at' => $message->replied_at,
            'created_at' => $message->created_at,
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_contact_message'), 403);

        $message = ContactMessage::findOrFail($id);

        $request->validate([
            'reply'  => 'nullable|string',
            'status' => 'nullable|in:pending,read,replied',
        ]);

        $data = $request->only(['reply', 'status']);

        if ($request->has('reply')) {
            $data['replied_by'] = auth()->id();
            $data['replied_at'] = now();
            $data['status']     = 'replied';
        }

        $message->update($data);

        return $this->success([
            'id'         => $message->id,
            'status'     => $message->status,
            'reply'      => $message->reply,
            'replied_at' => $message->replied_at,
        ], 'Message updated.');
    }
}
