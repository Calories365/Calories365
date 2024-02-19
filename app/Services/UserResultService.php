<?php

namespace App\Services;

use App\Models\UserResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserResultService
{
    public function saveUserResults($user, $validatedData): UserResult
    {
        return DB::transaction(function () use ($user, $validatedData) {
            $user->calories_limit = $validatedData['dailyCalories'];
            $user->save();

            $validatedData['user_id'] = $user->id;

            $userResult = UserResult::updateOrCreate(
                ['user_id' => $user->id],
                $validatedData
            );
            Cache::forget("user_results_{$user->id}");

            return $userResult;
        });
    }
}
