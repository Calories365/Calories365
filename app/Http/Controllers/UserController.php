<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request)
    {
        return $request->user()->only(['email', 'name', 'email_verified_at', 'calories_limit', 'id']);
    }
}
