<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserDataRequest;
use App\Http\Resources\CalculationResource;
use App\Models\User;
use App\Models\UserResult;
use App\Services\UserResultService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CalculationController extends Controller
{
    protected ?User $user;

    protected function getUser(): ?User
    {
        return $this->user ??= auth()->user();
    }

    public function store(UserResultService $userResultService, UserDataRequest $request): \Illuminate\Http\JsonResponse
    {
        $validatedData = $request->validated();

        $user = $this->getUser();

        try {
            $userResult = $userResultService->saveUserResults($user, $validatedData);
            $status = $userResult->wasRecentlyCreated ? 201 : 200;

            return response()->json('Result saved', $status);
        } catch (\Throwable $e) {
            return response()->json(['Error in CalculationController in store' => 'An error occurred'], 500);
        }
    }

    public function index(): CalculationResource|\Illuminate\Http\JsonResponse
    {
        $userId = $this->getUser()->id;
        $userResult = Cache::remember("user_results_{$userId}", 36000, function () use ($userId) {
            return UserResult::where('user_id', $userId)->firstOrFail();
        });

        return new CalculationResource($userResult);
    }
}
