<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'sales_order_id',
        'product_id',
        'product_code',
        'product_name',
        'hsn_code',
        'quantity',
        'is_free',
        'unit_price',
        'gross_amount',
        'slab_discount_percent',
        'slab_discount_amount',
        'promo_discount_percent',
        'promo_discount_amount',
        'taxable_amount',
        'gst_rate',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'line_total',
        'pricing_breakdown',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'is_free' => 'boolean',
            'unit_price' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'slab_discount_percent' => 'decimal:2',
            'slab_discount_amount' => 'decimal:2',
            'promo_discount_percent' => 'decimal:2',
            'promo_discount_amount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'gst_rate' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'pricing_breakdown' => 'array',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(SalesOrderItemAllocation::class);
    }

    public function shipmentItems(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }
}
