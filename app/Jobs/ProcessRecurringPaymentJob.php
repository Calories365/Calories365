<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessRecurringPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function handle()
    {
        if (empty($this->user->token)) {
            Log::warning('У пользователя нет токена, пропускаем рекуррентный платеж', [
                'user_id' => $this->user->id,
            ]);

            return;
        }

        $apiUrl = 'https://www.portmone.com.ua/r3/recurrent/';
        $payeeId = env('PORTMONE_PAYEE_ID');
        $login = env('PORTMONE_LOGIN');
        $password = env('PORTMONE_PASSWORD');

        if (empty($payeeId) || empty($login) || empty($password)) {
            Log::error('Конфигурация платежной системы не настроена корректно.', [
                'user_id' => $this->user->id,
            ]);

            return;
        }

        $orderId = 'recurring_'.Str::uuid();
        $amount = config('portmone.default_amount', 2.50); // Укажите вашу сумму

        $payload = [
            'method' => 'pay',
            'params' => [
                'login' => $login,
                'password' => $password,
                'payeeId' => $payeeId,
                'shopOrderNumber' => $orderId,
                'billAmount' => $amount,
                'description' => 'Рекуррентный платеж',
                'token' => $this->user->token,
                'billCurrency' => 'USD',
            ],
            'id' => '1',
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post($apiUrl, $payload);

        if ($response->failed()) {
            Log::error('Ошибка HTTP при рекуррентном платеже', [
                'user_id' => $this->user->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return;
        }

        $data = $response->json();
        Log::info('Ответ на рекуррентный платеж', $data);

        if (isset($data['result']['result']) && $data['result']['result'] === 'PAYED') {
            $currentPremiumUntil = $this->user->premium_until;
            DB::transaction(function () use ($orderId, $amount, $currentPremiumUntil) {
                Transaction::create([
                    'user_id' => $this->user->id,
                    'transaction_id' => $orderId,
                    'amount' => $amount,
                    'transaction_date' => now(),
                    'status' => 'success',
                ]);
                $nextPremiumUntil = $currentPremiumUntil->copy()->addMonth();
                $this->user->premium_until = $nextPremiumUntil;
                $this->user->save();
            });
            Log::info("Рекуррентный платеж успешно обработан для пользователя {$this->user->id}");

            SendPremiumStatusToBotPanelJob::dispatch($this->user);
        } else {
            $errorMsg = $data['result']['result'] ?? $data['errorMessage'] ?? 'Неизвестная ошибка';
            Log::warning('Платеж отклонён или ошибка', [
                'user_id' => $this->user->id,
                'error' => $errorMsg,
                'response' => $data,
            ]);
            Transaction::create([
                'user_id' => $this->user->id,
                'transaction_id' => $orderId,
                'amount' => $amount,
                'transaction_date' => now(),
                'status' => 'failed',
            ]);
        }
    }
}
