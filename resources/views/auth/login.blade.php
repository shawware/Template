@extends('layouts.app')

@section('title', 'Log in')

@section('content')
<div class="max-w-sm mx-auto mt-12">
    <div class="bg-surface border border-edge rounded-lg shadow-sm p-6">
        <h1 class="text-heading text-xl font-semibold mb-6">Log in</h1>

        <a href="{{ url('/user/auth/google') }}"
           class="block w-full text-center bg-btn hover:bg-btn-hover text-white rounded px-4 py-2 mb-6">
            Continue with Google
        </a>

        <div class="flex items-center gap-3 text-subtle text-xs mb-6">
            <div class="flex-1 border-t border-divider"></div>
            or
            <div class="flex-1 border-t border-divider"></div>
        </div>

        <form method="POST" action="{{ url('/user/login') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-label text-sm mb-1">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="w-full border border-edge rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-focus-ring">
            </div>
            <div>
                <label for="password" class="block text-label text-sm mb-1">Password</label>
                <input id="password" name="password" type="password" required
                       class="w-full border border-edge rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-focus-ring">
            </div>
            <button type="submit" class="w-full bg-btn hover:bg-btn-hover text-white rounded px-4 py-2">
                Log in
            </button>
        </form>
    </div>
</div>
@endsection
