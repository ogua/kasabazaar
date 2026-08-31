@extends('web.sub-template')

@section('heading', 'Shipment Activity - ' . $shipment->shipping_reference)

@section('sub-heading', 'Your Shipment')

@section('main-content')

<section id="shipment-portal" class="services section light-background">

    <div class="container section-title" data-aos="fade-up">
        <h2>Shipment {{ $shipment->shipping_reference }}</h2>
        <p>Tracking Number: <strong>{{ $shipment->tracking_number }}</strong></p>
    </div>

    <div class="container" data-aos="fade-up" data-aos-delay="100">

        @if (session('portal_status'))
            <div class="alert alert-info">{{ session('portal_status') }}</div>
        @endif
        @if (session('portal_error'))
            <div class="alert alert-danger">{{ session('portal_error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- Track your shipment --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="service-item p-4">
                    <h4 class="mb-3">Track Your Shipment</h4>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <div class="text-muted small">Tracking Number</div>
                            <div class="fw-bold">{{ $shipment->tracking_number ?? 'N/A' }}</div>
                            @if ($shipment->tracking_number)
                                <a href="{{ route('our-tracking') }}?query={{ urlencode($shipment->tracking_number) }}"
                                   target="_blank" class="btn btn-outline-primary btn-sm mt-2">Track shipment</a>
                            @endif
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="text-muted small">MSC / Ocean Tracking</div>
                            @if ($shipment->msc_tracking_number)
                                <div class="fw-bold">{{ $shipment->msc_tracking_number }}</div>
                                <a href="{{ $shipment->mscTrackingUrl() }}" target="_blank"
                                   class="btn btn-outline-secondary btn-sm mt-2">Track live on MSC</a>
                            @else
                                <div class="text-muted">Not yet assigned</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status & Route --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="service-item p-4">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <div class="text-muted small">Status</div>
                            <span class="badge" style="background:#{{ $shipment->status?->getColor() === 'success' ? '198754' : ($shipment->status?->getColor() === 'danger' ? 'dc3545' : ($shipment->status?->getColor() === 'info' ? '0dcaf0' : 'ffc107')) }}; color:#fff;">
                                {{ ucfirst($shipment->status?->value ?? 'N/A') }}
                            </span>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="text-muted small">From</div>
                            <div>{{ $shipment->originBranch?->name ?? $shipment->origin_branch_id }}</div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="text-muted small">To</div>
                            <div>{{ $shipment->destinationBranch?->name ?? $shipment->destination_branch_id }}</div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="text-muted small">Estimated Delivery</div>
                            <div>{{ $shipment->estimated_delivery_date ? \Carbon\Carbon::parse($shipment->estimated_delivery_date)->format('M d, Y') : 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Summary --}}
        @php
            $balanceDue = $shipment->outstanding_balance;
        @endphp
        <div class="row mb-4">
            <div class="col-12">
                <div class="service-item p-4">
                    <h4 class="mb-3">Payment Summary</h4>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <div class="text-muted small">Shipping Cost</div>
                            <div>${{ number_format((float) $shipment->shipping_cost, 2) }}</div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="text-muted small">Insurance</div>
                            <div>${{ number_format((float) $shipment->insurance, 2) }}</div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="text-muted small">VAT</div>
                            <div>${{ number_format((float) $shipment->vat, 2) }}</div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="text-muted small">Total</div>
                            <div class="fw-bold">${{ number_format((float) $shipment->total, 2) }}</div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-3 mb-2">
                            <div class="text-muted small">Paid</div>
                            <div class="fw-bold text-success">${{ number_format($shipment->amount_paid, 2) }}</div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="text-muted small">Balance Due</div>
                            <div class="fw-bold text-danger">${{ number_format($balanceDue, 2) }}</div>
                        </div>
                    </div>

                    @if ($balanceDue > 0)
                        <form method="POST" action="{{ route('public-shipment-pay', $shipment->public_view_token) }}" class="row g-2 align-items-end mt-3">
                            @csrf
                            <div class="col-sm-4">
                                <label class="form-label text-muted small mb-1">Amount to pay (USD)</label>
                                <input type="number" name="amount" class="form-control"
                                       step="0.01" min="1" max="{{ number_format($balanceDue, 2, '.', '') }}"
                                       value="{{ old('amount', number_format($balanceDue, 2, '.', '')) }}" required>
                            </div>
                            <div class="col-sm-4">
                                <button type="submit" class="btn btn-primary">Pay with Paystack</button>
                            </div>
                            <div class="col-12">
                                <div class="form-text">You can pay any amount towards your balance, bit by bit, until it is cleared.</div>
                            </div>
                        </form>
                    @endif

                    @if ($shipment->payments->isNotEmpty())
                        <h5 class="mt-4 mb-2">Transactions</h5>
                        <div class="table-responsive">
                        <table class="table mt-1">
                            <thead>
                                <tr><th>Reference</th><th>Method</th><th>Amount</th><th>Balance After</th><th>Date</th><th></th></tr>
                            </thead>
                            <tbody>
                                @php
                                    $ordered = $shipment->payments->sortBy(fn ($p) => $p->paid_on ?? $p->created_at)->values();
                                    $running = 0;
                                @endphp
                                @foreach ($ordered as $payment)
                                    @php
                                        $running += (float) $payment->amount;
                                        $balanceAfter = max(0, (float) $shipment->total - $running);
                                    @endphp
                                    <tr>
                                        <td>{{ $payment->payment_ref }}</td>
                                        <td>{{ $payment->paying_method }}</td>
                                        <td>${{ number_format((float) $payment->amount, 2) }}</td>
                                        <td>${{ number_format($balanceAfter, 2) }}</td>
                                        <td>{{ $payment->paid_on ? \Carbon\Carbon::parse($payment->paid_on)->format('M d, Y') : 'N/A' }}</td>
                                        <td><a href="{{ route('payment-receipt', $payment->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm">Receipt</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    @endif

                    <div class="mt-3 d-flex gap-2">
                        <a href="{{ route('shipping-invoice', $shipment->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm">View Invoice</a>
                        <a href="{{ route('shipping-receipt', $shipment->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm">View Receipt</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Receivers & Items --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="service-item p-4">
                    <h4 class="mb-3">Receivers & Items</h4>
                    @forelse ($shipment->receivers as $receiver)
                        <div class="mb-3">
                            <div class="fw-bold">{{ $receiver->receiver_name }}</div>
                            <div class="text-muted small mb-2">{{ $receiver->address }}</div>
                            <table class="table table-sm">
                                <thead>
                                    <tr><th>Product</th><th>Qty</th><th>Value</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($receiver->items as $item)
                                        <tr>
                                            <td>{{ $item->product?->name ?? 'N/A' }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>${{ number_format($item->item_cost, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @empty
                        <p class="text-muted">No receiver details on this shipment yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Status Timeline --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="service-item p-4">
                    <h4 class="mb-3">Status Timeline</h4>
                    @forelse ($shipment->statusupdate as $update)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <div>
                                <span class="fw-bold">{{ ucfirst($update->status) }}</span>
                                @if ($update->location)
                                    <span class="text-muted">— {{ $update->location }}</span>
                                @endif
                                @if ($update->remarks)
                                    <div class="text-muted small">{{ $update->remarks }}</div>
                                @endif
                            </div>
                            <div class="text-muted small text-nowrap">
                                {{ $update->updated_at ? \Carbon\Carbon::parse($update->updated_at)->format('M d, Y g:i A') : '' }}
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No status history recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Photos & Updates --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="service-item p-4">
                    <h4 class="mb-3">Photos & Updates</h4>
                    @php $mediaByStage = $shipment->media->groupBy('stage'); @endphp
                    @forelse (\App\Models\ShipmentMedia::STAGES as $stage => $label)
                        @if ($mediaByStage->has($stage))
                            <div class="mb-3">
                                <div class="fw-bold mb-2">{{ $label }}</div>
                                <div class="row">
                                    @foreach ($mediaByStage[$stage] as $item)
                                        <div class="col-md-3 col-6 mb-3">
                                            @if ($item->type === 'image')
                                                <img src="{{ asset('storage/' . $item->file_path) }}" class="img-fluid rounded" style="height:150px;width:100%;object-fit:cover;">
                                            @else
                                                <video src="{{ asset('storage/' . $item->file_path) }}" controls class="img-fluid rounded" style="height:150px;width:100%;"></video>
                                            @endif
                                            @if ($item->caption)
                                                <div class="text-muted small mt-1">{{ $item->caption }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @empty
                    @endforelse
                    @if ($shipment->media->isEmpty())
                        <p class="text-muted">No photos or videos have been uploaded for this shipment yet.</p>
                    @endif
                </div>
            </div>
        </div>

    </div>

</section>

@endsection
