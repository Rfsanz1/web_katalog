<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kledo API Access Token
    |--------------------------------------------------------------------------
    | Static Bearer token generated from your Kledo account dashboard.
    | Set KLEDO_ACCESS_TOKEN in your .env file.
    | When this token expires, generate a new one from Kledo and update .env.
    */
    'access_token' => env('KLEDO_ACCESS_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Kledo API Base URL
    |--------------------------------------------------------------------------
    | For a custom sub-domain (e.g. gentongmas.api.kledo.com), override via
    | KLEDO_API_BASE_URL in your .env.
    */
    'api_base_url' => env('KLEDO_API_BASE_URL', 'https://app.kledo.com/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Invoice Due Days
    |--------------------------------------------------------------------------
    | Number of days after the order date that the Kledo invoice becomes due.
    */
    'due_days' => (int) env('KLEDO_DUE_DAYS', 30),
];
