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
        $merchantAccount = config('services.wayforpay.merchant', 'test_merch_n1');
        $merchantSecret  = config('services.wayforpay.secret',  'flk3409refn54t54t*FNJRET');
        $merchantDomain  = config('services.wayforpay.domain',  'www.market.ua');

        $amount     = '10';
        $currency   = 'UAH';
        $orderDate  = time();
        $orderRef   = 'DH' . $orderDate . random_int(100, 999);

        $productName  = ['Підписка'];
        $productCount = ['1'];
        $productPrice = [$amount];

        $sigParts = array_merge(
            [$merchantAccount, $merchantDomain, $orderRef, $orderDate, $amount, $currency],
            $productName,
            $productCount,
            $productPrice
        );
        $merchantSignature = hash_hmac('md5', implode(';', $sigParts), $merchantSecret);

        Payment::create([
            'user_id'        => Auth::id(),
            'order_reference'=> $orderRef,
            'status'         => 'Pending',
            'signature'      => $merchantSignature,
        ]);

        $user = Auth::user();
        $clientName  = ($user->name ?? '');
        $clientEmail = $user->email;

        $fields = [
            'merchantAccount'    => $merchantAccount,
            'merchantAuthType'   => 'SimpleSignature',
            'merchantDomainName' => $merchantDomain,

            'orderReference' => $orderRef,
            'orderDate'      => $orderDate,
            'amount'         => $amount,
            'currency'       => $currency,

            'productName'    => $productName,
            'productCount'   => $productCount,
            'productPrice'   => $productPrice,
            'regularMode'    => 'daily',   // daily | weekly | monthly | yearly
            'regularAmount'  => $amount,
            'regularCount' => 2,
            'regularBehavior' => 'preset',

            'serviceUrl' => 'https://calculator.calories365.com/wayforpay/callback',
            'returnUrl'  => 'https://calculator.calories365.com/thank-you',

            'clientEmail'      => $clientEmail,
            'clientName'       => $clientName,

            'language'         => app()->getLocale(),
            'paymentSystems'    => 'card;googlePay;applePay;privat24',
            'merchantSignature' => $merchantSignature,
        ];

        return response()->json([
            'pay_url' => 'https://secure.wayforpay.com/pay',
            'fields'  => $fields,
        ]);
    }

    /**
     * WayForPay POST-callback
     */
    public function callback(Request $r)
    {
        \App\Models\Payment::processCallback($r->all());

        $status = 'accept';
        $time   = time();
        $sig    = hash_hmac(
            'md5',
            $r->orderReference.';'.$status.';'.$time,
            config('services.wayforpay.secret')
        );

        return response()->json([
            'orderReference' => $r->orderReference,
            'status'         => $status,
            'time'           => $time,
            'signature'      => $sig,
        ]);
    }

    public function cancelPremium(): JsonResponse
    {
        $user = Auth::user();

        $payment = Payment::where('user_id', $user->id)
            ->where('status', 'Approved')
            ->whereRaw("order_reference NOT LIKE '%_WFPREG-%'")
            ->orderBy('id')
            ->first();

        if (!$payment) {
            Log::error('Original payment not found for user', $user->id);
            return response()->json(['error' => 'Original payment not found'], 404);
        }

        $merchant = config('services.wayforpay.merchant');
        $secret   = config('services.wayforpay.secret');

        $payload = [
            'requestType'      => 'REMOVE',
            'merchantAccount'  => $merchant,
            'merchantPassword' => md5($secret),
            'orderReference'   => $payment->order_reference,
        ];

        $resp = Http::post('https://api.wayforpay.com/regularApi', $payload)
            ->json();

        if (($resp['reasonCode'] ?? null) !== 1100) {
            Log::error('Subscription did not remove for user', $user->id);
            return response()->json($resp, 422);
        }

        $payment->update(['status' => 'Deleted']);

//         $user->subscription()->update(['rule_status' => 'Suspended']);

        return response()->json(['message' => 'success']);
    }


}
