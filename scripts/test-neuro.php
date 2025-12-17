<?php

/**
 * Standalone NeuroCore Test Script
 * 
 * Run from project root:
 *   php scripts/test-neuro.php
 * 
 * This script bootstraps Laravel and runs the NeuroCore tests
 */

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Load test class
require_once __DIR__ . '/../app/NeuroCore/Tests/NeuroHelperTest.php';

use App\NeuroCore\Tests\NeuroHelperTest;

echo "\n";
echo "╔══════════════════════════════════════════════════╗\n";
echo "║        🧠 NeuroCore Test Runner                  ║\n";
echo "╚══════════════════════════════════════════════════╝\n";
echo "\n";

// Run tests
$tester = new NeuroHelperTest();
$results = $tester->runAll();

// Exit with appropriate code
exit($results['failed'] > 0 ? 1 : 0);


