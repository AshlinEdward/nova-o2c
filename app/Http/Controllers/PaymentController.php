<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $payments = Payment::with(['customer', 'allocations'])
            ->where('company_id', $companyId)
            ->latest()
            ->get();

        $customers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('payments.index', compact('payments', 'customers'));
    }

    public function store(Request $request, PaymentService $paymentService)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', 'in:CASH,BANK_TRANSFER,UPI,CHEQUE'],
            'transaction_reference' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = Customer::where('company_id', $request->user()->company_id)
            ->where('is_active', true)
            ->findOrFail($data['customer_id']);

        $payment = $paymentService->recordPayment(
            $customer,
            $data,
            $request->user()->id
        );

        return redirect()
            ->route('payments.show', $payment->id)
            ->with('success', 'Payment recorded successfully.');
    }

    public function show(Request $request, int $id)
    {
        $payment = Payment::with([
                'customer',
                'receiver',
                'allocations.invoice',
                'allocations.ledgerEntry',
            ])
            ->where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        $allocated = (float) $payment->allocations->sum('allocated_amount');
        $remaining = round((float) $payment->amount - $allocated, 2);

        // Outstanding sales invoice ledger entries
        $invoiceEntries = CustomerLedgerEntry::where('company_id', $payment->company_id)
            ->where('customer_id', $payment->customer_id)
            ->where('entry_type', 'INVOICE')
            ->where('reference_type', Invoice::class)
            ->where('outstanding_amount', '>', 0)
            ->orderBy('due_date')
            ->get();

        // Fetch corresponding invoice records
        $invoiceMap = Invoice::where('company_id', $payment->company_id)
            ->where('customer_id', $payment->customer_id)
            ->whereIn('id', $invoiceEntries->pluck('reference_id'))
            ->get()
            ->keyBy('id');

        // Outstanding opening invoice ledger entries
        $openingEntries = CustomerLedgerEntry::where('company_id', $payment->company_id)
            ->where('customer_id', $payment->customer_id)
            ->where('entry_type', 'INVOICE')
            ->where('reference_type', 'OPENING_INVOICE')
            ->where('outstanding_amount', '>', 0)
            ->orderBy('due_date')
            ->get();

        return view('payments.show', compact(
            'payment',
            'remaining',
            'invoiceEntries',
            'invoiceMap',
            'openingEntries'
        ));
    }

    public function allocate(Request $request, int $id, PaymentService $paymentService)
    {
        $payment = Payment::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        $data = $request->validate([
            'target_type' => ['required', 'in:invoice,opening'],
            'target_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $allocation = [
            'amount' => $data['amount'],
        ];

        if ($data['target_type'] === 'invoice') {
            $allocation['invoice_id'] = $data['target_id'];
        } else {
            $allocation['ledger_entry_id'] = $data['target_id'];
        }

        $paymentService->allocatePayment($payment->id, [$allocation]);

        return redirect()
            ->route('payments.show', $payment->id)
            ->with('success', 'Payment allocated successfully.');
    }
}