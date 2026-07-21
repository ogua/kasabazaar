@if (session()->has('impersonate.original_id'))
    <div style="background:#A0043C;color:#fff;padding:.5rem 1rem;text-align:center;font-size:.875rem;">
        You are impersonating <strong>{{ auth()->user()->name }}</strong>.
        <a href="{{ route('impersonate.stop') }}" style="color:#fff;text-decoration:underline;">Stop impersonating</a>
    </div>
@endif
