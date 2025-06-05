<?php

namespace App\Http\Controllers;

use App\Jobs\SendPremiumStatusToBotPanelJob;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function generatePaymentLink(): JsonResponse
    {
        $user = Auth::user();

        $user->premium_until = Carbon::now()->addMonth();

        $user->save();

        SendPremiumStatusToBotPanelJob::dispatch($user);

        return response()->json([
            'premium_until' => $user->premium_until,
        ], 200);
    }
}
