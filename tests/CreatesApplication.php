<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Set penda-jwt config for testing (needed before service providers are booted)
        $app['config']->set('penda-jwt.secret', 'test-secret-key-for-testing-12345');

        return $app;
    }
}

