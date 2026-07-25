<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! config('app.auth_enabled')) {
            return $next($request);
        }

        if (! auth()->user()?->hasPermission($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
