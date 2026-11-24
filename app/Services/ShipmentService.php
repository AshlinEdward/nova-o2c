<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\SalesOrderItemAllocation;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipmentService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {
    }


    public function createShipment(
        int $orderId,
        int $warehouseId,
        array $items,
        int $userId
    ): Shipment {
        return DB::transaction(function () use (
            $orderId,
            $warehouseId,
            $items,
            $userId
        ) {
            $order = SalesOrder::whereKey($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($order->status, [
                'CONFIRMED',
                'PARTIALLY_SHIPPED',
            ])) {
                throw ValidationException::withMessages([
                    'order' => 'Only confirmed orders can be shipped.',
                ]);
            }

            if (empty($items)) {
                throw ValidationException::withMessages([
                    'items' => 'At least one shipment item is required.',
                ]);
            }

            $itemIds = array_column($items, 'sales_order_item_id');

            if (count($itemIds) !== count(array_unique($itemIds))) {
                throw ValidationException::withMessages([
                    'items' => 'A product order line cannot appear twice in one shipment.',
                ]);
            }

            $user = User::findOrFail($userId);

            if ($user->company_id !== $order->company_id) {
                throw ValidationException::withMessages([
                    'user' => 'You cannot ship another company’s order.',
                ]);
            }

            $warehouse = Warehouse::whereKey($warehouseId)
                ->where('company_id', $order->company_id)
                ->first();

            if (!$warehouse) {
                throw ValidationException::withMessages([
                    'warehouse' => 'Warehouse does not belong to this order company.',
                ]);
            }

            $shipment = Shipment::create([
                'company_id' => $order->company_id,
                'sales_order_id' => $order->id,
                'warehouse_id' => $warehouseId,
                'shipment_number' => 'TEMP-' . uniqid(),
                'shipment_date' => now()->toDateString(),
                'status' => 'SHIPPED',
                'shipped_by' => $userId,
            ]);

            $shipment->update([
                'shipment_number' => 'SHP/' .
                    $order->company->code . '/' .
                    now()->format('Ymd') . '/' .
                    str_pad((string) $shipment->id, 6, '0', STR_PAD_LEFT),
            ]);

            foreach ($items as $item) {
                $orderItem = $order->items()
                    ->whereKey($item['sales_order_item_id'])
                    ->firstOrFail();

                $quantity = (string) $item['quantity'];

                if (!is_numeric($quantity) || (float) $quantity <= 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Shipment quantity must be greater than zero.',
                    ]);
                }

                $allocation = SalesOrderItemAllocation::where(
                    'sales_order_item_id',
                    $orderItem->id
                )
                    ->where('warehouse_id', $warehouseId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $remainingReserved =
                    (float) $allocation->reserved_quantity -
                    (float) $allocation->shipped_quantity;

                if ((float) $quantity > $remainingReserved) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Shipment quantity exceeds reserved quantity.',
                    ]);
                }

                $this->inventoryService->shipStock(
                    $order->company_id,
                    $warehouseId,
                    $orderItem->product_id,
                    $quantity,
                    $userId,
                    'SHIPMENT',
                    $shipment->id
                );

                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'sales_order_item_id' => $orderItem->id,
                    'quantity' => $quantity,
                ]);

                $allocation->shipped_quantity =
                    (float) $allocation->shipped_quantity +
                    (float) $quantity;

                $allocation->save();
            }

            $allItemsShipped = $order->items->every(
                function ($orderItem) {
                    $shipped = $orderItem->shipmentItems()->sum('quantity');

                    return (float) $shipped >= (float) $orderItem->quantity;
                }
            );

            $order->update([
                'status' => $allItemsShipped
                    ? 'SHIPPED'
                    : 'PARTIALLY_SHIPPED',
            ]);

            return $shipment->load('items');
        });

      
    }

}
