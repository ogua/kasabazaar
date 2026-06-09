<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerComplaintController extends CustomerBaseController
{
    public function index(Request $request): JsonResponse
    {
        $user      = $request->user();
        $paginated = ContactMessage::where('email', $user->email)
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($m) => $this->formatMessage($m));
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $message = ContactMessage::create([
            'name'    => $user->name,
            'email'   => $user->email,
            'phone'   => $user->phone,
            'subject' => $request->subject,
            'message' => $request->message,
            'status'  => 'pending',
        ]);

        return $this->success($this->formatMessage($message), 'Message submitted.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $user    = auth()->user();
        $message = ContactMessage::where('email', $user->email)->findOrFail($id);

        return $this->success($this->formatMessage($message));
    }

    private function formatMessage(ContactMessage $m): array
    {
        return [
            'id'         => $m->id,
            'subject'    => $m->subject,
            'message'    => $m->message,
            'status'     => $m->status,
            'reply'      => $m->reply,
            'replied_at' => $m->replied_at,
            'created_at' => $m->created_at,
        ];
    }
}
