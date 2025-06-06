<?php

namespace App\Http\Controllers;

use App\Jobs\SendNewUserToBotPanelJob;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): \Illuminate\Http\RedirectResponse
    {
        $googleUser = Socialite::driver('google')
            ->stateless()
            ->user();
        $user = $this->findOrCreateUser($googleUser, 'google');

        if (is_null($user->email_verified_at)) {
            $user->email_verified_at = now();
            $user->save();
        }

        Auth::login($user, true);

        return redirect('/');
    }

    protected function findOrCreateUser($socialUser, $driver)
    {
        $email = $socialUser->getEmail();

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $email,
                'password' => bcrypt(\Str::random(16)),
            ]);

            SendNewUserToBotPanelJob::dispatch($user);

        }

        return $user;
    }
}
