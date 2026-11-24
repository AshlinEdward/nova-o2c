<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceSequence;
use App\Models\Shipment;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\CustomerLedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function createInvoice(int $shipmentId, int $userId): Invoice
    {
        return DB::transaction(function () use ($shipmentId, $userId) {

            // Lock shipment to prevent concurrent invoice creation.
            $shipment = Shipment::with([
                'salesOrder.customer',
                'salesOrder.company',
                'items.salesOrderItem',
            ])->lockForUpdate()->findOrFail($shipmentId);

            // Prevent duplicate invoices.
            if (Invoice::where('shipment_id', $shipment->id)->exists()) {
                throw ValidationException::withMessages([
                    'shipment' => 'An invoice already exists for this shipment.',
                ]);
            }

            if ($shipment->status !== 'SHIPPED') {
                throw ValidationException::withMessages([
                    'shipment' => 'Only shipped shipments can be invoiced.',
                ]);
            }

            if ($shipment->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'shipment' => 'Cannot invoice an empty shipment.',
                ]);
            }

            $order = $shipment->salesOrder;
            $customer = $order->customer;
            $company = $order->company;

            // Validate company ownership.
            if (
                (int) $order->company_id !== (int) $customer->company_id ||
                (int) $order->company_id !== (int) $company->id ||
                (int) $shipment->company_id !== (int) $company->id
            ) {
                throw ValidationException::withMessages([
                    'company' => 'Company mismatch detected.',
                ]);
            }

            $user = User::findOrFail($userId);

            if ((int) $user->company_id !== (int) $company->id) {
                throw ValidationException::withMessages([
                    'user' => 'You cannot create invoices for another company.',
                ]);
            }

            // Calculate financial year (April to March).
            $today = now();

            if ($today->month >= 4) {
                $startYear = $today->year;
                $endYear = $today->year + 1;
            } else {
                $startYear = $today->year - 1;
                $endYear = $today->year;
            }

            $financialYear = $startYear . '-' . substr((string) $endYear, -2);

            // Create or lock the company's invoice sequence.
            InvoiceSequence::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'financial_year' => $financialYear,
                ],
                ['last_number' => 0]
            );

            $sequence = InvoiceSequence::where('company_id', $company->id)
                ->where('financial_year', $financialYear)
                ->lockForUpdate()
                ->firstOrFail();

            $nextNumber = $sequence->last_number + 1;

            $sequence->update([
                'last_number' => $nextNumber,
            ]);

            $invoiceNumber = sprintf(
                'INV/%s/%s/%05d',
                $company->invoice_prefix,
                $financialYear,
                $nextNumber
            );

            // Create invoice header.
            $invoice = Invoice::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'sales_order_id' => $order->id,
                'shipment_id' => $shipment->id,
                'invoice_number' => $invoiceNumber,
                'financial_year' => $financialYear,
                'invoice_date' => $today->toDateString(),
                'due_date' => $today->copy()
                    ->addDays($customer->payment_terms_days)
                    ->toDateString(),
                'status' => 'POSTED',
                'created_by' => $userId,
            ]);

            // Initialize invoice totals.
            $taxableTotal = 0;
            $cgstTotal = 0;
            $sgstTotal = 0;
            $igstTotal = 0;
            $grandTotal = 0;
            $cogsTotal = 0;

            foreach ($shipment->items as $shipmentItem) {

                $orderItem = $shipmentItem->salesOrderItem;

                $orderedQuantity = (float) $orderItem->quantity;
                $shippedQuantity = (float) $shipmentItem->quantity;

                if ($orderedQuantity <= 0 || $shippedQuantity <= 0) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Invalid invoice quantity.',
                    ]);
                }

                if ($shippedQuantity > $orderedQuantity) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Shipped quantity exceeds ordered quantity.',
                    ]);
                }

                // Calculate the proportion being invoiced.
                $ratio = $shippedQuantity / $orderedQuantity;

                // Calculate the proportional taxable and GST amounts.
                $taxable = round(
                    (float) $orderItem->taxable_amount * $ratio,
                    2,
                    PHP_ROUND_HALF_UP
                );

                $cgst = round(
                    (float) $orderItem->cgst_amount * $ratio,
                    2,
                    PHP_ROUND_HALF_UP
                );

                $sgst = round(
                    (float) $orderItem->sgst_amount * $ratio,
                    2,
                    PHP_ROUND_HALF_UP
                );

                $igst = round(
                    (float) $orderItem->igst_amount * $ratio,
                    2,
                    PHP_ROUND_HALF_UP
                );

                $lineTotal = round(
                    $taxable + $cgst + $sgst + $igst,
                    2,
                    PHP_ROUND_HALF_UP
                );

                // Calculate FIFO COGS for this shipment and product.
                $productShipmentQuantity = (float) $shipment->items
                    ->filter(function ($row) use ($orderItem) {
                        return (int) $row->salesOrderItem->product_id
                            === (int) $orderItem->product_id;
                    })
                    ->sum('quantity');

                $productCogs = (float) DB::table('inventory_layer_consumptions')
                    ->join(
                        'inventory_movements',
                        'inventory_movements.id',
                        '=',
                        'inventory_layer_consumptions.inventory_movement_id'
                    )
                    ->where('inventory_movements.reference_type', 'SHIPMENT')
                    ->where('inventory_movements.reference_id', $shipment->id)
                    ->where('inventory_movements.product_id', $orderItem->product_id)
                    ->sum(DB::raw(
                        'inventory_layer_consumptions.quantity * inventory_layer_consumptions.unit_cost'
                    ));

                $cogs = $productShipmentQuantity > 0
                    ? round(
                        $productCogs * ($shippedQuantity / $productShipmentQuantity),
                        2,
                        PHP_ROUND_HALF_UP
                    )
                    : 0;

                $margin = round(
                    $taxable - $cogs,
                    2,
                    PHP_ROUND_HALF_UP
                );

                // Create invoice line.
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'shipment_item_id' => $shipmentItem->id,
                    'sales_order_item_id' => $orderItem->id,
                    'product_code' => $orderItem->product_code,
                    'product_name' => $orderItem->product_name,
                    'quantity' => $shippedQuantity,
                    'is_free' => $orderItem->is_free,
                    'unit_price' => $orderItem->unit_price,
                    'taxable_amount' => $taxable,
                    'cgst_amount' => $cgst,
                    'sgst_amount' => $sgst,
                    'igst_amount' => $igst,
                    'line_total' => $lineTotal,
                    'cogs_amount' => $cogs,
                    'gross_margin' => $margin,
                ]);

                // Accumulate invoice totals.
                $taxableTotal += $taxable;
                $cgstTotal += $cgst;
                $sgstTotal += $sgst;
                $igstTotal += $igst;
                $grandTotal += $lineTotal;
                $cogsTotal += $cogs;
            }

            // Update invoice header totals.
            $invoice->update([
                'taxable_amount' => $taxableTotal,
                'cgst_amount' => $cgstTotal,
                'sgst_amount' => $sgstTotal,
                'igst_amount' => $igstTotal,
                'grand_total' => $grandTotal,
                'cogs_amount' => $cogsTotal,
                'gross_margin' => round(
                    $taxableTotal - $cogsTotal,
                    2,
                    PHP_ROUND_HALF_UP
                ),
            ]);

            // Post invoice to customer ledger.
            CustomerLedgerEntry::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'entry_type' => 'INVOICE',
                'amount' => $grandTotal,
                'outstanding_amount' => $grandTotal,
                'reference_type' => Invoice::class,
                'reference_id' => $invoice->id,
                'entry_date' => $today->toDateString(),
                'due_date' => $invoice->due_date,
                'description' => 'Invoice ' . $invoiceNumber,
            ]);

             $invoice = $invoice->fresh()->load('items');

            $destination = config('services.webhooks.destination');

            if (!empty($destination)) {
                DB::afterCommit(function () use ($invoice, $destination) {
                    app(WebhookService::class)->queue(
                        $invoice->company_id,
                        'invoice.created',
                        $destination,
                        [
                            'invoice_id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'customer_id' => $invoice->customer_id,
                            'sales_order_id' => $invoice->sales_order_id,
                            'shipment_id' => $invoice->shipment_id,
                            'grand_total' => $invoice->grand_total,
                            'invoice_date' => $invoice->invoice_date,
                        ]
                    );
                });
            }

            return $invoice;
        });
    }
}
