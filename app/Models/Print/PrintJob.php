<?php

namespace App\Models\Print;

use App\Models\Organization;
use App\Models\Customer;
use App\Models\User;
use App\Models\Quotation;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'job_number',
        'customer_id',
        'quotation_id',
        'invoice_id',
        'print_material_id',
        'ink_configuration_id',
        'pricing_rule_id',
        'width',
        'height',
        'quantity',
        'material_unit_cost',
        'ink_unit_cost',
        'off_cut_cost',
        'price_per_sqm',
        'setup_cost',
        'finishing_cost',
        'delivery_cost',
        'other_costs',
        'status',
        'quoted_at',
        'approved_at',
        'completed_at',
        'notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'quantity' => 'integer',
        'material_unit_cost' => 'decimal:2',
        'ink_unit_cost' => 'decimal:2',
        'off_cut_cost' => 'decimal:2',
        'price_per_sqm' => 'decimal:2',
        'setup_cost' => 'decimal:2',
        'finishing_cost' => 'decimal:2',
        'delivery_cost' => 'decimal:2',
        'other_costs' => 'decimal:2',
        'quoted_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'total_area',
        'base_unit_cost',
        'total_cost',
        'total_price',
        'margin_per_sqm',
        'total_margin',
        'margin_percentage',
        'additional_costs',
        'grand_total',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($job) {
            if (empty($job->job_number)) {
                $job->job_number = self::generateJobNumber($job->organization_id);
            }
        });
    }

    public static function generateJobNumber(string $organizationId): string
    {
        $year = date('Y');
        $count = self::where('organization_id', $organizationId)
            ->whereYear('created_at', $year)
            ->count() + 1;
        
        return sprintf('PJ-%s-%04d', $year, $count);
    }

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function printMaterial(): BelongsTo
    {
        return $this->belongsTo(PrintMaterial::class);
    }

    public function inkConfiguration(): BelongsTo
    {
        return $this->belongsTo(InkConfiguration::class);
    }

    public function pricingRule(): BelongsTo
    {
        return $this->belongsTo(PricingRule::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // Calculated attributes
    public function getTotalAreaAttribute(): float
    {
        return round($this->width * $this->height * $this->quantity, 2);
    }

    public function getBaseUnitCostAttribute(): float
    {
        return round($this->material_unit_cost + $this->ink_unit_cost + $this->off_cut_cost, 2);
    }

    public function getTotalCostAttribute(): float
    {
        return round($this->base_unit_cost * $this->total_area, 2);
    }

    public function getTotalPriceAttribute(): float
    {
        return round($this->price_per_sqm * $this->total_area, 2);
    }

    public function getMarginPerSqmAttribute(): float
    {
        return round($this->price_per_sqm - $this->base_unit_cost, 2);
    }

    public function getTotalMarginAttribute(): float
    {
        return round($this->margin_per_sqm * $this->total_area, 2);
    }

    public function getMarginPercentageAttribute(): float
    {
        if ($this->price_per_sqm <= 0) {
            return 0;
        }
        return round(($this->margin_per_sqm / $this->price_per_sqm) * 100, 2);
    }

    public function getAdditionalCostsAttribute(): float
    {
        return round($this->setup_cost + $this->finishing_cost + $this->delivery_cost + $this->other_costs, 2);
    }

    public function getGrandTotalAttribute(): float
    {
        return round($this->total_price + $this->additional_costs, 2);
    }

    // Status methods
    public function markAsQuoted(): void
    {
        $this->update([
            'status' => 'quoted',
            'quoted_at' => now(),
        ]);
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function startProgress(): void
    {
        $this->update(['status' => 'in_progress']);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public static function getStatusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'quoted' => 'Quoted',
            'approved' => 'Approved',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'quoted' => 'blue',
            'approved' => 'teal',
            'in_progress' => 'amber',
            'completed' => 'green',
            'cancelled' => 'red',
            default => 'gray',
        };
    }
}

