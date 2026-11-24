<?php

namespace App\Services;

use App\Models\InventoryCostLayer;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Models\SalesOrderItemAllocation;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryLayerConsumption;
use InvalidArgumentException;
use App\Notifications\CreditHoldNotification;
use App\Models\User;

class InventoryService
{
    public function receiveStock(
        int $companyId,
        int $warehouseId,
        int $productId,
        string $quantity,
        string $unitCost,
        ?int $userId = null,
        ?string $notes = null
    ): InventoryMovement {

        if (
            !is_numeric($quantity) ||
            !is_numeric($unitCost) ||
            (float) $quantity <= 0 ||
            (float) $unitCost < 0
        ) {
            throw new InvalidArgumentException(
                'Quantity must be positive and unit cost cannot be negative.'
            );
        }

        return DB::transaction(function () use (
            $companyId,
            $warehouseId,
            $productId,
            $quantity,
            $unitCost,
            $userId,
            $notes
        ) {
            $warehouse = Warehouse::where('id', $warehouseId)
                ->where('company_id', $companyId)
                ->firstOrFail();

            Product::where('id', $productId)
                ->where('company_id', $companyId)
                ->firstOrFail();

            // Create a balance row if one does not exist.
            DB::table('stock_balances')->insertOrIgnore([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'on_hand' => 0,
                'reserved' => 0,
                'available' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Lock the balance row to prevent concurrent stock updates.
            $balance = StockBalance::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->firstOrFail();

            // Record the stock receipt in the inventory ledger.
            $movement = InventoryMovement::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'user_id' => $userId,
                'movement_type' => 'INWARD',
                'quantity' => $quantity,
                'notes' => $notes ?? 'Stock received',
            ]);

            // Update the stock balance.
            $balance->increment('on_hand', $quantity);
            $balance->increment('available', $quantity);

            InventoryCostLayer::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'inventory_movement_id' => $movement->id,
                'initial_quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'unit_cost' => $unitCost,
                'received_at' => now(),
            ]);

            // Automatically fulfil pending backorders.
            $this->allocateBackorders(
                $companyId,
                $warehouseId,
                $productId,
                $userId ?? 1
            );
            return $movement;
        });
    }


    public function reserveStock(
        int $companyId,
        int $warehouseId,
        int $productId,
        string $quantity,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): InventoryMovement {

        if (!is_numeric($quantity) || (float) $quantity <= 0) {
            throw new InvalidArgumentException(
                'Reservation quantity must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $companyId,
            $warehouseId,
            $productId,
            $quantity,
            $userId,
            $referenceType,
            $referenceId
        ) {
            Warehouse::where('id', $warehouseId)
                ->where('company_id', $companyId)
                ->firstOrFail();

            Product::where('id', $productId)
                ->where('company_id', $companyId)
                ->firstOrFail();

            $balance = StockBalance::where('company_id', $companyId)
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$balance || (float) $balance->available < (float) $quantity) {
                throw new InvalidArgumentException(
                    'Insufficient available stock for reservation.'
                );
            }

            $balance->increment('reserved', $quantity);
            $balance->decrement('available', $quantity);

            return InventoryMovement::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'user_id' => $userId,
                'movement_type' => 'RESERVE',
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => 'Stock reserved for sales order',
            ]);
        });
    }


    public function releaseReservation(
        int $companyId,
        int $warehouseId,
        int $productId,
        string $quantity,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): InventoryMovement {

        if (!is_numeric($quantity) || (float) $quantity <= 0) {
            throw new InvalidArgumentException(
                'Release quantity must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $companyId,
            $warehouseId,
            $productId,
            $quantity,
            $userId,
            $referenceType,
            $referenceId
        ) {
            $balance = StockBalance::where('company_id', $companyId)
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $balance->reserved < (float) $quantity) {
                throw new InvalidArgumentException(
                    'Cannot release more stock than the reserved quantity.'
                );
            }

            $balance->decrement('reserved', $quantity);
            $balance->increment('available', $quantity);

            return InventoryMovement::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'user_id' => $userId,
                'movement_type' => 'UNRESERVE',
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => 'Reserved stock released',
            ]);
        });
    }


    public function shipStock(
        int $companyId,
        int $warehouseId,
        int $productId,
        string $quantity,
        ?int $userId = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): InventoryMovement {

        $quantityUnits = $this->toMilliUnits($quantity);

        if ($quantityUnits <= 0) {
            throw new InvalidArgumentException(
                'Shipping quantity must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $companyId,
            $warehouseId,
            $productId,
            $quantityUnits,
            $userId,
            $referenceType,
            $referenceId
        ) {
            $balance = StockBalance::where('company_id', $companyId)
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->toMilliUnits($balance->reserved) < $quantityUnits) {
                throw new InvalidArgumentException(
                    'Insufficient reserved stock for shipping.'
                );
            }

            // Lock FIFO layers, oldest batch first.
            $layers = InventoryCostLayer::where('company_id', $companyId)
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('remaining_quantity', '>', 0)
                ->orderBy('received_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $totalAvailable = $layers->sum(
                fn ($layer) => $this->toMilliUnits($layer->remaining_quantity)
            );

            if ($totalAvailable < $quantityUnits) {
                throw new InvalidArgumentException(
                    'Insufficient FIFO stock layers for shipping.'
                );
            }

            // Record the shipment movement.
            $movement = InventoryMovement::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'user_id' => $userId,
                'movement_type' => 'SHIP',
                'quantity' => $this->fromMilliUnits($quantityUnits),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => 'Stock shipped using FIFO',
            ]);

            $remainingToShip = $quantityUnits;

            foreach ($layers as $layer) {

                if ($remainingToShip <= 0) {
                    break;
                }

                $layerUnits = $this->toMilliUnits(
                    $layer->remaining_quantity
                );

                $consumedUnits = min($layerUnits, $remainingToShip);

                // Reduce the remaining quantity in this FIFO layer.
                $layer->remaining_quantity = $this->fromMilliUnits(
                    $layerUnits - $consumedUnits
                );

                $layer->save();

                // Record exactly which FIFO layer was consumed.
                InventoryLayerConsumption::create([
                    'inventory_cost_layer_id' => $layer->id,
                    'inventory_movement_id' => $movement->id,
                    'quantity' => $this->fromMilliUnits($consumedUnits),
                    'unit_cost' => $layer->unit_cost,
                ]);

                $remainingToShip -= $consumedUnits;
            }

            // Shipping reduces both physical and reserved stock.
            $balance->decrement(
                'on_hand',
                $this->fromMilliUnits($quantityUnits)
            );

            $balance->decrement(
                'reserved',
                $this->fromMilliUnits($quantityUnits)
            );

            return $movement;
        });
    }

    /**
     * Convert a quantity with up to 3 decimal places
     * into integer thousandths, avoiding float arithmetic.
     */
    private function toMilliUnits(string|int|float $quantity): int
    {
        $quantity = (string) $quantity;

        if (!preg_match('/^\d+(\.\d{1,3})?$/', $quantity)) {
            throw new InvalidArgumentException(
                'Quantity must be a non-negative number with up to 3 decimal places.'
            );
        }

        [$whole, $decimal] = array_pad(
            explode('.', $quantity, 2),
            2,
            ''
        );

        return ((int) $whole * 1000)
            + (int) str_pad($decimal, 3, '0');
    }

    /**
     * Convert integer thousandths back into a 3-decimal string.
     */
    private function fromMilliUnits(int $quantity): string
    {
        return intdiv($quantity, 1000)
            . '.'
            . str_pad((string) ($quantity % 1000), 3, '0', STR_PAD_LEFT);
    }

    public function allocateBackorders(
    int $companyId,
    int $warehouseId,
    int $productId,
    int $userId
    ): void {
        DB::transaction(function () use (
            $companyId,
            $warehouseId,
            $productId,
            $userId
        ) {
            $allocations = SalesOrderItemAllocation::query()
                ->where('backorder_quantity', '>', 0)
                ->whereHas('salesOrderItem', function ($query) use (
                    $companyId,
                    $productId
                ) {
                    $query->where('product_id', $productId)
                        ->whereHas('salesOrder', function ($orderQuery) use ($companyId) {
                            $orderQuery->where('company_id', $companyId)
                                ->where('status', 'CONFIRMED');
                        });
                })
                ->with('salesOrderItem.salesOrder.customer')
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($allocations as $allocation) {
                $stock = StockBalance::where('company_id', $companyId)
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (!$stock || (float) $stock->available <= 0) {
                    break;
                }

                $quantity = min(
                    (float) $allocation->backorder_quantity,
                    (float) $stock->available
                );

                $order = $allocation->salesOrderItem->salesOrder;

                // Reserve the newly received stock.
                $this->reserveStock(
                    $companyId,
                    $warehouseId,
                    $productId,
                    (string) $quantity,
                    $userId,
                    'SalesOrder',
                    $order->id
                );

                // Reduce the original backorder quantity.
                $allocation->decrement('backorder_quantity', $quantity);

                // Record reservation at the actual receiving warehouse.
                $destination = SalesOrderItemAllocation::firstOrNew([
                    'sales_order_item_id' => $allocation->sales_order_item_id,
                    'warehouse_id' => $warehouseId,
                ]);

                $destination->reserved_quantity =
                    (float) $destination->reserved_quantity + $quantity;

                $destination->save();

                // Notify the user who created the sales order.
                $salesUser = User::find($order->created_by);

                if ($salesUser) {
                    $salesUser->notify(new CreditHoldNotification(
                        $order->id,
                        $order->order_number,
                        $order->customer->name ?? 'Unknown Customer',
                        (float) $order->grand_total,
                        "Backorder fulfilled: {$quantity} units of product {$productId} have been reserved from warehouse {$warehouseId}."
                    ));
                }
            }
        });
    }
}
