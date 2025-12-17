<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send verification code via WhatsApp
     */
    public function sendVerificationCode(string $phoneNumber, string $code): array
    {
        $provider = config('services.whatsapp.provider', env('WHATSAPP_PROVIDER', 'custom'));
        $isLocal = app()->environment('local');
        
        try {
            // Format phone number for display and API
            $formattedPhoneDisplay = $this->formatPhoneNumber($phoneNumber);
            $formattedPhoneApi = $this->formatPhoneNumberForApi($phoneNumber);
        } catch (\Exception $e) {
            Log::error('WhatsApp phone number formatting failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
            
            // In local mode, still return success with test mode
            if ($isLocal) {
                return [
                    'success' => true,
                    'code' => $code,
                    'test_mode' => true,
                    'local_mode' => true,
                ];
            }
            
            return ['success' => false, 'code' => null, 'test_mode' => false, 'error' => 'Invalid phone number format'];
        }
        
        $message = "Your Addy Business verification code is: {$code}\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this code, please ignore this message.";
        
        try {
            $success = false;
            $useRealWhatsApp = false;
            
            // Check if real WhatsApp provider is configured
            if ($provider === 'twilio') {
                $accountSid = config('services.twilio.account_sid', env('TWILIO_ACCOUNT_SID'));
                $authToken = config('services.twilio.auth_token', env('TWILIO_AUTH_TOKEN'));
                $from = config('services.twilio.whatsapp_from', env('TWILIO_WHATSAPP_FROM'));
                $useRealWhatsApp = !empty($accountSid) && !empty($authToken) && !empty($from);
            } elseif ($provider === 'meta') {
                $apiUrl = config('services.whatsapp.api_url', env('WHATSAPP_API_URL'));
                $apiKey = config('services.whatsapp.api_key', env('WHATSAPP_API_KEY'));
                $phoneId = config('services.whatsapp.phone_id', env('WHATSAPP_PHONE_ID'));
                $useRealWhatsApp = !empty($apiUrl) && !empty($apiKey) && !empty($phoneId);
            }
            
            // If real WhatsApp is configured, use it (even in local environment)
            if ($useRealWhatsApp) {
                switch ($provider) {
                    case 'twilio':
                        $success = $this->sendViaTwilio($formattedPhoneApi, $message, $formattedPhoneDisplay);
                        break;
                    case 'meta':
                        $success = $this->sendViaMeta($formattedPhoneApi, $message, $formattedPhoneDisplay);
                        break;
                }
                
                if ($success) {
                    Log::info('WhatsApp message sent successfully', [
                        'formatted_phone' => $formattedPhoneDisplay,
                        'provider' => $provider,
                    ]);
                    return ['success' => true, 'code' => null, 'test_mode' => false];
                } else {
                    // If sending failed, log and fall through to test mode if in local
                    Log::warning('WhatsApp sending failed, falling back to test mode', [
                        'formatted_phone' => $formattedPhoneDisplay,
                        'provider' => $provider,
                        'is_local' => $isLocal,
                    ]);
                    // In production, return error if sending failed
                    if (!$isLocal) {
                        return ['success' => false, 'code' => null, 'test_mode' => false, 'error' => 'Failed to send WhatsApp message. Please try again.'];
                    }
                }
            }
            
            // Fallback to local/test mode only if WhatsApp is not configured or sending failed in local environment
            if (!$useRealWhatsApp || ($isLocal && !$success)) {
                Log::info('Using local WhatsApp service (test mode)', [
                    'formatted_phone' => $formattedPhoneDisplay,
                    'code' => $code,
                    'provider' => $provider,
                    'is_local' => $isLocal,
                    'use_real_whatsapp' => $useRealWhatsApp,
                ]);
                return [
                    'success' => true,
                    'code' => $code,
                    'test_mode' => true,
                    'local_mode' => $isLocal,
                ];
            }
            
            // This should rarely be reached, but handle it just in case
            Log::warning('WhatsApp service failed and not in local mode', [
                'formatted_phone' => $formattedPhoneDisplay,
                'provider' => $provider,
                'is_local' => $isLocal,
            ]);
            
            return ['success' => false, 'code' => null, 'test_mode' => false, 'error' => 'WhatsApp service not configured'];
            
        } catch (\Exception $e) {
            Log::error('WhatsApp verification code send failed', [
                'phone' => $formattedPhoneDisplay ?? $phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Always fall back to test mode in local environment, even on exceptions
            if ($isLocal) {
                Log::info('Falling back to test mode after exception in local environment', [
                    'code' => $code,
                ]);
                return [
                    'success' => true,
                    'code' => $code,
                    'test_mode' => true,
                    'local_mode' => true,
                ];
            }
            
            return ['success' => false, 'code' => null, 'test_mode' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a general WhatsApp message
     */
    public function sendMessage(string $phoneNumber, string $message): array
    {
        $provider = config('services.whatsapp.provider', env('WHATSAPP_PROVIDER', 'custom'));
        $isLocal = app()->environment('local');
        
        $formattedPhoneDisplay = $this->formatPhoneNumber($phoneNumber);
        $formattedPhoneApi = $this->formatPhoneNumberForApi($phoneNumber);
        
        try {
            $success = false;
            $useRealWhatsApp = false;
            
            if ($provider === 'twilio') {
                $accountSid = config('services.twilio.account_sid', env('TWILIO_ACCOUNT_SID'));
                $authToken = config('services.twilio.auth_token', env('TWILIO_AUTH_TOKEN'));
                $from = config('services.twilio.whatsapp_from', env('TWILIO_WHATSAPP_FROM'));
                $useRealWhatsApp = !empty($accountSid) && !empty($authToken) && !empty($from);
            } elseif ($provider === 'meta') {
                $apiUrl = config('services.whatsapp.api_url', env('WHATSAPP_API_URL'));
                $apiKey = config('services.whatsapp.api_key', env('WHATSAPP_API_KEY'));
                $phoneId = config('services.whatsapp.phone_id', env('WHATSAPP_PHONE_ID'));
                $useRealWhatsApp = !empty($apiUrl) && !empty($apiKey) && !empty($phoneId);
            }
            
            if ($useRealWhatsApp) {
                switch ($provider) {
                    case 'twilio':
                        $success = $this->sendViaTwilio($formattedPhoneApi, $message, $formattedPhoneDisplay);
                        break;
                    case 'meta':
                        $success = $this->sendViaMeta($formattedPhoneApi, $message, $formattedPhoneDisplay);
                        break;
                }
                
                if ($success) {
                    return ['success' => true, 'message' => null, 'test_mode' => false];
                }
            }
            
            if ($isLocal || !$useRealWhatsApp) {
                Log::info('Using local WhatsApp service', [
                    'formatted_phone' => $formattedPhoneDisplay,
                    'message' => substr($message, 0, 50) . '...',
                ]);
                return [
                    'success' => true,
                    'message' => $message,
                    'test_mode' => true,
                    'local_mode' => $isLocal,
                ];
            }
            
            return ['success' => false, 'message' => null, 'test_mode' => false];
            
        } catch (\Exception $e) {
            Log::error('WhatsApp message send failed', [
                'phone' => $formattedPhoneDisplay,
                'error' => $e->getMessage(),
            ]);
            
            if ($isLocal) {
                return [
                    'success' => true,
                    'message' => $message,
                    'test_mode' => true,
                    'local_mode' => true,
                ];
            }
            
            return ['success' => false, 'message' => null, 'test_mode' => false];
        }
    }

    /**
     * Send via Twilio WhatsApp API
     */
    protected function sendViaTwilio(string $phoneNumber, string $message, string $formattedPhone): bool
    {
        $accountSid = config('services.twilio.account_sid', env('TWILIO_ACCOUNT_SID'));
        $authToken = config('services.twilio.auth_token', env('TWILIO_AUTH_TOKEN'));
        $from = config('services.twilio.whatsapp_from', env('TWILIO_WHATSAPP_FROM'));
        
        if (!$accountSid || !$authToken || !$from) {
            Log::error('Twilio credentials not configured');
            return false;
        }
        
        try {
            // Ensure message is properly formatted - WhatsApp needs clean URLs for link detection
            $cleanMessage = trim($message);
            
            $response = Http::withOptions([
                'verify' => env('APP_ENV') === 'production',
            ])
            ->withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $from,
                'To' => 'whatsapp:+' . $phoneNumber,
                'Body' => $cleanMessage,
            ]);
            
            if (!$response->successful()) {
                $errorBody = $response->json() ?? $response->body();
                Log::error('Twilio API error', [
                    'status' => $response->status(),
                    'body' => $errorBody,
                    'phone' => $formattedPhone,
                ]);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Twilio API exception', [
                'error' => $e->getMessage(),
                'phone' => $formattedPhone,
            ]);
            return false;
        }
    }

    /**
     * Send via Meta WhatsApp Business API
     */
    protected function sendViaMeta(string $phoneNumber, string $message, string $formattedPhone): bool
    {
        $apiUrl = config('services.whatsapp.api_url', env('WHATSAPP_API_URL'));
        $apiKey = config('services.whatsapp.api_key', env('WHATSAPP_API_KEY'));
        $phoneId = config('services.whatsapp.phone_id', env('WHATSAPP_PHONE_ID'));
        
        if (!$apiUrl || !$apiKey || !$phoneId) {
            Log::error('Meta WhatsApp credentials not configured');
            return false;
        }
        
        try {
            $response = Http::withToken($apiKey)
                ->post("{$apiUrl}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $phoneNumber,
                    'type' => 'text',
                    'text' => [
                        'body' => $message,
                    ],
                ]);
            
            if (!$response->successful()) {
                Log::error('Meta WhatsApp API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'phone' => $formattedPhone,
                ]);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Meta WhatsApp API exception', [
                'error' => $e->getMessage(),
                'phone' => $formattedPhone,
            ]);
            return false;
        }
    }

    /**
     * Format phone number to include country code with spaces (e.g., "+260 973 660 337")
     */
    public function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If it doesn't start with a country code, assume Zambia (+260)
        if (!preg_match('/^260/', $phoneNumber)) {
            $phoneNumber = ltrim($phoneNumber, '0');
            $phoneNumber = '260' . $phoneNumber;
        }
        
        // Format with spaces: +260 XXX XXX XXX
        $countryCode = substr($phoneNumber, 0, 3);
        $number = substr($phoneNumber, 3);
        
        if (strlen($number) == 9) {
            $formatted = '+' . $countryCode . ' ' . 
                        substr($number, 0, 3) . ' ' . 
                        substr($number, 3, 3) . ' ' . 
                        substr($number, 6, 3);
        } else {
            $formatted = '+' . $countryCode . ' ' . chunk_split($number, 3, ' ');
            $formatted = rtrim($formatted);
        }
        
        return $formatted;
    }

    /**
     * Format phone number for API calls (numeric only, no spaces)
     */
    public function formatPhoneNumberForApi(string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If it doesn't start with a country code, assume Zambia (+260)
        if (!preg_match('/^260/', $phoneNumber)) {
            $phoneNumber = ltrim($phoneNumber, '0');
            $phoneNumber = '260' . $phoneNumber;
        }
        
        return $phoneNumber;
    }
}

