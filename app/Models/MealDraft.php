<?php

namespace App\Models;

use App\Services\ProductQuality;
use Illuminate\Database\Eloquent\Model;

/**
 * The meal a user is putting together from voice, text and photos before it
 * goes to the diary, see MealDraftService.
 *
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MealDraftItem> $items
 */
class MealDraft extends Model
{
    /**
     * Hours without a change after which a draft is abandoned.
     */
    public const LIFETIME_HOURS = 3;

    protected $fillable = ['user_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<MealDraftItem, $this>
     */
    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MealDraftItem::class)->orderBy('position');
    }

    /**
     * The user's open draft; an abandoned one is deleted and null returned.
     */
    public static function openFor(int $userId): ?self
    {
        $draft = self::query()->where('user_id', $userId)->first();

        if ($draft && $draft->updated_at->lt(now()->subHours(self::LIFETIME_HOURS))) {
            $draft->delete();

            return null;
        }

        return $draft;
    }

    /**
     * The draft as the bot panel and the site show it: items numbered from 1,
     * nutrition facts per 100 g and per portion, and the total of the meal.
     * The total's sugars and glycemic load count the items whose products have
     * them, and are null when none has.
     *
     * @return array{items: array<int, array<string, mixed>>, total: array<string, mixed>}
     */
    public function toPayload(): array
    {
        $this->items->loadMissing('product');
        $items = $this->items->values()->map(static fn (MealDraftItem $item, int $index) => $item->toPayload($index + 1))->all();

        $total = [];
        foreach (MealDraftItem::FACTS as $fact) {
            $total[$fact] = round(array_sum(array_map(static fn (array $item) => $item['portion'][$fact], $items)), 1);
        }

        foreach (['sugars', 'glycemic_load'] as $measure) {
            $known = array_filter(array_map(static fn (array $item) => $item['quality'][$measure], $items), static fn ($value) => $value !== null);
            $total[$measure] = $known === [] ? null : round(array_sum($known), 1);
        }

        $total['glycemic_load_level'] = $total['glycemic_load'] === null ? null : ProductQuality::mealLoadLevel($total['glycemic_load']);

        return ['items' => $items, 'total' => $total];
    }
}
