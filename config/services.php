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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'lenco' => [
        'base_url' => env('LENCO_BASE_URL', 'https://api.lenco.co/access/v2'),
        'secret_key' => env('LENCO_SECRET_KEY'),
        'public_key' => env('LENCO_PUBLIC_KEY'),
        'api_name' => env('LENCO_API_NAME', 'Addy'),
    ],

    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'custom'),
        'api_url' => env('WHATSAPP_API_URL'),
        'api_key' => env('WHATSAPP_API_KEY'),
        'phone_id' => env('WHATSAPP_PHONE_ID'),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/login/callback'),
        'redirect_uri' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/login/callback'),
        'drive_folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
    ],

    'budgets' => [
        'jwt_secret' => env('BUDGETS_JWT_SECRET'),
        'base_url' => env('BUDGETS_BASE_URL'),
    ],

    'digitax' => [
        'jwt_secret' => env('DIGITAX_JWT_SECRET'),
        'base_url' => env('DIGITAX_BASE_URL'),
        // Path prefix for DigiTax Zambia API (zm.docs.digitax.tech). Adjust if your environment uses different paths.
        'api_path_prefix' => env('DIGITAX_API_PATH_PREFIX', '/api/v1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Penda Cloud SSO & Integration
    |--------------------------------------------------------------------------
    */
    'penda_sso' => [
        'base_url' => env('PENDA_SSO_URL', 'https://penda.cloud'),
        'client_id' => env('PENDA_CLIENT_ID'),
        'client_secret' => env('PENDA_CLIENT_SECRET'),
        'redirect_uri' => env('PENDA_REDIRECT_URI', env('APP_URL') . '/auth/penda/callback'),
        'logout_redirect' => env('PENDA_LOGOUT_REDIRECT', false),
        'service_token' => env('PENDA_SERVICE_TOKEN'), // For service-to-service API calls
        'app_id' => 'addy',
    ],

    'penda_cloud' => [
        'url' => env('PENDA_CLOUD_URL', 'https://penda.cloud'),
        'api_token' => env('PENDA_CLOUD_API_TOKEN'),
    ],

    'penda_apps' => [
        'addy' => [
            'name' => 'Addy Business',
            'url' => env('ADDY_URL', 'https://doaddy.com'),
            'icon' => '/images/apps/addy.svg',
        ],
        'sendrr' => [
            'name' => 'Sendrr',
            'url' => env('SENDRR_URL', 'https://sendrr.penda.cloud'),
            'icon' => '/images/apps/sendrr.svg',
        ],
        'projjo' => [
            'name' => 'Projjo',
            'url' => env('PROJJO_URL', 'https://projjo.penda.cloud'),
            'icon' => '/images/apps/projjo.svg',
        ],
        'herro' => [
            'name' => 'Herro',
            'url' => env('HERRO_URL', 'https://herro.penda.cloud'),
            'icon' => '/images/apps/herro.svg',
        ],
        'taxxy' => [
            'name' => 'Taxxy',
            'url' => env('TAXXY_URL', 'https://taxxy.penda.cloud'),
            'icon' => '/images/apps/taxxy.svg',
        ],
    ],

];
