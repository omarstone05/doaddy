<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "========================================\n";
echo "Quick Login Information\n";
echo "========================================\n\n";

echo "Server URL: http://localhost:8000\n\n";

echo "Available Test Users:\n";
echo "---------------------\n";

$users = \App\Models\User::all(['name', 'email', 'phone_number']);

foreach($users as $user) {
    echo "Name: {$user->name}\n";
    echo "Email: {$user->email}\n";
    if ($user->phone_number) {
        echo "Phone: {$user->phone_number}\n";
    }
    echo "Password: password\n";
    echo "---\n";
}

echo "\n";
echo "Login Options:\n";
echo "1. Email Login: http://localhost:8000/login\n";
echo "   - Use: admin@test.com / password\n";
echo "   - Or: user@test.com / password\n\n";
echo "2. WhatsApp Login: http://localhost:8000/login (WhatsApp tab)\n";
echo "   - Enter your phone number\n";
echo "   - Receive code via WhatsApp\n\n";



