<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItemAllocation extends Model
{
    protected $fillable = [
        'sales_order_item_id',
        'warehouse_id',
        'reserved_quantity',
        'shipped_quantity',
        'backorder_quantity',
    ];

    protected function casts(): array
    {
        return [
            'reserved_quantity' => 'decimal:3',
            'shipped_quantity' => 'decimal:3',
            'backorder_quantity' => 'decimal:3',
        ];
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}