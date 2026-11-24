@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-1">Tax Invoice</h1>
        <p class="text-muted mb-0">{{ $invoice->invoice_number }}</p>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">
            Back
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            Print Invoice
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="panel p-4" id="invoice-print">
    <div class="row mb-4">
        <div class="col-md-7">
            <h3 class="fw-bold mb-1">{{ $invoice->company->name }}</h3>
            <div class="text-muted">GST Tax Invoice</div>
        </div>
        <div class="col-md-5 text-md-end">
            <h4 class="fw-bold text-primary">TAX INVOICE</h4>
            <div><strong>Invoice No:</strong> {{ $invoice->invoice_number }}</div>
            <div><strong>Invoice Date:</strong> {{ $invoice->invoice_date?->format('d-m-Y') }}</div>
            <div><strong>Due Date:</strong> {{ $invoice->due_date?->format('d-m-Y') }}</div>
            <div><strong>Financial Year:</strong> {{ $invoice->financial_year }}</div>
        </div>
    </div>

    <hr>

    <div class="row mb-4">
        <div class="col-md-6">
            <h6 class="text-muted text-uppercase">Bill To</h6>
            <h5 class="fw-bold">{{ $invoice->customer->name }}</h5>
            <div>Customer Code: {{ $invoice->customer->code }}</div>
            <div>GSTIN: {{ $invoice->customer->gstin ?? '-' }}</div>
            <div>State: {{ $invoice->customer->state ?? '-' }}</div>
        </div>

        <div class="col-md-6 text-md-end">
            <h6 class="text-muted text-uppercase">Shipment Details</h6>
            <div><strong>Shipment:</strong> {{ $invoice->shipment->shipment_number }}</div>
            <div><strong>Warehouse:</strong> {{ $invoice->shipment->warehouse->name ?? '-' }}</div>
            <div><strong>Sales Order:</strong> {{ $invoice->salesOrder->order_number ?? '-' }}</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Code</th>
                    <th>Product Description</th>
                    <th class="text-end">Quantity</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Taxable Amount</th>
                    <th class="text-end">GST</th>
                    <th class="text-end">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->product_code }}</td>
                        <td>
                            {{ $item->product_name }}
                            @if($item->is_free)
                                <span class="badge bg-info text-dark">Free</span>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format((float) $item->quantity, 3) }}</td>
                        <td class="text-end">₹{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="text-end">₹{{ number_format((float) $item->taxable_amount, 2) }}</td>
                        <td class="text-end">
                            ₹{{ number_format(
                                (float) $item->cgst_amount +
                                (float) $item->sgst_amount +
                                (float) $item->igst_amount,
                                2
                            ) }}
                        </td>
                        <td class="text-end">₹{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="row justify-content-end mt-4">
        <div class="col-md-5">
            <table class="table table-sm">
                <tr>
                    <th>Taxable Amount</th>
                    <td class="text-end">₹{{ number_format((float) $invoice->taxable_amount, 2) }}</td>
                </tr>
                <tr>
                    <th>CGST</th>
                    <td class="text-end">₹{{ number_format((float) $invoice->cgst_amount, 2) }}</td>
                </tr>
                <tr>
                    <th>SGST</th>
                    <td class="text-end">₹{{ number_format((float) $invoice->sgst_amount, 2) }}</td>
                </tr>
                <tr>
                    <th>IGST</th>
                    <td class="text-end">₹{{ number_format((float) $invoice->igst_amount, 2) }}</td>
                </tr>
                <tr class="table-primary">
                    <th>Grand Total</th>
                    <th class="text-end">₹{{ number_format((float) $invoice->grand_total, 2) }}</th>
                </tr>
            </table>
        </div>
    </div>

    <hr>

    <div class="row mt-4">
        <div class="col-md-6">
            <strong>Invoice Status:</strong>
            <span class="badge bg-success">{{ $invoice->status }}</span>
        </div>
        <div class="col-md-6 text-md-end">
            <strong>Authorized Signatory</strong>
            <div class="text-muted mt-4">{{ $invoice->company->name }}</div>
        </div>
    </div>
</div>

<style>
@media print {
    body {
        background: #fff !important;
    }

    .sidebar,
    .topbar,
    .no-print,
    button,
    a.btn {
        display: none !important;
    }

    .main-area {
        margin: 0 !important;
    }

    .content-area {
        padding: 0 !important;
    }

    #invoice-print {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
@endsection