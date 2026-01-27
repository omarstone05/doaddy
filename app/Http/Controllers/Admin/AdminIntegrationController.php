<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class AdminIntegrationController extends Controller
{
    /**
     * Show Digitax configuration page
     */
    public function digitax()
    {
        return Inertia::render('Admin/Integration/DigitaxSettings');
    }
}
