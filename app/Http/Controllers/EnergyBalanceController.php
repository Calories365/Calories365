<?php

namespace App\Http\Controllers;

use App\Services\EnergyBalance;
use Illuminate\Http\JsonResponse;

/**
 * The real daily expenditure the diary measured (EnergyBalance), and taking
 * the norm it gives for the user's goal as the calorie limit.
 */
class EnergyBalanceController extends Controller
{
    public function __construct(private EnergyBalance $balance) {}

    public function show(): JsonResponse
    {
        return response()->json($this->balance->estimate((int) auth()->id()) + [
            'calories_limit' => auth()->user()->calories_limit,
        ]);
    }

    /**
     * Make the measured norm the calorie limit of the diary.
     */
    public function apply(): JsonResponse
    {
        $estimate = $this->balance->estimate((int) auth()->id());

        if ($estimate['status'] !== 'ready') {
            return response()->json(['message' => 'not_enough_data'], 422);
        }

        $user = auth()->user();
        $user->calories_limit = $estimate['norm'];
        $user->save();

        return response()->json($estimate + ['calories_limit' => $user->calories_limit]);
    }
}
