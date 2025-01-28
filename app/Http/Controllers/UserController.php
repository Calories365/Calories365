<?php

namespace App\Http\Controllers;

use App\Jobs\SendPremiumStatusToBotPanelJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                'id',
                'premium_until'
            ]),
            [
                'telegram_auth' => !empty($user->telegram_id),
            ]
        );
    }

    public function showUsersInfoForBotMultiple(Request $request)
    {
        $idsString = $request->input('ids');
        $idsArray = explode(',', $idsString);
        $idsArray = array_filter($idsArray);

        $users = User::whereIn('id', $idsArray)->get();
        return $users->map(function($user) {
            return [
                'calories_id' => $user->id,
                'email'       => $user->email,
                'name'        => $user->name,
                'premium_until' => $user->premium_until
            ];
        });

    }
    public function showAllUsers()
    {
        $users = User::all();

        return $users->map(function ($user) {
            return [
                'calories_id' => $user->id,
                'email'       => $user->email,
                'name'        => $user->name,
                'premium_until' => $user->premium_until
            ];
        });
    }

    public function buyPremium()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'errors' => ['Пользователь не аутентифицирован.']
            ], 401);
        }

        try {
            $user->premium_until = Carbon::now()->addMonth();
            $user->save();

           SendPremiumStatusToBotPanelJob::dispatch($user);

            return response()->json([
//                'premium_until' => $user->premium_until->format('d.m.Y H:i:s')
                'premium_until' => $user->premium_until
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => ['Не удалось обновить статус премиум.']
            ], 500);
        }
    }


}
