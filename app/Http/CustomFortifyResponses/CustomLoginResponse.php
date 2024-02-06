<?php

namespace App\Http\CustomFortifyResponses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class CustomLoginResponse implements LoginResponseContract
{
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return new JsonResponse([
                'two_factor' => false,
                'user' => [
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'email_verified_at' => $request->user()->email_verified_at,
                    'id' =>$request->user()->id,
                ]
            ]);
        }

        return redirect()->intended(Fortify::redirects('login'));
    }
}
