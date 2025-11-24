<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSwaggerAuthorized
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $allowed = [
            'maxim.kubichka@gmail.com',
            'test@test.com',
            'admin@example.com',
        ];

        if ($user && in_array($user->email, $allowed, true)) {
            return $next($request);
        }

        abort(403, 'Swagger documentation is restricted to authorized users.');
    }
}
