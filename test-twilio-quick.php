<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "========================================\n";
echo "Twilio WhatsApp Quick Test\n";
echo "========================================\n\n";

$accountSid = env('TWILIO_ACCOUNT_SID');
$authToken = env('TWILIO_AUTH_TOKEN');
$whatsappFrom = env('TWILIO_WHATSAPP_FROM');
$provider = env('WHATSAPP_PROVIDER', 'custom');

echo "Configuration:\n";
echo "  Provider: " . ($provider ?: 'not set') . "\n";
echo "  Account SID: " . ($accountSid ? substr($accountSid, 0, 10) . '...' : '❌ NOT SET') . "\n";
echo "  Auth Token: " . ($authToken ? '✅ SET' : '❌ NOT SET') . "\n";
echo "  WhatsApp From: " . ($whatsappFrom ?: '❌ NOT SET') . "\n\n";

if (!$accountSid || !$authToken || !$whatsappFrom) {
    die("❌ Missing Twilio credentials in .env file\n");
}

if ($provider !== 'twilio') {
    echo "⚠️  Warning: WHATSAPP_PROVIDER is '{$provider}', should be 'twilio'\n\n";
}

// Test connection
echo "Testing Twilio API Connection...\n";
try {
    $response = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])
        ->withBasicAuth($accountSid, $authToken)
        ->get("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}.json");
    
    if ($response->successful()) {
        $account = $response->json();
        echo "✅ Connection successful!\n";
        echo "   Account: " . ($account['friendly_name'] ?? 'N/A') . "\n";
        echo "   Status: " . ($account['status'] ?? 'N/A') . "\n\n";
        echo "✅ Twilio is configured and working!\n";
    } else {
        echo "❌ Connection failed: " . $response->status() . "\n";
        echo "   Error: " . ($response->json()['message'] ?? $response->body()) . "\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ All checks passed! Twilio is ready to use.\n";



