<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCostLayer extends Model
{
    protected $fillable = [
        'company_id',
        'warehouse_id',
        'product_id',
        'inventory_movement_id',
        'initial_quantity',
        'remaining_quantity',
        'unit_cost',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'initial_quantity' => 'decimal:3',
            'remaining_quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function inventoryMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(InventoryLayerConsumption::class);
    }
}