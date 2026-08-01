@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="max-w-lg space-y-6">
    <div class="bg-surface border border-edge rounded-lg shadow-sm p-6">
        <h1 class="text-heading text-xl font-semibold mb-4">Profile</h1>
        <form method="POST" action="{{ url('/profile') }}" class="space-y-4">
            @csrf
            @method('PATCH')
            <div>
                <label for="name" class="block text-label text-sm mb-1">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                       class="w-full border border-edge rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-focus-ring">
            </div>
            <div>
                <label for="email" class="block text-label text-sm mb-1">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                       class="w-full border border-edge rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-focus-ring">
            </div>
            <button type="submit" class="bg-btn hover:bg-btn-hover text-white rounded px-4 py-2">Save</button>
        </form>
    </div>

    <div class="bg-surface border border-edge rounded-lg shadow-sm p-6">
        <h2 class="text-heading text-lg font-semibold mb-4">Password</h2>
        <form method="POST" action="{{ url('/profile/password') }}" class="space-y-4">
            @csrf
            @method('PATCH')
            @if ($user->hasPassword())
                <div>
                    <label for="current_password" class="block text-label text-sm mb-1">Current password</label>
                    <input id="current_password" name="current_password" type="password"
                           class="w-full border border-edge rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-focus-ring">
                </div>
            @else
                <p class="text-subtle text-sm">Your account signs in via Google only — set a password below for a fallback login.</p>
            @endif
            <div>
                <label for="password" class="block text-label text-sm mb-1">New password</label>
                <input id="password" name="password" type="password" required
                       class="w-full border border-edge rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-focus-ring">
            </div>
            <div>
                <label for="password_confirmation" class="block text-label text-sm mb-1">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="w-full border border-edge rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-focus-ring">
            </div>
            <button type="submit" class="bg-btn hover:bg-btn-hover text-white rounded px-4 py-2">Update password</button>
        </form>
    </div>
</div>
@endsection
