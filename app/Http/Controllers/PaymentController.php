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
    public function prepareWayForPay(): JsonResponse
    {
        $merchantAccount = env('WFP_MERCHANT');
        $merchantSecret = env('WFP_SECRET');
        $merchantDomain = env('WFP_DOMAIN');

        $amount = env('WFP_PRODUCT_PRICE');
        $currency = 'UAH';
        $orderDate = time();
        $orderRef = 'DH'.$orderDate.random_int(100, 999);

        $productName = [env('WFP_PRODUCT_NAME')];
        $productCount = ['1'];
        $productPrice = [$amount];
        $regularMode = env('WFP_REGULAR_MODE');
        $regularCount = env('WFP_REGULAR_COUNT');
        $sigParts = array_merge(
            [$merchantAccount, $merchantDomain, $orderRef, $orderDate, $amount, $currency],
            $productName,
            $productCount,
            $productPrice
        );
        $merchantSignature = hash_hmac('md5', implode(';', $sigParts), $merchantSecret);
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
            'regularMode' => $regularMode,   // daily | weekly | monthly | yearly
            'regularAmount' => $amount,
            'regularCount' => $regularCount,
            'regularBehavior' => 'preset',

            'serviceUrl' => 'https://calculator.calories365.com/wayforpay/callback-v2',
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

    public function callback(Request $r): JsonResponse
    {
        $raw = $r->getContent();

        $data = json_decode($raw, true) ?: $r->all();

        $orderReference = $data['orderReference'];

        // temp answer
        if ($orderReference == 'DH1752836321212') {
            return $this->responseToWFPTMP($orderReference);
        }

        if (! $this->verifyWfpSignature($data)) {
            Log::warning('WFP invalid signature', [
                'orderReference' => $data['orderReference'] ?? null,
            ]);

            return $this->responseToWFP($orderReference, 'reject');
        }

        Log::info('WFP callback parsed', $data);

        try {
            Payment::processCallback($data);
        } catch (\Throwable $e) {
            Log::error('WFP callback exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $this->responseToWFP($orderReference);
    }

    public function cancelPremium(): JsonResponse
    {
        $userId = Auth::id();
        $payment = Payment::latestActiveFor($userId);

        if (! $payment) {
            if (Payment::latestDeletedFor($userId)) {
                Log::info('Payment already deleted', ['user_id' => $userId]);

                return response()->json(['message' => 'success']);
            }

            Log::warning('Original payment not found', ['user_id' => $userId]);

            return response()->json([
                'code' => 'PAYMENT_NOT_FOUND',
                'message' => 'Original payment not found',
            ], 404);
        }

        try {
            $wfpResponse = $this->sendRemoveRequestToWfp($payment->order_reference);
        } catch (\Throwable $e) {
            Log::error('WayForPay network error', [
                'user_id' => $userId,
                'order_reference' => $payment->order_reference,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'code' => 'WFP_NETWORK_ERROR',
                'message' => 'WayForPay request failed',
            ], 502);
        }

        if (! in_array($wfpResponse['reasonCode'] ?? null, [1100, 4100], true)) {
            Log::notice('WayForPay refused to cancel', [
                'user_id' => $userId,
                'order_reference' => $payment->order_reference,
                'reasonCode' => $wfpResponse['reasonCode'] ?? null,
            ]);

            return response()->json([
                'code' => 'CANCEL_NOT_ALLOWED',
                'message' => 'Unable to cancel payment',
            ], 422);
        }

        $payment->update(['status' => Payment::STATUS_DELETED, 'active' => false]);

        return response()->json(['message' => 'success']);
    }

    private function verifyWfpSignature(array $p): bool
    {
        $secret = env('WFP_SECRET');

        $parts = [
            $p['merchantAccount'] ?? '',
            $p['orderReference'] ?? '',
            $p['amount'] ?? '',
            $p['currency'] ?? '',
            $p['authCode'] ?? '',
            $p['cardPan'] ?? '',
            $p['transactionStatus'] ?? '',
            $p['reasonCode'] ?? '',
        ];

        $calc = hash_hmac('md5', implode(';', $parts), $secret);

        return hash_equals($calc, $p['merchantSignature'] ?? '');
    }

    private function responseToWFP(string $orderReference, $status = 'accept'): JsonResponse
    {
        $time = time();
        $signature = hash_hmac(
            'md5',
            "{$orderReference};{$status};{$time}",
            env('WFP_SECRET'),
            false
        );

        return response()->json([
            'orderReference' => $orderReference,
            'status' => $status,
            'time' => $time,
            'signature' => $signature,
        ]);
    }

    private function responseToWFPTMP(string $orderReference, $status = 'accept'): JsonResponse
    {
        $time = time();
        $signature = hash_hmac(
            'md5',
            "{$orderReference};{$status};{$time}",
            'flk3409refn54t54t*FNJRET',
            false
        );

        return response()->json([
            'orderReference' => $orderReference,
            'status' => $status,
            'time' => $time,
            'signature' => $signature,
        ]);
    }

    private function sendRemoveRequestToWfp(string $orderReference): array
    {
        $payload = [
            'requestType' => 'REMOVE',
            'merchantAccount' => env('WFP_MERCHANT'),
            'merchantPassword' => env('WFP_PASSWORD'),

            'orderReference' => $orderReference,
        ];

        try {
            $response = Http::timeout(15)
                ->post('https://api.wayforpay.com/regularApi', $payload);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Transport error: {$e->getMessage()}", 0, $e);
        }

        if ($response->failed()) {
            throw new \RuntimeException(
                "HTTP {$response->status()} {$response->body()}"
            );
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new \RuntimeException('Invalid JSON from WayForPay');
        }

        return $json;
    }
}
