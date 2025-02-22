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

class PortmoneController extends Controller
{
    public function generatePaymentLink(): JsonResponse
    {
        $user = Auth::user();

        try {
            $payeeId  = config('portmone.payee_id');
            $login    = config('portmone.login');
            $password = config('portmone.password');
            $apiUrl   = config('portmone.api_url');

            $orderId = $user->id . '_' . Str::random(10);
            $amount  = config('portmone.default_amount');
            $locale  = app()->getLocale();
            $currency = config('portmone.currency');

            Transaction::create([
                'user_id'          => $user->id,
                'transaction_id'   => $orderId,
                'amount'           => $amount,
                'transaction_date' => now(),
                'status'           => 'pending',
            ]);

            $data = [
                'payee_id'          => $payeeId,
                'login'             => $login,
                'password'          => $password,
                'shop_order_number' => $orderId,
                'bill_amount'       => $amount,
                'bill_currency'     => $currency,
                'description'       => __('portmone.CaloriesSubscription'),
                'success_url'       => route('portmone.success.payment'),
                'failure_url'       => route('portmone.failure.payment'),
                'lang'              => $locale,
            ];

            $query = http_build_query($data);
            $url   = "{$apiUrl}?{$query}";

            return response()->json([
                'portmone_url' => $url
            ], 200);

        } catch (\Throwable $th) {
            Log::error('Error generating Portmone link: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString(),
                'request_data' => request()->all(),
            ]);
            return response()->json([
                'errors' => ['Failed to generate payment link.']
            ], 500);
        }
    }

    public function successPayment(Request $request)
    {
        try {
            $orderId = $request->input('SHOPORDERNUMBER');
            $token = $request->input('TOKEN');

            $transaction = Transaction::where('transaction_id', $orderId)->first();
            if (!$transaction) {
                Log::error('Transaction not found for orderId: ' . $orderId, [
                    'request_data' => $request->all(),
                ]);
                return redirect()->away(url('/cabinet?payment=error'));
            }

            if ($transaction->status === 'pending') {
                DB::transaction(function () use ($transaction, $token) {
                    $user = $transaction->user;

                    if (!$user) {
                        throw new \Exception('User not found for transaction: ' . $transaction->id);
                    }

                    $user->premium_until = Carbon::now()->addMonth();
                    $user->token = $token;
                    $user->save();

                    $transaction->status = 'success';
                    $transaction->save();

                    SendPremiumStatusToBotPanelJob::dispatch($user);
                });
            }

            return redirect()->away(url('/cabinet?payment=success'));

        } catch (\Throwable $th) {
            Log::error('Error in successPayment: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return redirect()->away(url('/cabinet?payment=error'));
        }
    }

    /**
     * Processes a failed payment from Portmone.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function failedPayment(Request $request)
    {
        try {
            $orderId = $request->input('SHOPORDERNUMBER');

            if (!$orderId) {
                Log::warning('SHOPORDERNUMBER is missing in failedPayment request', [
                    'request_data' => $request->all(),
                ]);
                return redirect()->away(url('/cabinet?payment=error'));
            }

            $transaction = Transaction::where('transaction_id', $orderId)->first();
            if (!$transaction) {
                Log::error('Transaction not found for orderId: ' . $orderId, [
                    'request_data' => $request->all(),
                ]);
                return redirect()->away(url('/cabinet?payment=error'));
            }

            if ($transaction->status === 'pending') {
                DB::transaction(function () use ($transaction) {
                    $transaction->status = 'failed';
                    $transaction->save();
                });
            }

            return redirect()->away(url('/cabinet?payment=error'));

        } catch (\Throwable $th) {
            Log::error('Error in failedPayment: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            return redirect()->away(url('/cabinet?payment=error'));
        }
    }
}
