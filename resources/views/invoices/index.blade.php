@extends('layouts.app')

@section('title', 'Invoices')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-1">Invoice Management</h1>
        <p class="text-muted mb-0">Generate and manage customer invoices</p>
    </div>
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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Shipments Ready for Invoicing</h5>
        <span class="badge bg-primary">{{ $shipments->count() }} Pending</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Shipment No.</th>
                    <th>Order No.</th>
                    <th>Customer</th>
                    <th>Warehouse</th>
                    <th>Shipment Date</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shipments as $shipment)
                    <tr>
                        <td class="fw-semibold">{{ $shipment->shipment_number }}</td>
                        <td>{{ $shipment->salesOrder->order_number ?? '-' }}</td>
                        <td>{{ $shipment->salesOrder->customer->name ?? '-' }}</td>
                        <td>{{ $shipment->warehouse->name ?? '-' }}</td>
                        <td>{{ $shipment->shipment_date?->format('d M Y') ?? '-' }}</td>
                        <td class="text-end">
                            <form method="POST"
                                  action="{{ route('invoices.store', $shipment->id) }}"
                                  onsubmit="return confirm('Generate invoice for this shipment?');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-primary">
                                    Generate Invoice
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No shipped shipments are waiting for invoices.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Invoice Register</h5>
        <span class="badge bg-success">{{ $invoices->count() }} Invoices</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Invoice Date</th>
                    <th>Customer</th>
                    <th>Shipment</th>
                    <th>Due Date</th>
                    <th class="text-end">Grand Total</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->invoice_date?->format('d M Y') }}</td>
                        <td>{{ $invoice->customer->name ?? '-' }}</td>
                        <td>{{ $invoice->shipment->shipment_number ?? '-' }}</td>
                        <td>{{ $invoice->due_date?->format('d M Y') }}</td>
                        <td class="text-end fw-semibold">
                            ₹{{ number_format((float) $invoice->grand_total, 2) }}
                        </td>
                        <td>
                            <span class="badge bg-success">{{ $invoice->status }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('invoices.show', $invoice->id) }}"
                               class="btn btn-sm btn-outline-primary">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No invoices generated yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection