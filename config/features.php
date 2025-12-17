<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Control which features are enabled in the application.
    |
    */

    'new_dashboard' => env('FEATURE_NEW_DASHBOARD', true),
    'gamification' => env('FEATURE_GAMIFICATION', true),
];

