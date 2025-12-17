<?php

namespace App\Http\Controllers;

use App\Models\AddyInsight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ExpensesController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        
        // Get upcoming liabilities (bills)
        $upcomingLiabilities = $this->getUpcomingLiabilities($organizationId);

        // Get vendor metrics
        $vendorMetrics = $this->getVendorMetrics($organizationId);

        // Get upcoming bills by vendor
        $upcomingBills = $this->getUpcomingBillsByVendor($organizationId);
        
        // Get projected bill timeline
        $billTimeline = $this->getBillTimeline($organizationId);
        
        // Get Expenses-specific insights
        $insights = AddyInsight::active($organizationId)
            ->where('category', 'expenses')
            ->orderBy('priority', 'desc')
            ->limit(3)
            ->get()
            ->map(fn($insight) => [
                'id' => $insight->id,
                'type' => $insight->type,
                'title' => $insight->title,
                'description' => $insight->description,
                'priority' => (float) $insight->priority,
                'is_actionable' => $insight->is_actionable,
                'action_url' => $insight->action_url,
            ]);

        return Inertia::render('Expenses/Index', [
            'upcomingLiabilities' => $upcomingLiabilities,
            'vendorMetrics' => $vendorMetrics,
            'upcomingBills' => $upcomingBills,
            'billTimeline' => $billTimeline,
            'insights' => $insights,
        ]);
    }

    private function getUpcomingLiabilities(string $organizationId): array
    {
        $unpaidBills = \App\Models\Bill::where('organization_id', $organizationId)
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->where('status', '!=', 'cancelled')
            ->get();

        $total = $unpaidBills->sum('amount_due');
        $overdue = $unpaidBills->filter(fn($bill) => ($bill->is_overdue ?? $bill->due_date->isPast()))->sum('amount_due');
        
        $dueSoon = $unpaidBills
            ->filter(fn($bill) => $bill->due_date->between(now(), now()->addWeek()))
            ->sum('amount_due');

        return [
            'total' => $total,
            'overdue' => $overdue,
            'due_soon' => $dueSoon,
            'vendor_count' => $unpaidBills->pluck('vendor_id')->unique()->count(),
            'bill_count' => $unpaidBills->count(),
        ];
    }

    private function getVendorMetrics(string $organizationId): array
    {
        $vendors = \App\Models\Vendor::where('organization_id', $organizationId);

        $totalVendors = $vendors->count();
        $activeVendors = $vendors->where('status', 'active')->count();
        
        $newThisMonth = \App\Models\Vendor::where('organization_id', $organizationId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $totalSpent = $vendors->sum('total_spent') ?? 0;

        return [
            'total' => $totalVendors,
            'active' => $activeVendors,
            'new_this_month' => $newThisMonth,
            'total_spent' => $totalSpent,
        ];
    }

    private function getUpcomingBillsByVendor(string $organizationId): array
    {
        $bills = \App\Models\Bill::where('organization_id', $organizationId)
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->where('status', '!=', 'cancelled')
            ->with('vendor')
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();

        return $bills->map(function ($bill) {
            return [
                'id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'vendor_name' => $bill->vendor->name ?? 'Unknown',
                'vendor_id' => $bill->vendor_id,
                'amount_due' => $bill->amount_due ?? ($bill->total - ($bill->amount_paid ?? 0)),
                'due_date' => $bill->due_date->toDateString(),
                'due_date_formatted' => $bill->due_date->format('M d, Y'),
                'days_until_due' => $bill->due_date->diffInDays(now(), false),
                'is_overdue' => $bill->due_date->isPast(),
                'status' => $bill->status,
                'payment_status' => $bill->payment_status,
                'currency' => $bill->currency ?? 'USD',
            ];
        })->toArray();
    }

    private function getBillTimeline(string $organizationId): array
    {
        $bills = \App\Models\Bill::where('organization_id', $organizationId)
            ->whereIn('payment_status', ['unpaid', 'partially_paid'])
            ->where('status', '!=', 'cancelled')
            ->orderBy('due_date', 'asc')
            ->get();

        // Group by week for the next 12 weeks
        $timeline = [];
        for ($i = 0; $i < 12; $i++) {
            $weekStart = now()->addWeeks($i)->startOfWeek();
            $weekEnd = now()->addWeeks($i)->endOfWeek();
            
            $weekBills = $bills->filter(function ($bill) use ($weekStart, $weekEnd) {
                return $bill->due_date->between($weekStart, $weekEnd);
            });

            $timeline[] = [
                'week' => $weekStart->format('M d'),
                'amount' => $weekBills->sum('amount_due'),
                'bill_count' => $weekBills->count(),
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
            ];
        }

        return $timeline;
    }
}
