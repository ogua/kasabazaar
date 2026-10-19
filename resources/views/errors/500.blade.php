@extends('errors.layout')

@section('code', '500')
@section('title', 'Something went wrong')
@section('heading', 'Something went wrong')
@section('message', 'We hit an unexpected problem on our side. It’s been logged. Please try again in a moment.')

@section('actions')
    <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-sm font-semibold text-sm px-4 py-2.5 bg-navy-900 text-white hover:bg-navy-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Go to homepage</a>
    <a href="{{ url('/contact-us') }}" class="inline-flex items-center justify-center rounded-sm font-semibold text-sm px-4 py-2.5 bg-white text-navy-900 border border-navy-900 hover:bg-navy-900 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Contact support</a>
@endsection
