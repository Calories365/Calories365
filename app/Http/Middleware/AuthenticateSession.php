<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession as SanctumAuthenticateSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sanctum 4.2.0 keeps the bare password hash in the session and compares it
 * with the bare hash, while the session guard of Laravel 12 stores it through
 * hashPasswordForCookie() on login. The two never matched, so every request
 * to the API after login ended in "Unauthenticated". Here both sides go through
 * hashPasswordForCookie().
 */
class AuthenticateSession extends SanctumAuthenticateSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession() || ! $request->user()) {
            return $next($request);
        }

        $guards = Collection::make(Arr::wrap(config('sanctum.guard')))
            ->mapWithKeys(fn ($guard) => [$guard => $this->auth->guard($guard)])
            ->filter(fn ($guard) => $guard instanceof SessionGuard);

        $shouldLogout = $guards->filter(
            fn ($guard, $driver) => $request->session()->has('password_hash_'.$driver)
        )->filter(
            fn ($guard, $driver) => $request->session()->get('password_hash_'.$driver) !==
                $guard->hashPasswordForCookie($request->user()->getAuthPassword())
        );

        if ($shouldLogout->isNotEmpty()) {
            $shouldLogout->each->logoutCurrentDevice();

            $request->session()->flush();

            throw new AuthenticationException('Unauthenticated.', [...$shouldLogout->keys()->all(), 'sanctum']);
        }

        return tap($next($request), function () use ($request, $guards) {
            if (! is_null($guard = $this->getFirstGuardWithUser($guards->keys()))) {
                $this->storePasswordHashInSession($request, $guard);
            }
        });
    }

    protected function storePasswordHashInSession($request, string $guard)
    {
        $sessionGuard = $this->auth->guard($guard);

        $request->session()->put([
            "password_hash_{$guard}" => $sessionGuard->hashPasswordForCookie($sessionGuard->user()->getAuthPassword()),
        ]);
    }
}
