<?php

namespace App\Http\Controllers;

use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()]);
    }

    public function update(Request $request, ProfileService $service): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $service->updateProfile($request->user(), $data);

        return back()->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request, ProfileService $service): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => 'nullable|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $service->updatePassword($request->user(), $data['current_password'] ?? '', $data['password']);

        return back()->with('success', 'Password updated.');
    }
}
