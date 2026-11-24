<?php

namespace App\Services;

use App\Models\StockBalance;
use App\Models\InventoryCostLayer;
use App\Models\InvoiceItem;
use App\Models\SalesOrderItemAllocation;


class InventoryReportService
{
   
    public function getStockSummary(int $companyId)
    {
        return StockBalance::with(['warehouse', 'product'])
            ->where('company_id', $companyId)
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->get()
            ->map(function ($stock) {
                return [
                    'warehouse' => $stock->warehouse->name,
                    'product_code' => $stock->product->code,
                    'product_name' => $stock->product->name,
                    'on_hand' => (float) $stock->on_hand,
                    'reserved' => (float) $stock->reserved,
                    'available' => (float) $stock->available,
                ];
            });
    }


    public function getFifoValuation(int $companyId)
    {
        return InventoryCostLayer::with(['warehouse', 'product'])
            ->where('company_id', $companyId)
            ->where('remaining_quantity', '>', 0)
            ->orderBy('received_at')
            ->orderBy('id')
            ->get()
            ->map(function ($layer) {
                $quantity = (float) $layer->remaining_quantity;
                $unitCost = (float) $layer->unit_cost;

                return [
                    'warehouse' => $layer->warehouse->name,
                    'product_code' => $layer->product->code,
                    'product_name' => $layer->product->name,
                    'received_at' => $layer->received_at,
                    'remaining_quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'stock_value' => round($quantity * $unitCost, 2),
                ];
            });
    }


    public function getBackorderReport(int $companyId)
    {
        return SalesOrderItemAllocation::with([
            'salesOrderItem.salesOrder.customer',
            'salesOrderItem.product',
            'warehouse',
        ])
            ->where('backorder_quantity', '>', 0)
            ->whereHas('salesOrderItem.salesOrder', function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->whereIn('status', ['CONFIRMED', 'PARTIALLY_SHIPPED']);
            })
            ->get()
            ->map(function ($allocation) {
                $item = $allocation->salesOrderItem;
                $order = $item->salesOrder;

                return [
                    'order_number' => $order->order_number,
                    'customer' => $order->customer->name,
                    'product_code' => $item->product->code,
                    'product_name' => $item->product->name,
                    'warehouse' => $allocation->warehouse->name,
                    'backorder_quantity' => (float) $allocation->backorder_quantity,
                ];
            });
    }

        public function getProductMarginReport(int $companyId)
    {
        return InvoiceItem::with('invoice', 'salesOrderItem.product')
            ->whereHas('invoice', function ($query) use ($companyId) {
                $query->where('company_id', $companyId)->whereIn('status', ['POSTED', 'PARTIALLY_PAID', 'PAID']);
            })
            ->get()
            ->groupBy('product_code')
            ->map(function ($items) {
                return [
                    'product_code' => $items->first()->product_code,
                    'product_name' => $items->first()->product_name,
                    'invoiced_quantity' => (float) $items->sum('quantity'),
                    'sales_value' => round((float) $items->sum('taxable_amount'), 2),
                    'fifo_cost' => round((float) $items->sum('cogs_amount'), 2),
                    'gross_margin' => round((float) $items->sum('gross_margin'), 2),
                ];
            })
            ->values();
    }
}
