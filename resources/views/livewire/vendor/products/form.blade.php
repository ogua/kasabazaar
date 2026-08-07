<div class="stat-card" style="max-width:640px;">
    @if ($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    <form wire:submit.prevent="save">
        <div class="form-group">
            <label>Product Name</label>
            <input type="text" class="form-control" wire:model="name">
            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label>SKU</label>
            <input type="text" class="form-control" wire:model="sku">
        </div>
        <div class="form-group">
            <label>Category</label>
            <select class="form-control" wire:model="category_id">
                <option value="">— Select —</option>
                @foreach ($categories as $category)
                    <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea class="form-control" wire:model="description" rows="4"></textarea>
        </div>
        <div class="row">
            <div class="col-6 form-group">
                <label>Price (GHS)</label>
                <input type="number" step="0.01" class="form-control" wire:model="price_ghs">
                @error('price_ghs') <span class="text-danger">{{ $message }}</span> @enderror
            </div>
            <div class="col-6 form-group">
                <label>Discount Price (GHS, optional)</label>
                <input type="number" step="0.01" class="form-control" wire:model="discount_price_ghs">
            </div>
        </div>
        <div class="form-group">
            <label>Stock Quantity</label>
            <input type="number" class="form-control" wire:model="stock">
            @error('stock') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label><input type="checkbox" wire:model="is_active"> Active (visible in shop)</label>
        </div>

        <button type="submit" class="btn btn-dark">Save Product</button>
    </form>

    @if ($productId)
        <hr>
        <h4>Images</h4>
        <div class="d-flex flex-wrap mb-3" style="gap:8px;">
            @foreach ($images as $image)
                <img src="{{ $image['url'] }}" alt="" width="80" height="80" style="object-fit:cover;border-radius:4px;">
            @endforeach
        </div>
        <input type="file" wire:model="newImage">
        @if ($newImage)
            <button type="button" class="btn btn-outline-dark btn-sm mt-2" wire:click="uploadImage">Upload</button>
        @endif
    @else
        <p class="text-muted mt-4">Save the product first, then you can upload images.</p>
    @endif
</div>
