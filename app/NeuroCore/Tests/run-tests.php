<?php

/**
 * Run NeuroCore tests
 * 
 * Usage:
 *   php artisan tinker < app/NeuroCore/Tests/run-tests.php
 * 
 * Or from tinker:
 *   require 'app/NeuroCore/Tests/run-tests.php';
 */

require_once __DIR__ . '/NeuroHelperTest.php';

use App\NeuroCore\Tests\NeuroHelperTest;

$tester = new NeuroHelperTest();
$results = $tester->runAll();

// Return results for programmatic use
return $results;


