<?php

namespace App\Services\Addy\Agents;

use App\Models\Organization;
use App\Models\GoodsAndService;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class InventoryAgent
{
    protected Organization $organization;

    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
    }

    public function perceive(): array
    {
        return [
            'stock_levels' => $this->getStockLevels(),
            'low_stock_items' => $this->getLowStockItems(),
            'out_of_stock' => $this->getOutOfStockItems(),
            'stock_movements' => $this->getStockMovements(),
            'inventory_value' => $this->getInventoryValue(),
        ];
    }

    protected function getStockLevels(): array
    {
        $products = GoodsAndService::where('organization_id', $this->organization->id)
            ->where('track_stock', true)
            ->get();

        $healthy = 0;
        $low = 0;
        $out = 0;

        foreach ($products as $product) {
            if ($product->current_stock <= 0) {
                $out++;
            } elseif ($product->current_stock <= $product->minimum_stock) {
                $low++;
            } else {
                $healthy++;
            }
        }

        return [
            'total_products' => $products->count(),
            'healthy' => $healthy,
            'low_stock' => $low,
            'out_of_stock' => $out,
        ];
    }

    protected function getLowStockItems(): array
    {
        return GoodsAndService::where('organization_id', $this->organization->id)
            ->where('track_stock', true)
            ->where('current_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->orderBy('current_stock')
            ->limit(5)
            ->get()
            ->map(fn($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'current_stock' => $product->current_stock,
                'reorder_level' => $product->minimum_stock,
            ])
            ->toArray();
    }

    protected function getOutOfStockItems(): array
    {
        return GoodsAndService::where('organization_id', $this->organization->id)
            ->where('track_stock', true)
            ->where('current_stock', '<=', 0)
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn($product) => [
                'id' => $product->id,
                'name' => $product->name,
            ])
            ->toArray();
    }

    protected function getStockMovements(): array
    {
        $thisMonth = StockMovement::where('organization_id', $this->organization->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->get();

        return [
            'total_movements' => $thisMonth->count(),
            'sales' => $thisMonth->where('movement_type', 'out')->count(),
            'purchases' => $thisMonth->where('movement_type', 'in')->count(),
            'adjustments' => $thisMonth->whereNotIn('movement_type', ['in', 'out'])->count(),
        ];
    }

    protected function getInventoryValue(): float
    {
        return GoodsAndService::where('organization_id', $this->organization->id)
            ->where('track_stock', true)
            ->get()
            ->sum(function($product) {
                return $product->current_stock * $product->cost_price;
            });
    }

    public function analyze(): array
    {
        $perception = $this->perceive();
        $insights = [];

        // Out of stock alert
        if ($perception['stock_levels']['out_of_stock'] > 0) {
            $items = $perception['out_of_stock'];
            $itemsList = collect($items)->pluck('name')->take(3)->implode(', ');
            
            $insights[] = [
                'type' => 'alert',
                'category' => 'inventory',
                'title' => 'Out of Stock Items',
                'description' => "{$perception['stock_levels']['out_of_stock']} product(s) are out of stock: {$itemsList}" . 
                    (count($items) > 3 ? '...' : '.'),
                'priority' => 0.9,
                'is_actionable' => true,
                'suggested_actions' => [
                    'Place urgent restock orders',
                    'Update product availability',
                    'Notify customers of delays',
                ],
                'action_url' => '/stock',
            ];
        }

        // Low stock warning
        if ($perception['stock_levels']['low_stock'] > 0) {
            $items = $perception['low_stock_items'];
            $itemsList = collect($items)->pluck('name')->take(3)->implode(', ');
            
            $insights[] = [
                'type' => 'suggestion',
                'category' => 'inventory',
                'title' => 'Low Stock Warning',
                'description' => "{$perception['stock_levels']['low_stock']} product(s) running low: {$itemsList}" . 
                    (count($items) > 3 ? '...' : '.'),
                'priority' => 0.75,
                'is_actionable' => true,
                'suggested_actions' => [
                    'Review reorder levels',
                    'Place purchase orders',
                    'Check supplier availability',
                ],
                'action_url' => '/stock',
            ];
        }

        // Inventory value observation
        if ($perception['inventory_value'] > 0) {
            $insights[] = [
                'type' => 'observation',
                'category' => 'inventory',
                'title' => 'Current Inventory Value',
                'description' => "Total inventory value: " . number_format($perception['inventory_value'], 2),
                'priority' => 0.4,
                'is_actionable' => false,
                'suggested_actions' => [
                    'Monitor slow-moving items',
                    'Consider inventory optimization',
                ],
                'action_url' => '/stock',
            ];
        }

        // High stock movement
        if ($perception['stock_movements']['total_movements'] > 50) {
            $insights[] = [
                'type' => 'observation',
                'category' => 'inventory',
                'title' => 'High Inventory Activity',
                'description' => "{$perception['stock_movements']['total_movements']} stock movements this month. " .
                    "Sales: {$perception['stock_movements']['sales']}, " .
                    "Purchases: {$perception['stock_movements']['purchases']}",
                'priority' => 0.5,
                'is_actionable' => false,
                'suggested_actions' => [
                    'Review inventory turnover',
                    'Optimize stock levels',
                ],
                'action_url' => '/stock/movements',
            ];
        }

        return $insights;
    }
}

