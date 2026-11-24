<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'name',
        'hsn_code',
        'gst_rate',
        'unit',
        'base_price',
        'is_active',
    ];

    protected $casts = [
        'gst_rate' => 'decimal:2',
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
