<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceSlab extends Model
{
    protected $fillable = [
        'company_id',
        'product_id',
        'minimum_quantity',
        'discount_percent',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'minimum_quantity' => 'decimal:3',
            'discount_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
