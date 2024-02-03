<?php

namespace App\Observers;

use Illuminate\Support\Facades\Cache;

class UserObserver
{
    //наблюдается модель user, если создатся новая запись, то кеш очищается
    public function created()
    {
        Cache::forget('user');
    }
}
