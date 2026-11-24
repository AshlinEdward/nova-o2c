<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\CreditNote;
use App\Models\CustomerLedgerEntry;
use App\Models\InventoryMovement;
use App\Models\InventoryCostLayer;
use App\Models\InventoryLayerConsumption;
use App\Models\StockBalance;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    public function processReturn(
        int $invoiceId,
        array $items,
        string $reason,
        int $userId
    ): SalesReturn {
        return DB::transaction(function () use (
            $invoiceId,
            $items,
            $reason,
            $userId
        ) {
            // Load and lock the invoice to prevent concurrent returns.
            $invoice = Invoice::with([
                'customer',
                'company',
                'items',
                'shipment'
            ])->lockForUpdate()->findOrFail($invoiceId);

            if (!in_array($invoice->status, [
                'POSTED',
                'PARTIALLY_PAID',
                'PAID'
            ])) {
                throw ValidationException::withMessages([
                    'invoice' => 'This invoice cannot be returned.'
                ]);
            }

            if (empty($items)) {
                throw ValidationException::withMessages([
                    'items' => 'At least one return item is required.'
                ]);
            }

            if (trim($reason) === '') {
                throw ValidationException::withMessages([
                    'reason' => 'Return reason is required.'
                ]);
            }

            $user = User::findOrFail($userId);

            if ((int) $invoice->company_id !== (int) $user->company_id) {
                throw ValidationException::withMessages([
                    'invoice' => 'You cannot process returns for another company.'
                ]);
            }

            if (!$invoice->shipment) {
                throw ValidationException::withMessages([
                    'shipment' => 'Original shipment was not found.'
                ]);
            }

            $returnLines = [];
            $seenItemIds = [];

            $totalTaxable = 0;
            $totalCgst = 0;
            $totalSgst = 0;
            $totalIgst = 0;
            $totalCogs = 0;

            // Validate each invoice item and calculate return values.
            foreach ($items as $input) {
                $invoiceItemId = (int) ($input['invoice_item_id'] ?? 0);
                $quantity = (float) ($input['quantity'] ?? 0);

                if (in_array($invoiceItemId, $seenItemIds, true)) {
                    throw ValidationException::withMessages([
                        'items' => 'Duplicate invoice items are not allowed.'
                    ]);
                }

                $seenItemIds[] = $invoiceItemId;

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Return quantity must be greater than zero.'
                    ]);
                }

                $invoiceItem = $invoice->items->firstWhere(
                    'id',
                    $invoiceItemId
                );

                if (!$invoiceItem) {
                    throw ValidationException::withMessages([
                        'invoice_item_id' => 'The item does not belong to this invoice.'
                    ]);
                }

                // Check the quantity returned in previous transactions.
                $previouslyReturned = (float) SalesReturnItem::where(
                    'invoice_item_id',
                    $invoiceItem->id
                )->sum('quantity');

                $remainingQuantity = max(
                    0,
                    (float) $invoiceItem->quantity - $previouslyReturned
                );

                if ($quantity > $remainingQuantity) {
                    throw ValidationException::withMessages([
                        'quantity' => "Only {$remainingQuantity} quantity can be returned for {$invoiceItem->product_name}."
                    ]);
                }

                // Calculate proportional taxable value and GST.
                $ratio = $quantity / (float) $invoiceItem->quantity;

                $taxable = round(
                    (float) $invoiceItem->taxable_amount * $ratio,
                    2,
                    PHP_ROUND_HALF_UP
                );

                $cgst = round(
                    (float) $invoiceItem->cgst_amount * $ratio,
                    2,
                    PHP_ROUND_HALF_UP
                );

                $sgst = round(
                    (float) $invoiceItem->sgst_amount * $ratio,
                    2,
                    PHP_ROUND_HALF_UP
                );

                $igst = round(
                    (float) $invoiceItem->igst_amount * $ratio,
                    2,
                    PHP_ROUND_HALF_UP
                );

                $lineTotal = round(
                    $taxable + $cgst + $sgst + $igst,
                    2,
                    PHP_ROUND_HALF_UP
                );

                $returnLines[] = [
                    'invoice_item' => $invoiceItem,
                    'quantity' => $quantity,
                    'taxable' => $taxable,
                    'cgst' => $cgst,
                    'sgst' => $sgst,
                    'igst' => $igst,
                    'line_total' => $lineTotal,
                ];

                $totalTaxable += $taxable;
                $totalCgst += $cgst;
                $totalSgst += $sgst;
                $totalIgst += $igst;
            }

            // Create the return header.
            $salesReturn = SalesReturn::create([
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'return_number' => 'RET/' .
                    $invoice->company->invoice_prefix . '/' .
                    now()->format('YmdHisv'),
                'return_date' => now()->toDateString(),
                'reason' => trim($reason),
                'status' => 'POSTED',
                'taxable_amount' => $totalTaxable,
                'cgst_amount' => $totalCgst,
                'sgst_amount' => $totalSgst,
                'igst_amount' => $totalIgst,
                'grand_total' => round(
                    $totalTaxable + $totalCgst + $totalSgst + $totalIgst,
                    2,
                    PHP_ROUND_HALF_UP
                ),
                'cogs_reversed' => 0,
                'created_by' => $userId,
            ]);

            // Restore stock and create return item records.
            foreach ($returnLines as $line) {
                $invoiceItem = $line['invoice_item'];

                $returnCogs = $this->restoreReturnedStock(
                    $invoice,
                    $invoiceItem,
                    $line['quantity'],
                    $userId,
                    $salesReturn->id
                );

                $totalCogs += $returnCogs;

                SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'invoice_item_id' => $invoiceItem->id,
                    'sales_order_item_id' => $invoiceItem->sales_order_item_id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $invoiceItem->unit_price,
                    'taxable_amount' => $line['taxable'],
                    'cgst_amount' => $line['cgst'],
                    'sgst_amount' => $line['sgst'],
                    'igst_amount' => $line['igst'],
                    'line_total' => $line['line_total'],
                    'cogs_reversed' => $returnCogs,
                ]);
            }

            $salesReturn->update([
                'cogs_reversed' => round($totalCogs, 2, PHP_ROUND_HALF_UP),
            ]);

            // Create the credit note.
            $creditNote = CreditNote::create([
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'sales_return_id' => $salesReturn->id,
                'credit_note_number' => 'CN/' .
                    $invoice->company->invoice_prefix . '/' .
                    now()->format('YmdHisv'),
                'credit_note_date' => now()->toDateString(),
                'taxable_amount' => $totalTaxable,
                'cgst_amount' => $totalCgst,
                'sgst_amount' => $totalSgst,
                'igst_amount' => $totalIgst,
                'grand_total' => $salesReturn->grand_total,
                'status' => 'POSTED',
                'created_by' => $userId,
            ]);

            // Reduce the original invoice outstanding balance.
            $invoiceLedger = CustomerLedgerEntry::where(
                'company_id',
                $invoice->company_id
            )
                ->where('customer_id', $invoice->customer_id)
                ->where('entry_type', 'INVOICE')
                ->where('reference_type', Invoice::class)
                ->where('reference_id', $invoice->id)
                ->lockForUpdate()
                ->first();

            if ($invoiceLedger) {
                $invoiceLedger->outstanding_amount = max(
                    0,
                    (float) $invoiceLedger->outstanding_amount -
                    (float) $salesReturn->grand_total
                );

                $invoiceLedger->save();
            }

            // Record the credit note in the customer ledger.
            CustomerLedgerEntry::create([
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'entry_type' => 'CREDIT_NOTE',
                'amount' => -$salesReturn->grand_total,
                'outstanding_amount' => 0,
                'reference_type' => CreditNote::class,
                'reference_id' => $creditNote->id,
                'entry_date' => now()->toDateString(),
                'due_date' => null,
                'description' => 'Credit note for return ' .
                    $salesReturn->return_number,
            ]);

            return $salesReturn->fresh([
                'items',
                'creditNote'
            ]);
        });
    }

    /**
     * Restore stock to the original shipment warehouse
     * and reverse the original FIFO cost.
     */
    private function restoreReturnedStock(
        Invoice $invoice,
        $invoiceItem,
        float $quantity,
        int $userId,
        int $returnId
    ): float {
        $shipment = $invoice->shipment;

        // Invoice items store product_code, not product_id.
        $product = Product::where(
            'company_id',
            $invoice->company_id
        )
            ->where('code', $invoiceItem->product_code)
            ->firstOrFail();

        // Find the original shipment movement.
        $shipMovement = InventoryMovement::where(
            'company_id',
            $invoice->company_id
        )
            ->where('warehouse_id', $shipment->warehouse_id)
            ->where('product_id', $product->id)
            ->where('movement_type', 'SHIP')
            ->where('reference_type', 'SHIPMENT')
            ->where('reference_id', $shipment->id)
            ->lockForUpdate()
            ->first();

        if (!$shipMovement) {
            throw ValidationException::withMessages([
                'inventory' => 'Original shipment inventory movement was not found.'
            ]);
        }

        // Get original FIFO consumption, newest consumed layer first.
        $consumptions = InventoryLayerConsumption::where(
            'inventory_movement_id',
            $shipMovement->id
        )
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();

        // Calculate how much of this invoice item was returned earlier.
        $previouslyReturned = (float) SalesReturnItem::where(
            'invoice_item_id',
            $invoiceItem->id
        )->sum('quantity');

        $alreadyAllocatedToReturns = $previouslyReturned;
        $remainingToRestore = $quantity;
        $cogsReversed = 0;

        foreach ($consumptions as $consumption) {
            $consumedQuantity = (float) $consumption->quantity;

            // Allocate previous returns against the newest consumed layers.
            $previouslyRestoredFromLayer = min(
                $alreadyAllocatedToReturns,
                $consumedQuantity
            );

            $alreadyAllocatedToReturns -= $previouslyRestoredFromLayer;

            $availableToRestoreFromLayer =
                $consumedQuantity - $previouslyRestoredFromLayer;

            if ($remainingToRestore <= 0) {
                break;
            }

            if ($availableToRestoreFromLayer <= 0) {
                continue;
            }

            $restoreQuantity = min(
                $remainingToRestore,
                $availableToRestoreFromLayer
            );

            $layer = InventoryCostLayer::lockForUpdate()->findOrFail(
                $consumption->inventory_cost_layer_id
            );

            // Add returned quantity back to its original cost layer.
            $layer->remaining_quantity =
                (float) $layer->remaining_quantity + $restoreQuantity;

            $layer->save();

            $cogsReversed +=
                $restoreQuantity * (float) $consumption->unit_cost;

            $remainingToRestore -= $restoreQuantity;
        }

        if ($remainingToRestore > 0) {
            throw ValidationException::withMessages([
                'inventory' => 'The full return quantity could not be matched to the original FIFO layers.'
            ]);
        }

        // Insert a new RETURN movement; never modify the old SHIP movement.
        InventoryMovement::create([
            'company_id' => $invoice->company_id,
            'warehouse_id' => $shipment->warehouse_id,
            'product_id' => $product->id,
            'user_id' => $userId,
            'movement_type' => 'RETURN',
            'quantity' => $quantity,
            'reference_type' => 'SALES_RETURN',
            'reference_id' => $returnId,
            'notes' => 'Customer return against invoice ' .
                $invoice->invoice_number,
        ]);

        // Returned stock becomes available, not reserved.
        $balance = StockBalance::where(
            'company_id',
            $invoice->company_id
        )
            ->where('warehouse_id', $shipment->warehouse_id)
            ->where('product_id', $product->id)
            ->lockForUpdate()
            ->firstOrFail();

        $balance->on_hand =
            (float) $balance->on_hand + $quantity;

        $balance->available =
            (float) $balance->available + $quantity;

        $balance->save();

        return round($cogsReversed, 2, PHP_ROUND_HALF_UP);
    }
}
