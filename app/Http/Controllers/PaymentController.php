<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Формирует данные для WayForPay c включённым regularOn=1 (token-схема).
     * Фронт получает pay_url + fields, собирает форму и отправляет.
     * Callback вернёт recToken, который дальше используем в regularApi.
     */
    public function prepareWayForPay(): JsonResponse
    {
        /* 1. Конфиг */
        $merchantAccount = config('services.wayforpay.merchant', 'test_merch_n1');
        $merchantSecret = config('services.wayforpay.secret', 'flk3409refn54t54t*FNJRET');
        $merchantDomain = config('services.wayforpay.domain', 'www.market.ua');

        /* 2. Заказ */
        $amount = '10';               // подписка 100 ₴
        $currency = 'UAH';
        $orderDate = time();
        $orderRef = 'DH'.$orderDate.random_int(100, 999);

        $productName = ['Підписка'];
        $productCount = ['1'];
        $productPrice = [$amount];

        /* 3. Подпись */
        $stringParts = array_merge(
            [
                $merchantAccount,
                $merchantDomain,
                $orderRef,
                $orderDate,
                $amount,
                $currency,
            ],
            $productName,
            $productCount,
            $productPrice
        );
        $merchantSignature = hash_hmac('md5', implode(';', $stringParts), $merchantSecret);

        /* 4. Поля формы */
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

            /* включаем токен-режим */
            'regularOn' => '1',
//            'serviceUrl' => secure_url('wayforpay/callback'),
            'serviceUrl' => 'https://calculator.calories365.com/wayforpay/callback',
//            'returnUrl'  => secure_url('thank-you'),
            'returnUrl'  => 'https://calculator.calories365.com/thank-you',
            /* при желании — список доступных кошельков */
            'paymentSystems' => 'card;googlePay;applePay;privat24',

            'merchantSignature' => $merchantSignature,
        ];

        /* 5. Отдаём во фронт */
        return response()->json([
            'pay_url' => 'https://secure.wayforpay.com/pay',
            'fields' => $fields,
        ]);
    }

    public function callback(Request $request)
    {
        // логируем чистый JSON
        \Log::info('WFP callback', $request->all());
        return response('OK');
    }
}
