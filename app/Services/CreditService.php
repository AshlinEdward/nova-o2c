<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\SalesOrder;

class CreditService
{
    public function getExposure(Customer $customer): float
    {
        // 1. Outstanding invoice balance.
        $unpaidInvoices = CustomerLedgerEntry::where(
            'company_id',
            $customer->company_id
        )
            ->where('customer_id', $customer->id)
            ->where('entry_type', 'INVOICE')
            ->sum('outstanding_amount');

        // 2. Remaining uninvoiced value of confirmed orders.
        $orders = SalesOrder::where(
            'company_id',
            $customer->company_id
        )
            ->where('customer_id', $customer->id)
            ->whereIn('status', [
                'CONFIRMED',
                'PARTIALLY_SHIPPED',
                'SHIPPED',
            ])
            ->withSum('invoices', 'grand_total')
            ->get();

        $uninvoicedOrders = 0;

        foreach ($orders as $order) {

            $invoicedAmount = (float) (
                $order->invoices_sum_grand_total ?? 0
            );

            $remainingAmount = max(
                0,
                (float) $order->grand_total - $invoicedAmount
            );

            $uninvoicedOrders += $remainingAmount;
        }

        return round(
            (float) $unpaidInvoices + $uninvoicedOrders,
            2
        );
    }

    public function getAvailableCredit(Customer $customer): float
    {
        return round(
            (float) $customer->credit_limit -
            $this->getExposure($customer),
            2
        );
    }

    public function canApprove(
        Customer $customer,
        float $orderAmount
    ): bool {
        return (
            $this->getExposure($customer) + $orderAmount
        ) <= (float) $customer->credit_limit;
    }
}
