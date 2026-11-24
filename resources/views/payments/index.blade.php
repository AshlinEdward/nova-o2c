@extends('layouts.app')

@section('title', 'Payments')

@section('content')
<div class="mb-4">
    <h1 class="page-title mb-1">Payment Management</h1>
    <p class="text-muted mb-0">Record customer receipts and manage payment allocations</p>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="panel p-4 mb-4">
    <h5 class="fw-bold mb-3">Record New Payment</h5>

    <form method="POST" action="{{ route('payments.store') }}">
        @csrf

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Customer *</label>
                <select name="customer_id" class="form-select" required>
                    <option value="">Select Customer</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                            {{ $customer->code }} - {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Payment Date *</label>
                <input type="date" name="payment_date" class="form-control"
                       value="{{ old('payment_date', now()->toDateString()) }}" required>
            </div>

            <div class="col-md-2">
                <label class="form-label">Amount (₹) *</label>
                <input type="number" name="amount" class="form-control"
                       min="0.01" step="0.01" value="{{ old('amount') }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Payment Method *</label>
                <select name="payment_method" class="form-select" required>
                    <option value="">Select Method</option>
                    @foreach(['CASH', 'BANK_TRANSFER', 'UPI', 'CHEQUE'] as $method)
                        <option value="{{ $method }}" @selected(old('payment_method') == $method)>
                            {{ str_replace('_', ' ', $method) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Transaction Reference</label>
                <input type="text" name="transaction_reference" class="form-control"
                       value="{{ old('transaction_reference') }}"
                       placeholder="UTR / cheque number / transaction ID">
            </div>

            <div class="col-md-6">
                <label class="form-label">Notes</label>
                <input type="text" name="notes" class="form-control"
                       value="{{ old('notes') }}" placeholder="Optional notes">
            </div>
        </div>

        <button class="btn btn-primary mt-3">Record Payment</button>
    </form>
</div>

<div class="panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Payment Register</h5>
        <span class="badge bg-primary">{{ $payments->count() }} Payments</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Payment No.</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Allocated</th>
                    <th class="text-end">Balance</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    @php
                        $allocated = (float) $payment->allocations->sum('allocated_amount');
                        $balance = round((float) $payment->amount - $allocated, 2);
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $payment->payment_number }}</td>
                        <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                        <td>{{ $payment->customer->name ?? '-' }}</td>
                        <td>{{ str_replace('_', ' ', $payment->payment_method) }}</td>
                        <td>{{ $payment->transaction_reference ?? '-' }}</td>
                        <td class="text-end">₹{{ number_format((float) $payment->amount, 2) }}</td>
                        <td class="text-end">₹{{ number_format($allocated, 2) }}</td>
                        <td class="text-end fw-semibold">₹{{ number_format($balance, 2) }}</td>
                        <td class="text-end">
                            <a href="{{ route('payments.show', $payment->id) }}"
                               class="btn btn-sm btn-outline-primary">View / Allocate</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            No payments recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection