<div>
    <form wire:submit="track">
        <div class="row gy-3 align-items-end">
            <div class="col-md-8">
                <label for="tracking-input" class="form-label fw-semibold">Tracking Number / Reference</label>
                <input id="tracking-input" type="text" wire:model="query"
                    class="form-control form-control-lg @error('query') is-invalid @enderror"
                    placeholder="e.g. RD-20240001 or TRK123456"
                    autocomplete="off">
                @error('query') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-danger btn-lg w-100" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="track">
                        <i class="bi bi-search me-2"></i>Track Now
                    </span>
                    <span wire:loading wire:target="track" style="display:none;">
                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>Tracking...
                    </span>
                </button>
            </div>
        </div>
    </form>

    @if($searched)
        <div class="mt-4">
            @if($error)
                <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    <span>{{ $error }}</span>
                </div>
            @elseif($result)
                @php
                    $statusColors = [
                        'delivered' => ['bg' => 'success', 'icon' => 'bi-check-circle-fill'],
                        'cleared'   => ['bg' => 'success', 'icon' => 'bi-check-circle-fill'],
                        'shipped'   => ['bg' => 'info',    'icon' => 'bi-truck'],
                        'pickup'    => ['bg' => 'warning', 'icon' => 'bi-box-seam'],
                        'pending'   => ['bg' => 'secondary','icon' => 'bi-hourglass-split'],
                        'cancelled' => ['bg' => 'danger',  'icon' => 'bi-x-circle-fill'],
                    ];
                    $color = $statusColors[strtolower($result['status_raw'])] ?? ['bg' => 'secondary', 'icon' => 'bi-box'];
                @endphp

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-{{ $color['bg'] }} text-white d-flex align-items-center gap-2 py-3">
                        <i class="bi {{ $color['icon'] }} fs-4"></i>
                        <div>
                            <div class="fw-bold fs-5">{{ $result['status'] }}</div>
                            <small class="opacity-75">
                                @if($result['tracking_number']) Tracking #: {{ $result['tracking_number'] }} @endif
                                @if($result['reference']) &nbsp;|&nbsp; Ref: {{ $result['reference'] }} @endif
                            </small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row gy-3">
                            <div class="col-sm-6">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-geo-alt text-muted mt-1"></i>
                                    <div>
                                        <div class="text-muted small">Origin</div>
                                        <div class="fw-semibold">{{ $result['origin'] }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-geo-alt-fill text-muted mt-1"></i>
                                    <div>
                                        <div class="text-muted small">Destination</div>
                                        <div class="fw-semibold">{{ $result['destination'] }}</div>
                                    </div>
                                </div>
                            </div>
                            @if($result['shipped_at'])
                            <div class="col-sm-6">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-calendar-check text-muted mt-1"></i>
                                    <div>
                                        <div class="text-muted small">Shipped</div>
                                        <div class="fw-semibold">{{ $result['shipped_at'] }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($result['estimated_delivery'])
                            <div class="col-sm-6">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-calendar2-event text-muted mt-1"></i>
                                    <div>
                                        <div class="text-muted small">Estimated Delivery</div>
                                        <div class="fw-semibold">{{ $result['estimated_delivery'] }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($result['delivered_at'])
                            <div class="col-12">
                                <div class="alert alert-success mb-0 d-flex align-items-center gap-2 py-2">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>Delivered on <strong>{{ $result['delivered_at'] }}</strong></span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
