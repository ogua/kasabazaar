@extends('errors.layout')

@section('code', '419')
@section('title', 'Page expired')
@section('heading', 'Page expired')
@section('message', 'Your session timed out for security. Go back, reload the page and try again.')

@section('actions')
    <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center rounded-sm font-semibold text-sm px-4 py-2.5 bg-navy-900 text-white hover:bg-navy-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent">Go back and retry</a>
@endsection
