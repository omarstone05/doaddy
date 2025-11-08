<?php

namespace App\Http\Controllers;

use App\Models\PayrollItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PayrollItemController extends Controller
{
    public function show($id)
    {
        $payrollItem = PayrollItem::whereHas('payrollRun', function ($query) {
            $query->where('organization_id', Auth::user()->organization_id);
        })
        ->with(['payrollRun', 'teamMember'])
        ->findOrFail($id);

        return Inertia::render('Payroll/Items/Show', [
            'payrollItem' => $payrollItem,
        ]);
    }
}

