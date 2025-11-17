<?php

/**
 * Twilio WhatsApp Test Script
 * 
 * This script helps you test your Twilio WhatsApp configuration
 * Run it from the command line: php test-twilio-whatsapp.php
 * 
 * Make sure your .env file has the Twilio credentials configured first.
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel to load environment variables properly
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "========================================\n";
echo "Twilio WhatsApp Configuration Test\n";
echo "========================================\n\n";

// Check environment variables
$accountSid = env('TWILIO_ACCOUNT_SID');
$authToken = env('TWILIO_AUTH_TOKEN');
$whatsappFrom = env('TWILIO_WHATSAPP_FROM');
$provider = env('WHATSAPP_PROVIDER', 'custom');

echo "Configuration Check:\n";
echo "-------------------\n";
echo "Provider: " . ($provider ?: 'not set') . "\n";
echo "Account SID: " . ($accountSid ? substr($accountSid, 0, 10) . '...' : '❌ NOT SET') . "\n";
echo "Auth Token: " . ($authToken ? '✅ SET' : '❌ NOT SET') . "\n";
echo "WhatsApp From: " . ($whatsappFrom ?: '❌ NOT SET') . "\n\n";

if (!$accountSid || !$authToken || !$whatsappFrom) {
    die("❌ Error: Missing required Twilio credentials. Please check your .env file.\n");
}

if ($provider !== 'twilio') {
    echo "⚠️  Warning: WHATSAPP_PROVIDER is set to '{$provider}'. Set it to 'twilio' to use Twilio.\n\n";
}

// Test Twilio API connection
echo "Testing Twilio API Connection:\n";
echo "------------------------------\n";

try {
    $response = Http::withBasicAuth($accountSid, $authToken)
        ->get("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}.json");
    
    if ($response->successful()) {
        $account = $response->json();
        echo "✅ Connection successful!\n";
        echo "   Account Name: " . ($account['friendly_name'] ?? 'N/A') . "\n";
        echo "   Account Status: " . ($account['status'] ?? 'N/A') . "\n";
        echo "   Account Type: " . ($account['type'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Connection failed!\n";
        echo "   Status: " . $response->status() . "\n";
        echo "   Error: " . ($response->json()['message'] ?? $response->body()) . "\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "❌ Error connecting to Twilio API:\n";
    echo "   " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test WhatsApp number format
echo "WhatsApp Number Format Check:\n";
echo "-----------------------------\n";
if (preg_match('/^whatsapp:\+\d+$/', $whatsappFrom)) {
    echo "✅ WhatsApp 'From' number format is correct\n";
    echo "   Format: {$whatsappFrom}\n";
} else {
    echo "❌ WhatsApp 'From' number format is incorrect\n";
    echo "   Current: {$whatsappFrom}\n";
    echo "   Expected format: whatsapp:+1234567890\n";
    echo "   Example (Sandbox): whatsapp:+14155238886\n";
}

echo "\n";

// Optional: Test sending a message
echo "========================================\n";
echo "Optional: Test Message Send\n";
echo "========================================\n";
echo "Do you want to send a test WhatsApp message? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$sendTest = trim(strtolower($line)) === 'y';
fclose($handle);

if ($sendTest) {
    echo "\nEnter recipient phone number (E.164 format, e.g., 260973660337): ";
    $handle = fopen("php://stdin", "r");
    $phoneNumber = trim(fgets($handle));
    fclose($handle);
    
    if (empty($phoneNumber)) {
        echo "❌ Phone number is required.\n";
        exit(1);
    }
    
    // Remove any non-numeric characters
    $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
    
    // Ensure it starts with country code
    if (!preg_match('/^\d{10,15}$/', $phoneNumber)) {
        echo "❌ Invalid phone number format. Use E.164 format (e.g., 260973660337)\n";
        exit(1);
    }
    
    $testMessage = "Test message from Addy Business WhatsApp integration. If you receive this, your setup is working! ✅";
    
    echo "\nSending test message to whatsapp:+{$phoneNumber}...\n";
    
    try {
        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $whatsappFrom,
                'To' => 'whatsapp:+' . $phoneNumber,
                'Body' => $testMessage,
            ]);
        
        if ($response->successful()) {
            $message = $response->json();
            echo "✅ Message sent successfully!\n";
            echo "   Message SID: " . ($message['sid'] ?? 'N/A') . "\n";
            echo "   Status: " . ($message['status'] ?? 'N/A') . "\n";
            echo "\n";
            echo "📱 Check WhatsApp on +{$phoneNumber} for the test message.\n";
            echo "   Note: For sandbox, the recipient must have joined the sandbox first.\n";
        } else {
            $error = $response->json();
            echo "❌ Failed to send message!\n";
            echo "   Status: " . $response->status() . "\n";
            echo "   Error Code: " . ($error['code'] ?? 'N/A') . "\n";
            echo "   Error Message: " . ($error['message'] ?? $response->body()) . "\n";
            echo "\n";
            echo "Common issues:\n";
            echo "- For sandbox: Recipient must join sandbox first\n";
            echo "- Phone number format must be correct (E.164)\n";
            echo "- WhatsApp Business account must be approved (for production)\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error sending message:\n";
        echo "   " . $e->getMessage() . "\n";
    }
} else {
    echo "Skipping test message send.\n";
}

echo "\n";
echo "========================================\n";
echo "Test Complete!\n";
echo "========================================\n";
echo "\n";
echo "Next steps:\n";
echo "1. If all checks passed, your Twilio configuration is correct!\n";
echo "2. Test your application's WhatsApp endpoints\n";
echo "3. For production, apply for WhatsApp Business API approval\n";
echo "4. See TWILIO_WHATSAPP_SETUP_GUIDE.md for detailed instructions\n";
echo "\n";

