<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // Ваша модель пользователя
use Illuminate\Http\RedirectResponse;

class SocialAuthController extends Controller
{
    /**
     * 1) Перенаправляем пользователя на Google
     */
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * 2) Обрабатываем callback от Google
     */
    public function handleGoogleCallback()
    {
        $socialUser = Socialite::driver('google')->stateless()->user();
        $user = $this->findOrCreateUser($socialUser, 'google');

        if (is_null($user->email_verified_at)) {
            $user->email_verified_at = now();
            $user->save();
        }

        Auth::login($user);

        return redirect('/');
    }

    /**
     * Вспомогательный метод:
     * Создать (или найти) локального User по данным из соцсети
     */
    protected function findOrCreateUser($socialUser, $driver)
    {
        $email = $socialUser->getEmail();

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name'  => $socialUser->getName(),
                'email' => $email,
                'password' => bcrypt(\Str::random(16)),
            ]);
        }

        return $user;
    }
}
