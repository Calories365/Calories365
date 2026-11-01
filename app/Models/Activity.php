<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A physical activity of a day of the user and the calories it burned above
 * rest, see BodyLog.
 *
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property string|null $type
 * @property string $title
 * @property int $minutes
 * @property float $met
 * @property int $calories
 */
class Activity extends Model
{
    protected $fillable = ['user_id', 'date', 'started_at', 'type', 'title', 'minutes', 'met', 'calories'];

    protected $casts = [
        'date' => 'date',
        'started_at' => 'datetime',
        'met' => 'float',
        'minutes' => 'integer',
        'calories' => 'integer',
    ];

    /**
     * @return array{id: int, type: string|null, title: string, minutes: int, calories: int, time: string|null, date: string}
     */
    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'minutes' => $this->minutes,
            'calories' => $this->calories,
            'time' => $this->started_at?->format('H:i'),
            'date' => $this->date->toDateString(),
        ];
    }
}
