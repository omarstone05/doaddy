<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\WhatsAppService;

echo "========================================\n";
echo "Add Phone Number to User Account\n";
echo "========================================\n\n";

$phoneNumber = '0973660337';
$whatsappService = new WhatsAppService();
$normalizedPhone = $whatsappService->formatPhoneNumberForApi($phoneNumber);

echo "Phone Number: {$phoneNumber}\n";
echo "Normalized: {$normalizedPhone}\n\n";

// Check if user already has this phone number
$existingUser = User::findByPhoneNumber($phoneNumber);
if ($existingUser) {
    echo "✅ User already exists with this phone number:\n";
    echo "   Name: {$existingUser->name}\n";
    echo "   Email: {$existingUser->email}\n";
    echo "   Phone: {$existingUser->phone_number}\n";
    exit(0);
}

// Get first available user
$user = User::first();
if (!$user) {
    echo "❌ No users found in database. Please run: php artisan db:seed\n";
    exit(1);
}

echo "Found user: {$user->name} ({$user->email})\n";
echo "Adding phone number to this account...\n";

$user->update(['phone_number' => $normalizedPhone]);

echo "✅ Phone number added successfully!\n\n";
echo "You can now login with:\n";
echo "  Phone: {$phoneNumber}\n";
echo "  Or Email: {$user->email}\n";
echo "  Password: password\n\n";



