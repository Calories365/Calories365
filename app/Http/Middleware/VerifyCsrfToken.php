<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verif.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/portmone/success',
        '/portmone/failure',
    ];
}
