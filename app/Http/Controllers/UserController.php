<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
                'id'
            ]),
            [
                'telegram_auth' => !empty($user->telegram_id),
            ]
        );
    }

}
