<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\NeuroCore\Tests\NeuroHelperTest;

class NeuroTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'neuro:test 
                            {--quick : Run only quick tests without AI}
                            {--verbose : Show detailed output}';

    /**
     * The console command description.
     */
    protected $description = 'Run NeuroCore test suite';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('🧠 NeuroCore Test Suite');
        $this->info(str_repeat('=', 50));

        // Ensure test class is loaded
        require_once app_path('NeuroCore/Tests/NeuroHelperTest.php');

        $tester = new NeuroHelperTest();
        $results = $tester->runAll();

        $this->info('');

        if ($results['failed'] === 0) {
            $this->info('✅ All tests passed!');
            return Command::SUCCESS;
        } else {
            $this->error("❌ {$results['failed']} test(s) failed");
            
            if ($this->option('verbose')) {
                foreach ($results['results'] as $name => $result) {
                    if ($result['status'] === 'failed') {
                        $this->error("  - {$name}: {$result['error']}");
                    }
                }
            }
            
            return Command::FAILURE;
        }
    }
}


