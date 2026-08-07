<div>
    <div class="row mb-6">
        <div class="col-md-4"><div class="stat-card"><div class="text-muted">Available</div><div class="h3">GHS {{ number_format($summary['balance_ghs'] ?? 0, 2) }}</div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="text-muted">Pending</div><div class="h3">GHS {{ number_format($summary['pending_balance_ghs'] ?? 0, 2) }}</div></div></div>
        <div class="col-md-4"><div class="stat-card"><div class="text-muted">Lifetime</div><div class="h3">GHS {{ number_format($summary['lifetime_earnings_ghs'] ?? 0, 2) }}</div></div></div>
    </div>

    @if ($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    @if ($showPayoutForm)
        <div class="stat-card mb-6" style="max-width:420px;">
            <div class="form-group"><label>Amount (GHS)</label><input type="number" class="form-control" wire:model="amount_ghs"></div>
            <div class="form-group">
                <label>Payout Method</label>
                <select class="form-control" wire:model="payout_method">
                    <option value="momo">Mobile Money</option>
                    <option value="bank">Bank Transfer</option>
                </select>
            </div>
            <div class="form-group"><label>Account Details</label><textarea class="form-control" wire:model="payout_details" rows="2"></textarea></div>
            <button type="button" class="btn btn-dark" wire:click="requestPayout">Submit Request</button>
        </div>
    @else
        <button type="button" class="btn btn-dark mb-6" wire:click="$set('showPayoutForm', true)">Request Payout</button>
    @endif

    <div class="stat-card">
        <h4>Transaction History</h4>
        <table class="table">
            <thead><tr><th>Date</th><th>Type</th><th>Amount</th></tr></thead>
            <tbody>
                @forelse ($transactions as $tx)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($tx['created_at'])->format('M d, Y') }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $tx['type'])) }}</td>
                        <td>GHS {{ number_format($tx['amount_ghs'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No transactions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
