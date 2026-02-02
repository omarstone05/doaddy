<?php

namespace App\Console\Commands;

use App\Mail\WeeklySummaryMail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWeeklySummaries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:weekly-summaries 
                            {--org= : Send to specific organization ID}
                            {--user= : Send to specific user email}
                            {--dry-run : Preview without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly summary emails to all organization owners/admins';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting weekly summary email dispatch...');
        
        $dryRun = $this->option('dry-run');
        $specificOrg = $this->option('org');
        $specificUser = $this->option('user');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No emails will be sent');
        }

        // Build query for organizations
        $query = Organization::query();
        
        if ($specificOrg) {
            $query->where('id', $specificOrg);
        }

        $organizations = $query->get();
        $sentCount = 0;
        $errorCount = 0;

        foreach ($organizations as $organization) {
            // Get users to send to (owner and admins)
            $usersQuery = $organization->members()
                ->wherePivotIn('role_id', function ($q) {
                    $q->select('id')
                        ->from('organization_roles')
                        ->whereIn('slug', ['owner', 'admin']);
                })
                ->wherePivot('is_active', true);

            // If specific user requested, filter to that user
            if ($specificUser) {
                $usersQuery->where('email', $specificUser);
            }

            $users = $usersQuery->get();

            if ($users->isEmpty()) {
                // Fallback: get the first user with this organization
                $users = User::where('organization_id', $organization->id)
                    ->when($specificUser, fn($q) => $q->where('email', $specificUser))
                    ->limit(1)
                    ->get();
            }

            foreach ($users as $user) {
                try {
                    $this->line("Processing: {$user->email} ({$organization->name})");

                    if ($dryRun) {
                        $mail = new WeeklySummaryMail($user, $organization);
                        $this->info("  → Would send: {$mail->envelope()->subject}");
                        $this->line("  → Revenue: {$organization->currency} {$mail->summary['revenue_received']}");
                        $this->line("  → Invoices Created: {$mail->summary['invoices_created']}");
                        $this->line("  → Invoices Paid: {$mail->summary['invoices_paid']}");
                        continue;
                    }

                    Mail::to($user->email)->send(new WeeklySummaryMail($user, $organization));
                    
                    $sentCount++;
                    $this->info("  ✓ Sent successfully");

                    Log::info('Weekly summary sent', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'organization_id' => $organization->id,
                    ]);

                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("  ✗ Failed: {$e->getMessage()}");
                    
                    Log::error('Failed to send weekly summary', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'organization_id' => $organization->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->newLine();
        
        if ($dryRun) {
            $this->info("Dry run complete. {$organizations->count()} organization(s) processed.");
        } else {
            $this->info("Complete! Sent: {$sentCount}, Errors: {$errorCount}");
        }

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
