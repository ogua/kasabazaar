@extends('errors.layout')

@section('code', '404')
@section('title', 'Page not found')
@section('heading', 'Page not found')
@section('message', 'The page you were looking for has moved, been removed or never existed. If you followed a product link, the item may no longer be listed.')

@section('actions')
    <a href="{{ url('/shop') }}" class="inline-flex items-center justify-center rounded-sm font-semibold text-sm px-4 py-2.5 bg-navy-900 text-white hover:bg-navy-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Browse products</a>
    <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-sm font-semibold text-sm px-4 py-2.5 bg-white text-navy-900 border border-navy-900 hover:bg-navy-900 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Go to homepage</a>
@endsection
