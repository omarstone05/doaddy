<?php

namespace App\Modules\Tax\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ModuleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaxController extends Controller
{
    public function index(Request $request, ModuleManager $modules): Response|RedirectResponse
    {
        if (!$modules->isEnabled('Tax')) {
            return redirect()->route('settings.modules')->with('error', 'Tax module is not enabled for this organization.');
        }

        return Inertia::render('Tax/Index', [
            'modules' => [
                'tax' => true,
                'smart_invoice_enabled' => $modules->isEnabled('SmartInvoice'),
            ],
        ]);
    }
}
