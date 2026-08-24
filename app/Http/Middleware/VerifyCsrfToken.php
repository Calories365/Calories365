<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/wayforpay/callback-v2',
        '/thank-you',
        // Authenticated by Telegram's initData signature, and it is the call
        // that has to work before any CSRF cookie exists.
        'api/telegram/web-app-auth',
    ];
}
