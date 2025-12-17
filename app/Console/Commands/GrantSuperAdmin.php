<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantSuperAdmin extends Command
{
    protected $signature = 'admin:grant {email}';
    protected $description = 'Grant super admin access to a user';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User not found with email: {$email}");
            return 1;
        }
        
        $user->update(['is_super_admin' => true]);
        
        $this->info("✓ Super admin access granted to: {$user->email}");
        $this->info("✓ User ID: {$user->id}");
        $this->info("✓ is_super_admin: " . ($user->is_super_admin ? 'true' : 'false'));
        
        return 0;
    }
}

