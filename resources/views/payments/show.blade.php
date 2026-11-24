@extends('layouts.app')

@section('title', 'Payment Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-1">Payment Details</h1>
        <p class="text-muted mb-0">{{ $payment->payment_number }}</p>
    </div>
    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Back to Payments</a>
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
    <h5 class="fw-bold mb-3">Receipt Information</h5>

    <div class="row g-3">
        <div class="col-md-3">
            <small class="text-muted">Payment Number</small>
            <div class="fw-semibold">{{ $payment->payment_number }}</div>
        </div>
        <div class="col-md-3">
            <small class="text-muted">Customer</small>
            <div class="fw-semibold">{{ $payment->customer->name }}</div>
        </div>
        <div class="col-md-3">
            <small class="text-muted">Payment Date</small>
            <div class="fw-semibold">{{ $payment->payment_date?->format('d M Y') }}</div>
        </div>
        <div class="col-md-3">
            <small class="text-muted">Payment Method</small>
            <div class="fw-semibold">{{ str_replace('_', ' ', $payment->payment_method) }}</div>
        </div>
        <div class="col-md-3">
            <small class="text-muted">Total Received</small>
            <div class="fw-bold text-primary">₹{{ number_format((float) $payment->amount, 2) }}</div>
        </div>
        <div class="col-md-3">
            <small class="text-muted">Allocated</small>
            <div class="fw-semibold">₹{{ number_format((float) $payment->amount - $remaining, 2) }}</div>
        </div>
        <div class="col-md-3">
            <small class="text-muted">Unallocated Balance</small>
            <div class="fw-bold text-success">₹{{ number_format($remaining, 2) }}</div>
        </div>
        <div class="col-md-3">
            <small class="text-muted">Transaction Reference</small>
            <div class="fw-semibold">{{ $payment->transaction_reference ?? '-' }}</div>
        </div>
    </div>

    @if($payment->notes)
        <div class="mt-3"><strong>Notes:</strong> {{ $payment->notes }}</div>
    @endif
</div>

@if($remaining > 0)
<div class="panel p-4 mb-4">
    <h5 class="fw-bold mb-3">Allocate Payment</h5>
    <p class="text-muted small">
        Allocate the available payment balance against an outstanding invoice or opening invoice.
    </p>

    <form method="POST" action="{{ route('payments.allocate', $payment->id) }}">
        @csrf

        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Invoice Type *</label>
                <select name="target_type" id="target_type" class="form-select" required>
                    <option value="invoice">Sales Invoice</option>
                    <option value="opening">Opening Invoice</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Outstanding Invoice *</label>
                <select name="target_id" id="target_id" class="form-select" required>
                    <option value="">Select Invoice</option>

                    @foreach($invoiceEntries as $entry)
                        @php $invoice = $invoiceMap[$entry->reference_id] ?? null; @endphp
                        @if($invoice)
                            <option value="{{ $invoice->id }}"
                                    data-type="invoice"
                                    data-balance="{{ $entry->outstanding_amount }}">
                                {{ $invoice->invoice_number }} —
                                Due ₹{{ number_format((float) $entry->outstanding_amount, 2) }}
                            </option>
                        @endif
                    @endforeach

                    @foreach($openingEntries as $entry)
                        <option value="{{ $entry->id }}"
                                data-type="opening"
                                data-balance="{{ $entry->outstanding_amount }}">
                            {{ $entry->description ?? ('Opening Invoice #' . $entry->id) }} —
                            Due ₹{{ number_format((float) $entry->outstanding_amount, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Allocation Amount (₹) *</label>
                <input type="number" name="amount" class="form-control"
                       min="0.01" max="{{ number_format($remaining, 2, '.', '') }}"
                       step="0.01" required>
                <small class="text-muted">
                    Available: ₹{{ number_format($remaining, 2) }}
                </small>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3">
            Allocate Payment
        </button>
    </form>
</div>
@endif

<div class="panel p-4">
    <h5 class="fw-bold mb-3">Allocation History</h5>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Target</th>
                    <th>Reference</th>
                    <th class="text-end">Allocated Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payment->allocations as $allocation)
                    <tr>
                        <td>
                            {{ $allocation->invoice_id ? 'Sales Invoice' : 'Opening Invoice' }}
                        </td>
                        <td>
                            @if($allocation->invoice)
                                {{ $allocation->invoice->invoice_number }}
                            @elseif($allocation->ledgerEntry)
                                {{ $allocation->ledgerEntry->description ?? ('Ledger Entry #' . $allocation->ledger_entry_id) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end fw-semibold">
                            ₹{{ number_format((float) $allocation->allocated_amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-3">
                            No allocations made yet. The payment remains available as an advance.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    const typeSelect = document.getElementById('target_type');
    const targetSelect = document.getElementById('target_id');

    function filterTargets() {
        const selectedType = typeSelect.value;
        let firstAvailable = '';

        Array.from(targetSelect.options).forEach(option => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const visible = option.dataset.type === selectedType;
            option.hidden = !visible;

            if (visible && !firstAvailable) {
                firstAvailable = option.value;
            }
        });

        targetSelect.value = firstAvailable;
    }

    typeSelect.addEventListener('change', filterTargets);
    filterTargets();
</script>
@endsection