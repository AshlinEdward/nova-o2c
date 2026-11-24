
@extends('layouts.app')

@section('title', 'Delivery Challan')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h3>Shipment Details</h3>
            <p class="text-muted mb-0">{{ $shipment->shipment_number }}</p>
        </div>

        <div class="d-flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-primary">
                Print Delivery Challan
            </button>

            <a href="{{ route('shipments.index') }}"
               class="btn btn-outline-secondary">
                Back
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm" id="challan">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <h2>DELIVERY CHALLAN</h2>
                <p class="text-muted mb-0">Goods Shipment Document</p>
            </div>

            <hr>

            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="fw-bold">Shipment Details</h6>
                    <p class="mb-1">
                        <strong>Challan Number:</strong>
                        {{ $shipment->shipment_number }}
                    </p>
                    <p class="mb-1">
                        <strong>Date:</strong>
                        {{ $shipment->shipment_date?->format('d M Y') }}
                    </p>
                    <p class="mb-1">
                        <strong>Warehouse:</strong>
                        {{ $shipment->warehouse->name ?? 'N/A' }}
                    </p>
                    <p class="mb-1">
                        <strong>Shipped By:</strong>
                        {{ $shipment->shippedBy->name ?? 'N/A' }}
                    </p>
                </div>

                <div class="col-md-6">
                    <h6 class="fw-bold">Customer Details</h6>
                    <p class="mb-1">
                        <strong>Customer:</strong>
                        {{ $shipment->salesOrder->customer->customer_name
                            ?? $shipment->salesOrder->customer->name
                            ?? 'N/A' }}
                    </p>
                    <p class="mb-1">
                        <strong>Customer Code:</strong>
                        {{ $shipment->salesOrder->customer->code ?? 'N/A' }}
                    </p>
                    <p class="mb-1">
                        <strong>GSTIN:</strong>
                        {{ $shipment->salesOrder->customer->gstin ?? 'N/A' }}
                    </p>
                    <p class="mb-1">
                        <strong>Sales Order:</strong>
                        {{ $shipment->salesOrder->order_number }}
                    </p>
                </div>
            </div>

            <h6 class="fw-bold mb-3">Shipped Products</h6>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product Code</th>
                            <th>Product Name</th>
                            <th>HSN</th>
                            <th class="text-end">Quantity Shipped</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shipment->items as $index => $shipmentItem)
                            @php
                                $orderItem = $shipmentItem->salesOrderItem;
                                $product = $orderItem?->product;
                            @endphp

                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $orderItem->product_code ?? 'N/A' }}</td>
                                <td>{{ $orderItem->product_name ?? $product->name ?? 'N/A' }}</td>
                                <td>{{ $orderItem->hsn_code ?? $product->hsn_code ?? 'N/A' }}</td>
                                <td class="text-end">
                                    {{ number_format((float) $shipmentItem->quantity, 3) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5 row">
                <div class="col-md-6">
                    <p>Received By: __________________________</p>
                    <p>Date: _________________________________</p>
                </div>

                <div class="col-md-6 text-md-end">
                    <p>Authorized Signature</p>
                    <br><br>
                    <p>____________________________</p>
                </div>
            </div>

            <p class="text-center text-muted small mt-5">
                This is a system-generated delivery challan.
            </p>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print,
    .sidebar,
    nav,
    header {
        display: none !important;
    }

    body {
        background: white !important;
    }

    #challan {
        box-shadow: none !important;
        border: none !important;
    }

    .container-fluid {
        width: 100% !important;
        padding: 0 !important;
    }
}
</style>
@endsection
