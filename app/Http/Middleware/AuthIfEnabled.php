<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthIfEnabled
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.auth_enabled')) {
            return $next($request);
        }

        if (! auth()->check()) {
            return redirect('/user/login');
        }

        return $next($request);
    }
}
