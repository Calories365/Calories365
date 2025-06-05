<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'gender', 'birthYear', 'weight', 'height', 'goalWeight',
        'fat', 'activity', 'goal', 'dailyCalories', 'user_id',
        'checkboxActive',
    ];
}
