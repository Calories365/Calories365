<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'user_id',
        'transaction_id',
        'amount',
        'transaction_date',
        'status',
        'recurring',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
