<div>
    @if ($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    @if ($showForm)
        <div class="stat-card mb-6" style="max-width:420px;">
            <div class="form-group"><label>Code</label><input type="text" class="form-control" wire:model="code" style="text-transform:uppercase;"></div>
            <div class="form-group">
                <label>Type</label>
                <select class="form-control" wire:model="type">
                    <option value="percentage">Percentage</option>
                    <option value="fixed">Fixed Amount (GHS)</option>
                </select>
            </div>
            <div class="form-group"><label>Value</label><input type="number" step="0.01" class="form-control" wire:model="value"></div>
            <div class="form-group"><label>Minimum Order (GHS, optional)</label><input type="number" step="0.01" class="form-control" wire:model="min_order_amount_ghs"></div>
            <div class="form-group"><label>Expires At (optional)</label><input type="date" class="form-control" wire:model="expires_at"></div>
            <button type="button" class="btn btn-dark" wire:click="create">Create Coupon</button>
        </div>
    @else
        <button type="button" class="btn btn-dark mb-6" wire:click="$set('showForm', true)">+ New Coupon</button>
    @endif

    <div class="stat-card">
        <table class="table">
            <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Used</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($coupons as $coupon)
                    <tr wire:key="coupon-{{ $coupon['id'] }}">
                        <td><code>{{ $coupon['code'] }}</code></td>
                        <td>{{ ucfirst($coupon['type']) }}</td>
                        <td>{{ $coupon['type'] === 'percentage' ? $coupon['value'].'%' : 'GHS '.number_format($coupon['value'], 2) }}</td>
                        <td>{{ $coupon['used_count'] }}</td>
                        <td>
                            <button type="button" class="btn btn-sm {{ $coupon['is_active'] ? 'btn-success' : 'btn-outline-secondary' }}"
                                    wire:click="toggleActive('{{ $coupon['id'] }}', {{ $coupon['is_active'] ? 'true' : 'false' }})">
                                {{ $coupon['is_active'] ? 'Active' : 'Inactive' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No coupons yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
