<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleService;
use App\Services\Auth\LoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback(Request $request, GoogleService $service, LoginService $loginService): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception) {
            return redirect('/user/login')->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        try {
            $user = $service->handleCallback(
                $googleUser->getId(),
                $googleUser->getEmail(),
                $googleUser->getName() ?? $googleUser->getEmail(),
                $googleUser->getAvatar(),
            );
        } catch (ValidationException $e) {
            return redirect('/user/login')->withErrors($e->errors());
        }

        Auth::login($user);
        $loginService->recordLogin($user, $request->ip(), 'google');

        return redirect('/');
    }
}
