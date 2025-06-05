<?php

namespace App\Actions\Fortify;

use App\Jobs\SendNewUserToBotPanelJob;
use App\Models\User;
use Illuminate\Support\Facades\Auth; // Добавляем фасад Auth
use Illuminate\Support\Facades\Hash;
// Для отладки (опционально)
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        Auth::login($user, $input['remember'] ?? false);

        SendNewUserToBotPanelJob::dispatch($user);

        return $user;
    }
}
