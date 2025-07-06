<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Отдаём фронту pay_url + fields для автодебета (daily, 10 UAH).
     */
    public function prepareWayForPay(): JsonResponse
    {
        $merchantAccount = config('wayforpay.merchant', 'test_merch_n1');
        $merchantSecret = config('wayforpay.secret', 'flk3409refn54t54t*FNJRET');
        $merchantDomain = config('wayforpay.domain', 'www.market.ua');

        $amount = '10';
        $currency = 'UAH';
        $orderDate = time();
        $orderRef = 'DH'.$orderDate.random_int(100, 999);

        $productName = ['Підписка'];
        $productCount = ['1'];
        $productPrice = [$amount];

        $sigParts = array_merge(
            [$merchantAccount, $merchantDomain, $orderRef, $orderDate, $amount, $currency],
            $productName,
            $productCount,
            $productPrice
        );
        $merchantSignature = hash_hmac('md5', implode(';', $sigParts), $merchantSecret);
//        Log::info(implode(';', $sigParts));
        Payment::create([
            'user_id' => Auth::id(),
            'order_reference' => $orderRef,
            'status' => 'Pending',
            'signature' => $merchantSignature,
        ]);

        $user = Auth::user();
        $clientName = ($user->name ?? '');
        $clientEmail = $user->email;

        $fields = [
            'merchantAccount' => $merchantAccount,
            'merchantAuthType' => 'SimpleSignature',
            'merchantDomainName' => $merchantDomain,

            'orderReference' => $orderRef,
            'orderDate' => $orderDate,
            'amount' => $amount,
            'currency' => $currency,

            'productName' => $productName,
            'productCount' => $productCount,
            'productPrice' => $productPrice,
            'regularMode' => 'daily',   // daily | weekly | monthly | yearly
            'regularAmount' => $amount,
            'regularCount' => 2,
            'regularBehavior' => 'preset',

            'serviceUrl' => 'https://calculator.calories365.com/wayforpay/callback',
            'returnUrl' => 'https://calculator.calories365.com/thank-you',

            'clientEmail' => $clientEmail,
            'clientName' => $clientName,

            'language' => app()->getLocale(),
            'paymentSystems' => 'card;googlePay;applePay;privat24',
            'merchantSignature' => $merchantSignature,
        ];

        return response()->json([
            'pay_url' => 'https://secure.wayforpay.com/pay',
            'fields' => $fields,
        ]);
    }

    /**
     * WayForPay POST-callback
     */
    public function callback(Request $r)
    {
        try {
            $raw = $r->getContent();
            $data = json_decode($raw, true);

            if (! is_array($data)) {
                $data = json_decode(urldecode($raw), true);
            }

            if (! is_array($data)) {
                Log::error('WFP: invalid callback payload', [
                    'raw' => $raw,
                    'json_err' => json_last_error_msg(),
                ]);

                return response('Invalid payload', 400);
            }

            Log::info('WFP callback parsed', $data);

            Payment::processCallback($data);
            $orderReference = $data['orderReference'];
            $status = 'accept';
            $time = time();
            Log::info($orderReference.';'.$status.';'.$time);

            $sig = hash_hmac(
                'md5',
                $orderReference.';'.$status.';'.$time,
                config('wayforpay.secret', 'flk3409refn54t54t*FNJRET')
            );

            return response()->json([
                'orderReference' => $orderReference,
                'status' => $status,
                'time' => $time,
                'signature' => $sig,
            ]);
        } catch (\Throwable $e) {
            Log::error('WFP callback exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $orderReference = $data['orderReference'];

            $status = 'accept';
            $time = time();
            $sig = hash_hmac(
                'md5',
                $orderReference.';'.$status.';'.$time,
                config('wayforpay.secret', 'flk3409refn54t54t*FNJRET')
            );

            return response()->json([
                'orderReference' => $orderReference ?? null,
                'status' => $status,
                'time' => $time,
                'signature' => $sig,
            ]);
        }
    }

    public function cancelPremium(): JsonResponse
    {
        $user = Auth::user();

        $payment = Payment::query()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('status', 'Approved')
                    ->orWhere('status', 'Refunded');
            })
            ->whereRaw("order_reference NOT LIKE '%_WFPREG-%'")
            ->latest('id')
            ->first();

        if (! $payment) {
            $deletedPayment = Payment::query()
                ->where('user_id', $user->id)
                ->where('status', 'Deleted')
                ->whereRaw("order_reference NOT LIKE '%_WFPREG-%'")
                ->latest('id')
                ->first();

            if ($deletedPayment) {
                Log::error('Payment was deleted', ['user_id' => $user->id]);

                return response()->json(['message' => 'success']);
            }
            Log::error('Original WFP payment not found', ['user_id' => $user->id]);

            return response()->json(['error' => 'Original payment not found '], 404);
        }

        $payload = [
            'requestType' => 'REMOVE',
            'merchantAccount' => config('wayforpay.merchant', 'test_merch_n1'),
            'merchantPassword' => config('wayforpay.secret', 'd485396ae413eb60dc251b0899b261c2'),
            'orderReference' => $payment->order_reference,
        ];

        $response = Http::post('https://api.wayforpay.com/regularApi', $payload);

        Log::info('WFP REMOVE raw response', ['body' => $response->body()]);

        if ($response->failed()) {
            Log::error('WFP REMOVE network error', [
                'user_id' => $user->id,
                'http_code' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json(['error' => 'WayForPay request failed'], 502);
        }

        $respJson = $response->json();
        if (! in_array($respJson['reasonCode'] ?? null, [1100, 4100], true)) {
            Log::error('WFP REMOVE declined', [
                'user_id' => $user->id,
                'orderRef' => $payment->order_reference,
                'reasonCode' => $respJson['reasonCode'] ?? null,
                'reason' => $respJson['reason'] ?? null,
            ]);

            return response()->json($respJson, 422);
        }

        $payment->update(['status' => 'Deleted']);

        return response()->json(['message' => 'success']);
    }
}
