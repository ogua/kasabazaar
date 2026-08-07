<div class="storefront-toast-stack" style="position:fixed;top:90px;right:16px;z-index:1200;display:flex;flex-direction:column;gap:8px;max-width:320px;">
    @foreach ($messages as $toast)
        <div wire:key="toast-{{ $toast['id'] }}"
             x-data="{ show: true }" x-show="show" x-init="setTimeout(() => { show = false; $wire.dismiss('{{ $toast['id'] }}') }, 4000)"
             style="padding:12px 16px;border-radius:6px;color:#fff;font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,.2);background:{{ $toast['type'] === 'error' ? '#dc3545' : '#28a745' }};">
            {{ $toast['message'] }}
        </div>
    @endforeach
</div>
