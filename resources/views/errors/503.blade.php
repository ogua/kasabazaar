@extends('errors.layout')

@section('code', '503')
@section('title', 'Back shortly')
@section('heading', 'Back shortly')
@section('message', 'The marketplace is briefly unavailable while we carry out maintenance. Please check back in a few minutes.')

@section('actions')
    <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-sm font-semibold text-sm px-4 py-2.5 bg-navy-900 text-white hover:bg-navy-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Try again</a>
@endsection
