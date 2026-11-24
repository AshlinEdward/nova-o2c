<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\StockBalance;
use App\Models\SalesOrderItemAllocation;
use App\Models\Warehouse;
use App\Models\SalesOrderCreditReview;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use App\Notifications\CreditHoldNotification;


class SalesOrderService
{
    public function __construct(
        protected PricingService $pricingService,
        protected CreditService $creditService,
        protected InventoryService $inventoryService
    ) {}

    public function createDraft(
        Customer $customer,
        array $items,
        int $userId
    ): SalesOrder {
        return DB::transaction(function () use (
            $customer,
            $items,
            $userId
        ) {
            $company = $customer->company;

            if (!$company || !$company->is_active) {
                throw new InvalidArgumentException(
                    'Customer company is not active.'
                );
            }

            if ($items === []) {
                throw new InvalidArgumentException(
                    'An order must contain at least one item.'
                );
            }

            $order = SalesOrder::create([
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'order_number' => 'TEMP-' . uniqid(),
                'status' => 'DRAFT',
                'order_date' => now()->toDateString(),
                'created_by' => $userId,
            ]);

            $subtotal = 0;
            $discountTotal = 0;
            $taxTotal = 0;
            $grandTotal = 0;

            foreach ($items as $item) {
                $product = Product::where(
                    'company_id',
                    $customer->company_id
                )
                    ->whereKey($item['product_id'])
                    ->firstOrFail();

                $quantity = (float) $item['quantity'];

                if ($quantity <= 0) {
                    throw new InvalidArgumentException(
                        'Order quantity must be positive.'
                    );
                }

                $calculation = $this->pricingService->calculate(
                    $customer,
                    $product,
                    $quantity
                );

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'product_name' => $product->name,
                    'hsn_code' => $product->hsn_code,
                    'quantity' => $quantity,
                    'is_free' => false,
                    'unit_price' => $calculation['unit_price'],
                    'gross_amount' => $calculation['gross_amount'],
                    'slab_discount_percent' => $calculation['slab_discount_percent'],
                    'slab_discount_amount' => $calculation['slab_discount_amount'],
                    'promo_discount_percent' => $calculation['promo_discount_percent'],
                    'promo_discount_amount' => $calculation['promo_discount_amount'],
                    'taxable_amount' => $calculation['taxable_amount'],
                    'gst_rate' => $calculation['gst_rate'],
                    'cgst_amount' => $calculation['cgst_amount'],
                    'sgst_amount' => $calculation['sgst_amount'],
                    'igst_amount' => $calculation['igst_amount'],
                    'line_total' => $calculation['line_total'],
                    'pricing_breakdown' => $calculation['pricing_breakdown'],
                ]);

                $subtotal += $calculation['gross_amount'];

                $discountTotal +=
                    $calculation['slab_discount_amount'] +
                    $calculation['promo_discount_amount'];

                $taxTotal +=
                    $calculation['cgst_amount'] +
                    $calculation['sgst_amount'] +
                    $calculation['igst_amount'];

                $grandTotal += $calculation['line_total'];

                // Store free goods as a separate zero-value line.
                if ($calculation['free_quantity'] > 0) {
                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_code' => $product->code,
                        'product_name' => $product->name . ' (FREE)',
                        'hsn_code' => $product->hsn_code,
                        'quantity' => $calculation['free_quantity'],
                        'is_free' => true,
                        'unit_price' => 0,
                        'gross_amount' => 0,
                        'taxable_amount' => 0,
                        'gst_rate' => $calculation['gst_rate'],
                        'cgst_amount' => 0,
                        'sgst_amount' => 0,
                        'igst_amount' => 0,
                        'line_total' => 0,
                        'pricing_breakdown' => [
                            'promotion_code' =>
                                $calculation['pricing_breakdown']['free_promotion_code'],
                            'free_item' => true,
                        ],
                    ]);
                }
            }

            $order->update([
                'order_number' => sprintf(
                    'SO/%s/%s/%06d',
                    $company->code,
                    now()->format('Ymd'),
                    $order->id
                ),
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
            ]);

            return $order->load('items');
        });
    }

    public function confirmOrder(
        int $orderId,
        int $userId,
        bool $creditOverride = false
    ): SalesOrder {
        return DB::transaction(function () use (
            $orderId,
            $userId,
            $creditOverride
        ) {
            $order = SalesOrder::whereKey($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'DRAFT') {
                throw ValidationException::withMessages([
                    'order' => 'Only draft orders can be confirmed.',
                ]);
            }

            $customer = Customer::whereKey($order->customer_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($customer->company_id !== $order->company_id) {
                throw ValidationException::withMessages([
                    'customer' => 'Customer does not belong to this company.',
                ]);
            }

            // Normal confirmation must respect the credit limit.
            // Only the authorized manager release can bypass this check.
            $exposure = $this->creditService->getExposure($customer);

            if (
                !$creditOverride &&
                ($exposure + (float) $order->grand_total) >
                (float) $customer->credit_limit
            ) {
                $order->update([
                    'status' => 'CREDIT_HOLD',
                    'credit_hold_at' => now(),
                ]);

                $customerName = $customer->name ?? 'Unknown Customer';

                User::where('company_id', $order->company_id)
                    ->whereHas('roles', function ($query) {
                        $query->where('name', 'Sales Manager');
                    })
                    ->get()
                    ->each(function ($manager) use ($order, $customerName) {
                        $manager->notify(new CreditHoldNotification(
                            $order->id,
                            $order->order_number,
                            $customerName,
                            (float) $order->grand_total,
                            'Order requires credit review.'
                        ));
                    });

                return $order->fresh();
            }

            // Reserve stock from preferred warehouse first,
            // followed by other warehouses belonging to the company.
            foreach ($order->items as $item) {
                $remaining = (float) $item->quantity;

                $stockRows = StockBalance::query()
                    ->where('company_id', $order->company_id)
                    ->where('product_id', $item->product_id)
                    ->where('available', '>', 0)
                    ->orderByRaw(
                        'CASE WHEN warehouse_id = ? THEN 0 ELSE 1 END',
                        [$customer->preferred_warehouse_id ?? 0]
                    )
                    ->orderBy('warehouse_id')
                    ->lockForUpdate()
                    ->get();

                foreach ($stockRows as $stock) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $reserveQuantity = min(
                        $remaining,
                        (float) $stock->available
                    );

                    $this->inventoryService->reserveStock(
                        $order->company_id,
                        $stock->warehouse_id,
                        $item->product_id,
                        $reserveQuantity,
                        $userId,
                        'SalesOrder',
                        $order->id
                    );

                    SalesOrderItemAllocation::create([
                        'sales_order_item_id' => $item->id,
                        'warehouse_id' => $stock->warehouse_id,
                        'reserved_quantity' => $reserveQuantity,
                        'shipped_quantity' => 0,
                        'backorder_quantity' => 0,
                    ]);

                    $remaining -= $reserveQuantity;
                }

                // Record any quantity that could not be reserved.
                if ($remaining > 0) {
                    $backorderWarehouseId =
                        $customer->preferred_warehouse_id
                        ?? Warehouse::where(
                            'company_id',
                            $order->company_id
                        )->value('id');

                    if (!$backorderWarehouseId) {
                        throw new \RuntimeException(
                            'No warehouse is configured for this company.'
                        );
                    }

                    $allocation = SalesOrderItemAllocation::firstOrNew([
                        'sales_order_item_id' => $item->id,
                        'warehouse_id' => $backorderWarehouseId,
                    ]);

                    $allocation->reserved_quantity =
                        (float) ($allocation->reserved_quantity ?? 0);

                    $allocation->shipped_quantity =
                        (float) ($allocation->shipped_quantity ?? 0);

                    $allocation->backorder_quantity =
                        (float) ($allocation->backorder_quantity ?? 0)
                        + $remaining;

                    $allocation->save();
                }
            }

            $order->update([
                'status' => 'CONFIRMED',
                'confirmed_by' => $userId,
                'confirmed_at' => now(),
            ]);

            $confirmedOrder = $order->fresh()->load('items.allocations');

            $destination = config('services.webhooks.destination');

            if (!empty($destination)) {
                DB::afterCommit(function () use ($confirmedOrder, $destination) {
                    app(WebhookService::class)->queue(
                        $confirmedOrder->company_id,
                        'order.confirmed',
                        $destination,
                        [
                            'order_id' => $confirmedOrder->id,
                            'order_number' => $confirmedOrder->order_number,
                            'customer_id' => $confirmedOrder->customer_id,
                            'grand_total' => $confirmedOrder->grand_total,
                            'confirmed_at' => $confirmedOrder->confirmed_at,
                        ]
                    );
                });
            }

            return $confirmedOrder;
        });
    }

    public function releaseCreditHold(
        int $orderId,
        int $managerId,
        string $comment
    ): SalesOrder {
        return DB::transaction(function () use (
            $orderId,
            $managerId,
            $comment
        ) {
            $order = SalesOrder::whereKey($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            $manager = User::with('roles')->findOrFail($managerId);

            if (
                $manager->company_id !== $order->company_id ||
                !$manager->roles->contains('name', 'Sales Manager')
            ) {
                throw ValidationException::withMessages([
                    'manager' => 'Only a Sales Manager from the same company can release this order.',
                ]);
            }

            if ($order->status !== 'CREDIT_HOLD') {
                throw ValidationException::withMessages([
                    'order' => 'Only credit-held orders can be released.',
                ]);
            }

            if (trim($comment) === '') {
                throw ValidationException::withMessages([
                    'comment' => 'A release comment is mandatory.',
                ]);
            }

            // Temporarily return to DRAFT to reuse confirmation logic.
            $order->update(['status' => 'DRAFT']);

            $confirmedOrder = $this->confirmOrder(
                $order->id,
                $managerId,
                true
            );

            if ($confirmedOrder->status !== 'CONFIRMED') {
                throw ValidationException::withMessages([
                    'credit' => 'The order could not be released.',
                ]);
            }

            $confirmedOrder->update([
                'credit_hold_comment' => trim($comment),
            ]);

            SalesOrderCreditReview::create([
                'sales_order_id' => $order->id,
                'reviewed_by' => $managerId,
                'action' => 'RELEASED',
                'comment' => trim($comment),
                'reviewed_at' => now(),
            ]);

            User::where('company_id', $order->company_id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Sales Executive');
            })
            ->get()
            ->each(function ($salesUser) use ($confirmedOrder) {
                $salesUser->notify(new CreditHoldNotification(
                    $confirmedOrder->id,
                    $confirmedOrder->order_number,
                    $confirmedOrder->customer->name ?? 'Unknown Customer',
                    (float) $confirmedOrder->grand_total,
                    'Your credit-held order has been approved.'
                ));
            });

            return $confirmedOrder->fresh()->load(
                'items.allocations',
                'creditReviews'
            );
        });
    }

    public function rejectCreditHold(
        int $orderId,
        int $managerId,
        string $comment
    ): SalesOrder {
        return DB::transaction(function () use (
            $orderId,
            $managerId,
            $comment
        ) {
            $order = SalesOrder::whereKey($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            $manager = User::with('roles')->findOrFail($managerId);

            if (
                $manager->company_id !== $order->company_id ||
                !$manager->roles->contains('name', 'Sales Manager')
            ) {
                throw ValidationException::withMessages([
                    'manager' => 'Only a Sales Manager from the same company can reject this order.',
                ]);
            }

            if ($order->status !== 'CREDIT_HOLD') {
                throw ValidationException::withMessages([
                    'order' => 'Only credit-held orders can be rejected.',
                ]);
            }

            if (trim($comment) === '') {
                throw ValidationException::withMessages([
                    'comment' => 'A rejection reason is mandatory.',
                ]);
            }

            $order->update([
                'status' => 'REJECTED',
                'credit_hold_comment' => trim($comment),
            ]);

            SalesOrderCreditReview::create([
                'sales_order_id' => $order->id,
                'reviewed_by' => $managerId,
                'action' => 'REJECTED',
                'comment' => trim($comment),
                'reviewed_at' => now(),
            ]);
            User::where('company_id', $order->company_id)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'Sales Executive');
                })
                ->get()
                ->each(function ($salesUser) use ($order) {
                    $salesUser->notify(new CreditHoldNotification(
                        $order->id,
                        $order->order_number,
                        $order->customer->name ?? 'Unknown Customer',
                        (float) $order->grand_total,
                        'Your credit-held order has been rejected.'
                    ));
                });
            return $order->fresh()->load('creditReviews');
        });
    }
}
