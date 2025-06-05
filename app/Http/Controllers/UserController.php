<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return array_merge(
            $user->only([
                'email',
                'name',
                'email_verified_at',
                'calories_limit',
                'id',
                'premium_until',
            ]),
            [
                'telegram_auth' => ! empty($user->telegram_id),
            ]
        );
    }

    public function showUsersInfoForBotMultiple(Request $request)
    {
        $idsString = $request->input('ids');
        $idsArray = explode(',', $idsString);
        $idsArray = array_filter($idsArray);

        $users = User::whereIn('id', $idsArray)->get();

        return $users->map(function ($user) {
            return [
                'calories_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'premium_until' => $user->premium_until,
            ];
        });

    }

    public function showAllUsers()
    {
        $users = User::all();

        return $users->map(function ($user) {
            return [
                'calories_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'premium_until' => $user->premium_until,
            ];
        });
    }
}
