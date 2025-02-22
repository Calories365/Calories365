<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Portmone Configuration
    |--------------------------------------------------------------------------
    |
    | Настройки для интеграции с платежным шлюзом Portmone.
    |
    */

    'login' => env('PORTMONE_LOGIN'),
    'password' => env('PORTMONE_PASSWORD'),
    'payee_id' => env('PORTMONE_PAYEE_ID'),

    'api_url' => env('PORTMONE_API_URL'),

    'currency' => 'USD',
    'default_amount' => 2.5,

];
