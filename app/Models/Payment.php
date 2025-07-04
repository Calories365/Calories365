<?php

namespace App\Models;

use App\Jobs\SendPremiumStatusToBotPanelJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'order_reference',
        'status',
        'signature',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function processCallback(array $wfp): void
    {
        Log::info(print_r($wfp, true));
        $statusMap = [
            'Approved' => 'Approved',
            'Pending' => 'Pending',
            'InProcessing' => 'Pending',
            'Declined' => 'Declined',
            'Expired' => 'Expired',
            'Refunded' => 'Refunded',
            'Reversed' => 'Reversed',
        ];
        $status = $statusMap[$wfp['transactionStatus']] ?? 'Declined';

        $payment = self::where('order_reference', $wfp['orderReference'])->first();

        Log::info(print_r($wfp['orderReference'], true));
        if ($payment) {
            $payment->update(['status' => $status, 'signature' => $wfp['merchantSignature']]);
        } else {
            $userId = null;
            if (! empty($wfp['email'])) {
                $userId = \App\Models\User::where('email', $wfp['email'])->value('id');
            }

            $payment = self::create([
                'user_id' => $userId,
                'order_reference' => $wfp['orderReference'],
                'status' => $status,
                'signature' => $wfp['merchantSignature'],
            ]);
        }

        if ($status === 'Approved' && $payment->user) {
            Log::info($status);
            $payment->user->premium_until = Carbon::now()->addMonth();
            $payment->user->save();
        }

        SendPremiumStatusToBotPanelJob::dispatch($payment->user);
    }
}
