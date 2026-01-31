<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ChangeUserPassword extends Command
{
    protected $signature = 'user:change-password {email} {password}';
    
    protected $description = 'Change a user\'s password';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return Command::FAILURE;
        }

        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password updated successfully for {$email}");

        return Command::SUCCESS;
    }
}
