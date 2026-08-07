@extends('storefront.layouts.app')

@section('title', $title ?? config('app.name'))

@section('content')
    <div class="container py-8 text-center">
        <h1>{{ $title ?? 'Coming Soon' }}</h1>
        <p>This page is still being built.</p>
    </div>
@endsection
