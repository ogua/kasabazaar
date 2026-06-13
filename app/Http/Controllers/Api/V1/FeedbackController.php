<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\CustomerFeedback;
use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->can('view_any_customer::feedback'), 403);

        $query = CustomerFeedback::query();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($rating = $request->input('rating')) {
            $query->where('rating', $rating);
        }

        $paginated = $query->latest()->paginate((int) $request->input('per_page', 20));

        return $this->paginated($paginated, fn ($f) => $this->formatFeedback($f, false));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('view_customer::feedback'), 403);

        $feedback = CustomerFeedback::findOrFail($id);

        return $this->success($this->formatFeedback($feedback, true));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        abort_unless(auth()->user()->can('update_customer::feedback'), 403);

        $feedback = CustomerFeedback::findOrFail($id);

        $request->validate([
            'response'             => 'nullable|string',
            'status'               => 'nullable|in:pending,reviewed,resolved',
            'complaint_resolution' => 'nullable|string',
        ]);

        $data = $request->only(['response', 'status', 'complaint_resolution']);

        if ($request->has('response')) {
            $data['responded_by'] = auth()->id();
        }

        $feedback->update($data);

        return $this->success($this->formatFeedback($feedback->fresh(), true), 'Feedback updated.');
    }

    private function formatFeedback(CustomerFeedback $f, bool $full): array
    {
        $data = [
            'id'               => $f->id,
            'user_id'          => $f->user_id,
            'shipment_id'      => $f->shipment_id,
            'feedback_on'      => $f->feedback_on,
            'customer_name'    => $f->customer_name,
            'customer_email'   => $f->customer_email,
            'customer_phone'   => $f->customer_phone,
            'invoice_number'   => $f->invoice_number,
            'category'         => $f->category,
            'rating'           => $f->rating,
            'status'           => $f->status,
            'created_at'       => $f->created_at,
        ];

        if ($full) {
            $data['comment']              = $f->comment;
            $data['attachments']          = $f->attachments;
            $data['complaint_type']       = $f->complaint_type;
            $data['complaint_reason']     = $f->complaint_reason;
            $data['complaint_resolution'] = $f->complaint_resolution;
            $data['response']             = $f->response;
            $data['responded_by']         = $f->responded_by;
            $data['meta']                 = $f->meta;
        }

        return $data;
    }
}
