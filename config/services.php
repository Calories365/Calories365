<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
    ],
    'telegram_bot_url' => env('TELEGRAM_BOT_URL', 'https://t.me/Calories365Test_bot'),
    'academic_telegram_bot_url' => env('ACADEMIC_TELEGRAM_BOT_URL', 'https://t.me/Calories365KNU_bot'),
    'bot_api_key' => env('BOT_API_KEY', ''),

    'bot_panel' => [
        'url' => env('BOT_PANEL_URL'),
        'key' => env('BOT_API_KEY'),
        'host' => env('BOT_HOST'),
    ],

    'openai' => [
        // One key per interface language; "ua" uses the _UK one, other locales the _RU one.
        'keys' => [
            'ua' => env('OPENAI_API_KEY_UK'),
            'ru' => env('OPENAI_API_KEY_RU'),
            'en' => env('OPENAI_API_KEY_EN'),
        ],
        'models' => [
            // What the user ate, from speech (photos later), and the diet feedback:
            // accuracy matters more than price.
            'meal_analysis' => env('OPENAI_MEAL_ANALYSIS_MODEL', 'gpt-6.1-sol'),
            'feedback' => env('OPENAI_FEEDBACK_MODEL', 'gpt-6.1-sol'),
            // Nutrition facts of a single product: short and frequent.
            'product' => env('OPENAI_PRODUCT_MODEL', 'gpt-6-luna'),
            'transcription' => env('OPENAI_TRANSCRIPTION_MODEL', 'gpt-transcribe'),
        ],
        // The GPT-6 models reason before answering; low keeps the answer fast. They
        // reject temperature other than 1 and max_tokens.
        'reasoning_effort' => env('OPENAI_REASONING_EFFORT', 'low'),
    ],

    'telegram_mini_app' => [
        // Which bot signed the initData; null lets the panel try every active bot.
        'bot' => env('TELEGRAM_MINI_APP_BOT'),
        // Telegram gives us no email, so accounts opened from the Mini App get one here.
        'email_domain' => env('TELEGRAM_MINI_APP_EMAIL_DOMAIN', 'telegram.calories365.local'),
    ],
];
