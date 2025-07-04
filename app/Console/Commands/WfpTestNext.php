<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WfpTestNext extends Command
{
    /** php artisan wfp:test-next */
    protected $signature   = 'wfp:test-next';
    protected $description = 'Однократное списание NEXT по recToken (WayForPay regularApi)';

    /* ПОДСТАВЬ СВОЁ ПРИ НУЖДЕ */
    private string $recToken  = '63ea3080-fc08-4cd2-b271-06e29cb36dc2';
    private string $amountUAH = '10.00';   // формат 10.00
    private string $currency  = 'UAH';
    /* ---------------------------------- */

    public function handle(): int
    {
        $merchant = config('services.wayforpay.merchant', 'test_merch_n1');
        $secret   = config('services.wayforpay.secret',  'flk3409refn54t54t*FNJRET');
        $domain   = config('services.wayforpay.domain',  'www.market.ua');

        $orderRef  = 'NEXT-' . now()->timestamp . '-' . Str::random(4);
        $orderDate = time();

        /* md5(secretKey) согласно докам */
        $merchantPassword = md5($secret);

        $payload = [
            'requestType'      => 'NEXT',                   // вместо STATUS
            'merchantAccount'  => $merchant,
            'merchantPassword' => md5($secret),
            'recToken'         => $this->recToken,
            'amount'           => $this->amountUAH,
            'currency'         => $this->currency,
        ];

        $resp = Http::post('https://api.wayforpay.com/regularApi', $payload)
            ->throw()
            ->json();

        Log::info('WFP NEXT test', $resp);
        $this->info(json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return 0;
    }
}
