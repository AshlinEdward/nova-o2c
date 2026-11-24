
@extends('layouts.app')

@section('title', 'Process Sales Return')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-1">Process Sales Return</h1>
        <p class="text-muted mb-0">
            Invoice: {{ $invoice->invoice_number }}
        </p>
    </div>

    <a href="{{ route('returns.index') }}" class="btn btn-outline-secondary">
        Back to Returns
    </a>
</div>

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
    <h5 class="fw-bold mb-3">Original Invoice Information</h5>

    <div class="row g-3">
        <div class="col-md-3">
            <small class="text-muted">Invoice Number</small>
            <div class="fw-semibold">{{ $invoice->invoice_number }}</div>
        </div>
        <div class="col-md-3">
            <small class="text-muted">Customer</small>
            <div class="fw-semibold">{{ $invoice->customer->name }}</div>
        </div>
        <div class="col-md-3">
            <small class="text-muted">Invoice Date</small>
            <div class="fw-semibold">{{ $invoice->invoice_date?->format('d M Y') }}</div>
        </div>
        <div class="col-md-3">
            <small class="text-muted">Invoice Total</small>
            <div class="fw-semibold">
                ₹{{ number_format((float) $invoice->grand_total, 2) }}
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('returns.store', $invoice->id) }}" id="returnForm">
    @csrf

    <div class="panel p-4 mb-4">
        <h5 class="fw-bold mb-3">Select Items and Return Quantities</h5>

        <p class="text-muted small">
            Enter the quantity being returned. Leave items at zero if they are not being returned.
        </p>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-end">Invoiced Qty</th>
                        <th class="text-end">Previously Returned</th>
                        <th class="text-end">Returnable Qty</th>
                        <th style="width: 180px;">Return Qty *</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $index => $item)
                        @php
                            $previous = (float) ($returnedQuantities[$item->id] ?? 0);
                            $returnable = max(0, (float) $item->quantity - $previous);
                        @endphp

                        <tr class="return-item-row">
                            <td>
                                <div class="fw-semibold">{{ $item->product_name }}</div>
                                <small class="text-muted">{{ $item->product_code }}</small>
                            </td>
                            <td class="text-end">{{ number_format((float) $item->quantity, 3) }}</td>
                            <td class="text-end">{{ number_format($previous, 3) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($returnable, 3) }}</td>
                            <td>
                                @if($returnable > 0)
                                    <input type="hidden"
                                           name="items[{{ $index }}][invoice_item_id]"
                                           value="{{ $item->id }}">

                                    <input type="number"
                                           name="items[{{ $index }}][quantity]"
                                           class="form-control return-quantity"
                                           min="0"
                                           max="{{ number_format($returnable, 3, '.', '') }}"
                                           step="0.001"
                                           value="0">
                                @else
                                    <span class="text-muted">Fully returned</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel p-4 mb-4">
        <h5 class="fw-bold mb-3">Return Reason</h5>

        <textarea name="reason"
                  class="form-control"
                  rows="3"
                  maxlength="1000"
                  placeholder="Enter reason for return..."
                  required>{{ old('reason') }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary">
        Process Return and Generate Credit Note
    </button>
</form>

<script>
    document.getElementById('returnForm').addEventListener('submit', function (event) {
        const rows = document.querySelectorAll('.return-item-row');
        let hasQuantity = false;

        rows.forEach(row => {
            const quantityInput = row.querySelector('.return-quantity');

            if (!quantityInput) {
                return;
            }

            const quantity = parseFloat(quantityInput.value || 0);

            if (quantity > 0) {
                hasQuantity = true;
            } else {
                row.querySelectorAll('input[name^="items["]').forEach(input => {
                    input.disabled = true;
                });
            }
        });

        if (!hasQuantity) {
            event.preventDefault();
            alert('Please enter a return quantity for at least one item.');
        }
    });
</script>
@endsection
