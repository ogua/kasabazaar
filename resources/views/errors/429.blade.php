@extends('errors.layout')

@section('code', '429')
@section('title', 'Too many requests')
@section('heading', 'Too many requests')
@section('message', 'You’ve made a lot of requests in a short time. Please wait a minute, then try again.')

@section('actions')
    <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-sm font-semibold text-sm px-4 py-2.5 bg-navy-900 text-white hover:bg-navy-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Go to homepage</a>
@endsection
