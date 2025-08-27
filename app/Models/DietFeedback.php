<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DietFeedback extends Model
{
    protected $table = 'diet_feedbacks';

    protected $fillable = [
        'user_id',
        'feedback_date',
        'part_of_day',
        'meals_signature',
        'feedback_text',
        'status',
    ];

    protected $casts = [
        'feedback_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function buildMealsSignature(Collection|array $meals): string
    {
        $pairs = ($meals instanceof Collection ? $meals : collect($meals))
            ->map(function ($item) {
                $foodId = is_array($item) ? ($item['food_id'] ?? null) : ($item->food_id ?? null);
                $qty = (int) (is_array($item) ? ($item['quantity'] ?? 0) : ($item->quantity ?? 0));

                return $foodId ? ['id' => (int) $foodId, 'g' => $qty] : null;
            })
            ->filter()
            ->groupBy('id')
            ->map(fn ($items) => (int) collect($items)->sum('g'))
            ->sortKeys()
            ->map(fn (int $g, int $id) => $id.':'.$g)
            ->values()
            ->implode('|');

        return hash('sha256', $pairs);
    }

    public static function findBySignature(int $userId, string $date, ?string $partOfDay, string $signature): ?self
    {
        return self::where('user_id', $userId)
            ->where('feedback_date', $date)
            ->when($partOfDay, fn ($q) => $q->where('part_of_day', $partOfDay),
                fn ($q) => $q->whereNull('part_of_day'))
            ->where('meals_signature', $signature)
            ->first();
    }

    public static function updateStatusBySignature(int $userId, string $date, ?string $partOfDay, string $signature, string $status): int
    {
        return self::where('user_id', $userId)
            ->where('feedback_date', $date)
            ->when($partOfDay, fn ($q) => $q->where('part_of_day', $partOfDay),
                fn ($q) => $q->whereNull('part_of_day'))
            ->where('meals_signature', $signature)
            ->update(['status' => $status]);
    }

    public static function upsertReadyBySignature(int $userId, string $date, ?string $partOfDay, string $signature, $feedback): self
    {
        $feedbackText = is_string($feedback) ? $feedback : json_encode($feedback, JSON_UNESCAPED_UNICODE);

        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'feedback_date' => $date,
                'part_of_day' => $partOfDay,
                'meals_signature' => $signature,
            ],
            [
                'feedback_text' => $feedbackText,
                'status' => 'ready',
            ]
        );
    }

    public static function findLatestByUserAndSignature(int $userId, string $signature, ?string $date = null, ?string $partOfDay = null): ?self
    {
        return self::where('user_id', $userId)
            ->when($date, fn ($q) => $q->where('feedback_date', $date))
            ->when(
                $partOfDay !== null,
                fn ($q) => $q->where('part_of_day', $partOfDay),
                fn ($q) => $q
                    ->when($date !== null, fn ($qq) => $qq->whereNull('part_of_day'))
            )
            ->where('meals_signature', $signature)
            ->latest('id')
            ->first();
    }

    public static function insertPendingIfMissing(int $userId, string $date, ?string $partOfDay, string $signature): void
    {
        DB::table('diet_feedbacks')->upsert(
            [[
                'user_id' => $userId,
                'feedback_date' => $date,
                'part_of_day' => $partOfDay,
                'meals_signature' => $signature,
                'status' => 'pending',
                'feedback_text' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['user_id', 'feedback_date', 'part_of_day', 'meals_signature'],
            ['updated_at' => DB::raw('updated_at')]
        );
    }
}
