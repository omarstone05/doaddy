<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Customer extends Model
{
    use HasFactory, HasUuid, BelongsToOrganization;

    protected $fillable = [
        'organization_id',
        'name',
        'email',
        'phone',
        'company_name',
        'address',
        'tax_id',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(\App\Models\Attachment::class, 'attachable');
    }

    // public function invoices(): HasMany
    // {
    //     return $this->hasMany(\App\Models\Invoice::class);
    // }
}
