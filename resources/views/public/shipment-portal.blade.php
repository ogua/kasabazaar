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
        <div class="row mb-4">
            <div class="col-12">
                <div class="service-item p-4">
                    <h4 class="mb-3">Payment Summary</h4>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <div class="text-muted small">Total</div>
                            <div class="fw-bold">${{ number_format($shipment->total, 2) }}</div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="text-muted small">Paid</div>
                            <div class="fw-bold text-success">${{ number_format($shipment->paid, 2) }}</div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="text-muted small">Balance Due</div>
                            <div class="fw-bold text-danger">${{ number_format($shipment->total - $shipment->paid, 2) }}</div>
                        </div>
                        <div class="col-md-3 mb-2 d-flex align-items-end">
                            @if (($shipment->total - $shipment->paid) > 0)
                                <a href="{{ route('make-payment', ['record' => $shipment]) }}" target="_blank" class="btn btn-primary">Pay Now</a>
                            @endif
                        </div>
                    </div>

                    @if ($shipment->payments->isNotEmpty())
                        <table class="table mt-3">
                            <thead>
                                <tr><th>Reference</th><th>Method</th><th>Amount</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($shipment->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_ref }}</td>
                                        <td>{{ $payment->paying_method }}</td>
                                        <td>${{ number_format($payment->amount, 2) }}</td>
                                        <td>{{ $payment->paid_on ? \Carbon\Carbon::parse($payment->paid_on)->format('M d, Y') : 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
