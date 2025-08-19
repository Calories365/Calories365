<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;

class UserObserver
{
    public function created()
    {
        Cache::forget('user');
    }
}
