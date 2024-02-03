<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\ApiKey;
use App\Models\Country;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ApiController extends Controller
{
    public function get()
    {
        return new UserResource(User::first());
    }

    public function getWithCache()
    {
        //кешируется запрос на сутки
        return new UserResource(Cache::remember('user', 60 * 60 * 24, function () {
            return new UserResource(User::first());
        }));
    }

    public function all()
    {
        return new UserCollection(User::all());
    }

}
