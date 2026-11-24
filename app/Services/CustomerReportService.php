<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use Illuminate\Support\Facades\DB;

class CustomerReportService
{
    
    public function getCustomerSummary(int $companyId, int $customerId): array
    {
        $customer = Customer::where('company_id', $companyId)
            ->whereKey($customerId)
            ->firstOrFail();

        $invoices = CustomerLedgerEntry::where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->where('entry_type', 'INVOICE')
            ->get();

        $totalInvoiced = (float) $invoices->sum('amount');

        $totalOutstanding = (float) $invoices->sum('outstanding_amount');

        $totalPayments = (float) CustomerLedgerEntry::where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->where('entry_type', 'PAYMENT')
            ->sum('amount');

        $totalAllocated = (float) $invoices->sum('amount')
            - $totalOutstanding;

        $advanceBalance = (float) \App\Models\Payment::where(
            'company_id',
            $companyId
        )
        ->where('customer_id', $customer->id)
        ->get()
        ->sum(function ($payment) {
            $allocated = (float) $payment->allocations()
                ->sum('allocated_amount');

            return max(0, (float) $payment->amount - $allocated);
        });

        return [
            'customer_code' => $customer->code,
            'customer_name' => $customer->name,
            'total_invoiced' => round($totalInvoiced, 2),
            'total_payments' => round($totalPayments, 2),
            'total_outstanding' => round($totalOutstanding, 2),
            'advance_balance' => round($advanceBalance, 2),
        ];
    }


    public function getAgeingReport(int $companyId, int $customerId): array
    {
        $customer = Customer::where('company_id', $companyId)
            ->whereKey($customerId)
            ->firstOrFail();

        $today = now()->startOfDay();

        $invoices = CustomerLedgerEntry::where('company_id', $companyId)
            ->where('customer_id', $customer->id)
            ->where('entry_type', 'INVOICE')
            ->where('outstanding_amount', '>', 0)
            ->whereNotNull('due_date')
            ->get();

        $ageing = [
            'current' => 0,
            '1_30_days' => 0,
            '31_60_days' => 0,
            '61_90_days' => 0,
            'over_90_days' => 0,
        ];

        foreach ($invoices as $invoice) {
            $dueDate = \Carbon\Carbon::parse($invoice->due_date)
                ->startOfDay();

            $daysOverdue = $dueDate->isPast()
                ? (int) $dueDate->diffInDays($today)
                : 0;

            $amount = (float) $invoice->outstanding_amount;

            if ($daysOverdue === 0) {
                $ageing['current'] += $amount;
            } elseif ($daysOverdue <= 30) {
                $ageing['1_30_days'] += $amount;
            } elseif ($daysOverdue <= 60) {
                $ageing['31_60_days'] += $amount;
            } elseif ($daysOverdue <= 90) {
                $ageing['61_90_days'] += $amount;
            } else {
                $ageing['over_90_days'] += $amount;
            }
        }

        foreach ($ageing as $bucket => $amount) {
            $ageing[$bucket] = round($amount, 2);
        }

        return [
            'customer_code' => $customer->code,
            'customer_name' => $customer->name,
            'as_of_date' => $today->toDateString(),
            'ageing' => $ageing,
        ];
    }


}
