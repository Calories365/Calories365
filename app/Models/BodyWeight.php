<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The body weight a user told for a day of the user, see BodyLog.
 *
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $date
 * @property float $weight
 */
class BodyWeight extends Model
{
    /**
     * Weights a person may tell, in kg; anything else is a slip.
     */
    public const MIN = 25;

    public const MAX = 350;

    protected $fillable = ['user_id', 'date', 'weight'];

    protected $casts = [
        'date' => 'date',
        'weight' => 'float',
    ];

    /**
     * The user's weight on the day or the last one before it; the weight of the
     * calorie calculator when the user never told one.
     */
    public static function latestFor(int $userId, string $date): ?float
    {
        $told = self::query()->where('user_id', $userId)->where('date', '<=', $date)->orderByDesc('date')->value('weight');

        return $told !== null
            ? (float) $told
            : (UserResult::query()->where('user_id', $userId)->value('weight') ?? null);
    }
}
