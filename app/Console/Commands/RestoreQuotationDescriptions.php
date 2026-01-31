<?php

namespace App\Console\Commands;

use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Console\Command;

class RestoreQuotationDescriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotations:restore-descriptions {--organization-id= : Specific organization ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore descriptions for quotation items that lost them, using product descriptions where available';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $organizationId = $this->option('organization-id');
        
        $query = QuotationItem::query()
            ->whereNull('description')
            ->orWhere('description', '');
        
        if ($organizationId) {
            $query->whereHas('quotation', function ($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            });
        }
        
        $items = $query->with(['product', 'quotation'])->get();
        
        $this->info("Found {$items->count()} items with missing descriptions");
        
        $restored = 0;
        $skipped = 0;
        
        foreach ($items as $item) {
            // Try to restore from product description if product exists
            if ($item->product_id && $item->product && $item->product->description) {
                $item->description = $item->product->description;
                $item->save();
                $restored++;
                $this->line("Restored description for item {$item->id} (Quotation: {$item->quotation->quotation_number})");
            } else {
                $skipped++;
            }
        }
        
        $this->info("Restored {$restored} descriptions");
        $this->info("Skipped {$skipped} items (no product or product description available)");
        
        return 0;
    }
}
