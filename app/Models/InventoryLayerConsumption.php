<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLayerConsumption extends Model
{
    protected $fillable = [
        'inventory_cost_layer_id',
        'inventory_movement_id',
        'quantity',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function costLayer(): BelongsTo
    {
        return $this->belongsTo(InventoryCostLayer::class, 'inventory_cost_layer_id');
    }

    public function inventoryMovement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class);
    }
}