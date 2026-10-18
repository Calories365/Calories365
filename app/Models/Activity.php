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
 * @property string $title
 * @property int $minutes
 * @property float $met
 * @property int $calories
 */
class Activity extends Model
{
    protected $fillable = ['user_id', 'date', 'title', 'minutes', 'met', 'calories'];

    protected $casts = [
        'date' => 'date',
        'met' => 'float',
        'minutes' => 'integer',
        'calories' => 'integer',
    ];

    /**
     * @return array{id: int, title: string, minutes: int, calories: int}
     */
    public function toPayload(): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'minutes' => $this->minutes, 'calories' => $this->calories];
    }
}
