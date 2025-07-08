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
        'active',
        'signature',
    ];

    public const STATUS_APPROVED = 'Approved';

    public const STATUS_REFUNDED = 'Refunded';

    public const STATUS_DELETED = 'Deleted';

    protected $casts = [
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeNonSubscription($q)
    {
        return $q->whereRaw("order_reference NOT LIKE '%_WFPREG-%'");
    }

    public function scopeActivePremium($q)
    {
        return $q->whereIn('status', [self::STATUS_APPROVED, self::STATUS_REFUNDED]);
    }

    public function scopeDeletedPremium($q)
    {
        return $q->where('status', self::STATUS_DELETED);
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
            'Removed' => 'Deleted',
            'Suspended' => 'Suspended',
        ];
        $status = $statusMap[$wfp['transactionStatus']] ?? 'Declined';
        $activeFlag = ! ($status === 'Deleted');
        $payment = self::where('order_reference', $wfp['orderReference'])->first();

        Log::info(print_r($wfp['orderReference'], true));
        if ($payment) {
            $payment->update(['status' => $status, 'signature' => $wfp['merchantSignature'], 'active' => $activeFlag]);
        } else {
            $userId = null;
            if (! empty($wfp['email'])) {
                $userId = \App\Models\User::where('email', $wfp['email'])->value('id');
            }
            if (! $userId) {
                $userId = 37;
            }
            $payment = self::create([
                'user_id' => $userId,
                'order_reference' => $wfp['orderReference'],
                'status' => $status,
                'signature' => $wfp['merchantSignature'],
            ]);
        }

        if ($status === 'Approved' && $payment->user) {
            $user = $payment->user;

            if (is_null($user->premium_until)) {
                $user->premium_until = Carbon::now()->addDay();
            } else {
                $user->premium_until = Carbon::parse($user->premium_until)->addDay();
            }

            $user->save();
            SendPremiumStatusToBotPanelJob::dispatch($payment->user);
        }

    }

    public static function latestActiveFor(int $userId): ?self
    {
        return self::query()
            ->where('user_id', $userId)
            ->nonSubscription()
            ->activePremium()
            ->latest('id')
            ->first();
    }

    public static function latestDeletedFor(int $userId): ?self
    {
        return self::query()
            ->where('user_id', $userId)
            ->nonSubscription()
            ->deletedPremium()
            ->latest('id')
            ->first();
    }
}
