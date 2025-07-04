<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Отдаём фронту pay_url + fields для автодебета (daily, 10 UAH).
     */
    public function prepareWayForPay(): JsonResponse
    {
        /* 1. Конфиг */
        $merchantAccount = config('services.wayforpay.merchant', 'test_merch_n1');
        $merchantSecret  = config('services.wayforpay.secret',  'flk3409refn54t54t*FNJRET');
        $merchantDomain  = config('services.wayforpay.domain',  'www.market.ua');

        /* 2. Параметры заказа */
        $amount     = '10';      // 10 ₴ за сутки премиума
        $currency   = 'UAH';
        $orderDate  = time();
        $orderRef   = 'DH' . $orderDate . random_int(100, 999);

        $productName  = ['Підписка'];
        $productCount = ['1'];
        $productPrice = [$amount];

        /* 3. Подпись (по документации) */
        $sigParts = array_merge(
            [$merchantAccount, $merchantDomain, $orderRef, $orderDate, $amount, $currency],
            $productName,
            $productCount,
            $productPrice
        );
        $merchantSignature = hash_hmac('md5', implode(';', $sigParts), $merchantSecret);

        /* 4. Поля формы */
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

            /* ←–---   автосписание 1 раз в день   ---→ */
            'regularMode'    => 'daily',   // daily | weekly | monthly | yearly
            'regularAmount'  => $amount,   // сумма каждого авто-дебета
            'regularCount' => 10,
            'regularBehavior' => 'preset',
            // (dateNext можно не указывать — возьмётся +1 день от оплаты)

            'serviceUrl' => 'https://calculator.calories365.com/wayforpay/callback',
            'returnUrl'  => 'https://calculator.calories365.com/thank-you',

            'paymentSystems'    => 'card;googlePay;applePay;privat24',
            'merchantSignature' => $merchantSignature,
        ];

        return response()->json([
            'pay_url' => 'https://secure.wayforpay.com/pay',
            'fields'  => $fields,
        ]);
    }

    /**
     * WayForPay POST-callback — успех / ошибка любой транзакции.
     */
    public function callback(Request $r)
    {
        /* ваша бизнес-логика здесь — продлить premium, записать токен и т.п. */

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

}
