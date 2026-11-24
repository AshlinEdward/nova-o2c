
@extends('layouts.app')

@section('title', 'Sales Returns')

@section('content')
<div class="mb-4">
    <h1 class="page-title mb-1">Sales Returns</h1>
    <p class="text-muted mb-0">Process customer returns and manage credit notes</p>
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
    <h5 class="fw-bold mb-3">Invoices Eligible for Return</h5>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th class="text-end">Invoice Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($eligibleInvoices as $invoice)
                    <tr>
                        <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->invoice_date?->format('d M Y') }}</td>
                        <td>{{ $invoice->customer->name ?? '-' }}</td>
                        <td>
                            <span class="badge bg-info text-dark">
                                {{ str_replace('_', ' ', $invoice->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            ₹{{ number_format((float) $invoice->grand_total, 2) }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('returns.create', $invoice->id) }}"
                               class="btn btn-sm btn-outline-primary">
                                Process Return
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No eligible invoices found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Return Register</h5>
        <span class="badge bg-primary">{{ $returns->count() }} Returns</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Return Number</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Original Invoice</th>
                    <th>Credit Note</th>
                    <th>Status</th>
                    <th class="text-end">Return Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($returns as $return)
                    @php $creditNote = $return->creditNote->first(); @endphp
                    <tr>
                        <td class="fw-semibold">{{ $return->return_number }}</td>
                        <td>{{ $return->return_date?->format('d M Y') }}</td>
                        <td>{{ $return->customer->name ?? '-' }}</td>
                        <td>{{ $return->invoice->invoice_number ?? '-' }}</td>
                        <td>{{ $creditNote->credit_note_number ?? '-' }}</td>
                        <td>
                            <span class="badge bg-success">{{ $return->status }}</span>
                        </td>
                        <td class="text-end fw-semibold">
                            ₹{{ number_format((float) $return->grand_total, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No returns have been processed yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
