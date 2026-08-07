<div>
    <div class="d-flex justify-content-end mb-4">
        <a href="{{ route('vendor.products.create') }}" class="btn btn-dark">+ Add Product</a>
    </div>

    <div class="stat-card">
        <table class="table">
            <thead><tr><th>Product</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($products as $product)
                    <tr wire:key="vp-{{ $product['id'] }}">
                        <td>{{ $product['name'] }}</td>
                        <td>GHS {{ number_format($product['price_ghs'], 2) }}</td>
                        <td>{{ $product['stock'] }}</td>
                        <td>
                            <button type="button" class="btn btn-sm {{ $product['is_active'] ? 'btn-success' : 'btn-outline-secondary' }}"
                                    wire:click="toggleActive('{{ $product['id'] }}')">
                                {{ $product['is_active'] ? 'Active' : 'Inactive' }}
                            </button>
                        </td>
                        <td><a href="{{ route('vendor.products.edit', $product['id']) }}">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">You haven't added any products yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
