<?php

if (!function_exists('redirect_to_penda_onboarding')) {
    /**
     * Redirect to Penda Cloud onboarding
     */
    function redirect_to_penda_onboarding()
    {
        $pendaCloudUrl = config('services.penda_sso.base_url', 'https://penda.cloud');
        return redirect($pendaCloudUrl . '/onboarding/step-1');
    }
}

