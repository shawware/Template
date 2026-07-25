@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="bg-surface border border-edge rounded-lg shadow-sm p-6">
    <h1 class="text-heading text-xl font-semibold mb-2">Welcome{{ auth()->user() ? ', '.auth()->user()->name : '' }}</h1>
    <p class="text-secondary">This is the template home page — replace it with the app's real landing content.</p>
</div>
@endsection
