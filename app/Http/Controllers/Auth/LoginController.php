<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\LoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (! config('app.auth_enabled')) {
            return redirect('/');
        }

        return view('auth.login');
    }

    public function login(Request $request, LoginService $service): RedirectResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $service->attempt($data['email'], $data['password'], $request->ip());

        if ($result === 'invalid') {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        if ($result === 'suspended') {
            return back()->withErrors(['email' => 'Your account has been suspended. Please contact your system administrator.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function logout(Request $request, LoginService $service): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            $service->recordLogout($user);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/user/login');
    }
}
