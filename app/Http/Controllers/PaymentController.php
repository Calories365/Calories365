<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Jobs\SendPremiumStatusToBotPanelJob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function generatePaymentLink(): JsonResponse
    {
        $user = Auth::user();

        $user->premium_until = Carbon::now()->addMonth();

        $user->save();

        SendPremiumStatusToBotPanelJob::dispatch($user);

        return response()->json([
            'premium_until' => $user->premium_until
        ], 200);
    }
}
