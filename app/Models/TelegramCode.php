<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramCode extends Model
{
    use HasFactory;

    protected $table = 'telegram_codes';

    protected $fillable = [
        'user_id',
        'telegram_code',
        'telegram_code_expire_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
