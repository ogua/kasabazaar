<div class="stat-card">
    @forelse ($reviews as $review)
        <div class="border-bottom py-4" wire:key="review-{{ $review['id'] }}">
            <strong>{{ $review['user']['name'] ?? 'Customer' }}</strong> on <em>{{ $review['product']['name'] ?? '' }}</em> — {{ $review['rating'] }} ★
            <p>{{ $review['body'] }}</p>

            @if (!empty($review['vendor_reply']))
                <div class="bg-light p-2 rounded"><strong>Your reply:</strong> {{ $review['vendor_reply'] }}</div>
            @else
                <div class="d-flex" style="gap:8px;">
                    <input type="text" class="form-control" placeholder="Write a reply…" wire:model="replyDrafts.{{ $review['id'] }}">
                    <button type="button" class="btn btn-outline-dark" wire:click="reply('{{ $review['id'] }}')">Reply</button>
                </div>
            @endif
        </div>
    @empty
        <p>No reviews yet.</p>
    @endforelse
</div>
