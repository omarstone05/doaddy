<?php

namespace App\Http\Controllers;

use App\Models\MoneyMovement;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Search for categories
     */
    public function search(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        $searchTerm = $request->input('q', '');
        $type = $request->input('type', 'expense'); // 'expense', 'income', 'general'

        $categories = collect();

        // Get categories from money_movements
        if ($type === 'expense') {
            $categories = MoneyMovement::where('organization_id', $organizationId)
                ->where('flow_type', 'expense')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->select('category')
                ->distinct()
                ->pluck('category');
        } elseif ($type === 'income') {
            $categories = MoneyMovement::where('organization_id', $organizationId)
                ->where('flow_type', 'income')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->select('category')
                ->distinct()
                ->pluck('category');
        } else {
            // General: get from both expense and income
            $expenseCategories = MoneyMovement::where('organization_id', $organizationId)
                ->where('flow_type', 'expense')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->select('category')
                ->distinct()
                ->pluck('category');

            $incomeCategories = MoneyMovement::where('organization_id', $organizationId)
                ->where('flow_type', 'income')
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->select('category')
                ->distinct()
                ->pluck('category');

            $categories = $expenseCategories->merge($incomeCategories)->unique();
        }

        // Get categories from bills
        $billCategories = Bill::where('organization_id', $organizationId)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->select('category')
            ->distinct()
            ->pluck('category');

        $categories = $categories->merge($billCategories)->unique()->sort()->values();

        // Filter by search term if provided
        if ($searchTerm) {
            $categories = $categories->filter(function ($category) use ($searchTerm) {
                return stripos($category, $searchTerm) !== false;
            })->take(10); // Limit to 10 results
        } else {
            $categories = $categories->take(20); // Limit to 20 results when no search
        }

        return response()->json([
            'categories' => $categories->values()->all(),
        ]);
    }
}

