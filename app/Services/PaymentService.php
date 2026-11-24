<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function recordPayment(
        Customer $customer,
        array $data,
        int $userId
    ): Payment {
        return DB::transaction(function () use ($customer, $data, $userId) {

            $customer = Customer::whereKey($customer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$customer->is_active) {
                throw ValidationException::withMessages([
                    'customer' => 'This customer is inactive.',
                ]);
            }

            if (
                !isset($data['amount']) ||
                !is_numeric($data['amount']) ||
                (float) $data['amount'] <= 0
            ) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount must be greater than zero.',
                ]);
            }

            if (
                !isset($data['payment_method']) ||
                !in_array($data['payment_method'], [
                    'CASH',
                    'BANK_TRANSFER',
                    'UPI',
                    'CHEQUE',
                ], true)
            ) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Invalid payment method.',
                ]);
            }

            $user = User::findOrFail($userId);

            if ($user->company_id !== $customer->company_id) {
                throw ValidationException::withMessages([
                    'user' => 'You cannot record payments for another company.',
                ]);
            }

            $payment = Payment::create([
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'payment_number' => 'TEMP-' . uniqid(),
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount' => round((float) $data['amount'], 2),
                'payment_method' => $data['payment_method'],
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'received_by' => $userId,
            ]);

            $payment->update([
                'payment_number' => 'PAY/' .
                    $customer->company_id . '/' .
                    now()->format('Ymd') . '/' .
                    str_pad($payment->id, 6, '0', STR_PAD_LEFT),
            ]);

            CustomerLedgerEntry::create([
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'entry_type' => 'PAYMENT',
                'amount' => $payment->amount,
                'outstanding_amount' => 0,
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
                'entry_date' => $payment->payment_date,
                'description' => 'Payment received ' . $payment->payment_number,
            ]);

            return $payment->fresh();
        });
    }

    public function allocatePayment(
        int $paymentId,
        array $allocations
    ): Payment {
        return DB::transaction(function () use ($paymentId, $allocations) {

            $payment = Payment::whereKey($paymentId)
                ->lockForUpdate()
                ->firstOrFail();

            if (empty($allocations)) {
                throw ValidationException::withMessages([
                    'allocations' => 'At least one allocation is required.',
                ]);
            }

            $alreadyAllocated = (float) $payment->allocations()
                ->sum('allocated_amount');

            $remainingPayment = round(
                (float) $payment->amount - $alreadyAllocated,
                2
            );

            foreach ($allocations as $allocation) {

                $hasInvoice = !empty($allocation['invoice_id']);
                $hasLedgerEntry = !empty($allocation['ledger_entry_id']);

                if ($hasInvoice === $hasLedgerEntry) {
                    throw ValidationException::withMessages([
                        'allocation' => 'Provide exactly one invoice_id or ledger_entry_id.',
                    ]);
                }

                $amount = round((float) ($allocation['amount'] ?? 0), 2);

                if ($amount <= 0 || $amount > $remainingPayment) {
                    throw ValidationException::withMessages([
                        'amount' => 'Invalid allocation amount or payment balance exceeded.',
                    ]);
                }

                if ($hasInvoice) {

                    $invoice = Invoice::whereKey($allocation['invoice_id'])
                        ->lockForUpdate()
                        ->first();

                    if (
                        !$invoice ||
                        $invoice->company_id !== $payment->company_id ||
                        $invoice->customer_id !== $payment->customer_id
                    ) {
                        throw ValidationException::withMessages([
                            'invoice_id' => 'Invoice does not belong to this customer and company.',
                        ]);
                    }

                    $ledgerEntry = CustomerLedgerEntry::where(
                        'company_id',
                        $payment->company_id
                    )
                        ->where('customer_id', $payment->customer_id)
                        ->where('entry_type', 'INVOICE')
                        ->where('reference_type', Invoice::class)
                        ->where('reference_id', $invoice->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$ledgerEntry) {
                        throw ValidationException::withMessages([
                            'invoice_id' => 'Invoice ledger entry was not found.',
                        ]);
                    }

                    $allocationKey = [
                        'payment_id' => $payment->id,
                        'invoice_id' => $invoice->id,
                    ];

                } else {

                    $ledgerEntry = CustomerLedgerEntry::whereKey(
                        $allocation['ledger_entry_id']
                    )
                        ->where('company_id', $payment->company_id)
                        ->where('customer_id', $payment->customer_id)
                        ->where('entry_type', 'INVOICE')
                        ->lockForUpdate()
                        ->first();

                    if (
                        !$ledgerEntry ||
                        $ledgerEntry->reference_type !== 'OPENING_INVOICE'
                    ) {
                        throw ValidationException::withMessages([
                            'ledger_entry_id' => 'Opening invoice does not belong to this customer and company.',
                        ]);
                    }

                    $invoice = null;

                    $allocationKey = [
                        'payment_id' => $payment->id,
                        'ledger_entry_id' => $ledgerEntry->id,
                    ];
                }

                if ($amount > (float) $ledgerEntry->outstanding_amount) {
                    throw ValidationException::withMessages([
                        'amount' => 'Allocation exceeds the invoice outstanding balance.',
                    ]);
                }

                $paymentAllocation = PaymentAllocation::firstOrNew(
                    $allocationKey
                );

                $paymentAllocation->invoice_id = $invoice?->id;
                $paymentAllocation->ledger_entry_id =
                    $invoice ? null : $ledgerEntry->id;

                $paymentAllocation->allocated_amount = round(
                    (float) $paymentAllocation->allocated_amount + $amount,
                    2
                );

                $paymentAllocation->save();

                $ledgerEntry->outstanding_amount = round(
                    (float) $ledgerEntry->outstanding_amount - $amount,
                    2
                );

                $ledgerEntry->save();

                $remainingPayment = round(
                    $remainingPayment - $amount,
                    2
                );

                if ($invoice) {
                    $invoice->status = $ledgerEntry->outstanding_amount <= 0
                        ? 'PAID'
                        : 'PARTIALLY_PAID';

                    $invoice->save();
                }
            }

            return $payment->fresh()->load('allocations');
        });
    }
}
