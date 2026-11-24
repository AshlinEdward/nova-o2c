
@extends('layouts.app')

@section('title', ucfirst($module) . ' | Nova O2C')

@section('content')

@php
    $titles = [
        'orders' => 'Sales Orders',
        'returns' => 'Sales Returns',
        'credit' => 'Credit Management',
        'inventory' => 'Inventory Overview',
        'reports' => 'Inventory Reports',
    ];

    $pageTitle = $titles[$module] ?? ucfirst($module);
    $recordCount = $data->count();
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="page-title mb-1">{{ $pageTitle }}</h1>
        <p class="text-muted small mb-0">Company operational records and management</p>
    </div>

    <span class="badge bg-primary-subtle text-primary px-3 py-2">
        {{ $recordCount }} Records
    </span>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">TOTAL RECORDS</div>
            <div class="stat-value">{{ $recordCount }}</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">MODULE</div>
            <div class="stat-value fs-4">{{ $pageTitle }}</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">COMPANY</div>
            <div class="stat-value fs-4">
                {{ auth()->user()->company->code ?? 'NOVA' }}
            </div>
        </div>
    </div>
</div>

<div class="panel overflow-hidden">
    <div class="p-4 border-bottom">
        <h5 class="fw-bold mb-1">{{ $pageTitle }} Directory</h5>
        <small class="text-muted">View registered {{ strtolower($pageTitle) }} records</small>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    @if ($module === 'products')
                        <th>Code</th>
                        <th>Product</th>
                        <th>HSN</th>
                        <th>GST</th>
                        <th>Unit</th>
                        <th>Base Price</th>
                    @elseif ($module === 'warehouses')
                        <th>Code</th>
                        <th>Name</th>
                        <th>Location</th>
                    @elseif (in_array($module, ['inventory', 'reports']))
                        <th>Warehouse</th>
                        <th>Product</th>
                        <th>On Hand</th>
                        <th>Reserved</th>
                        <th>Available</th>
                    @elseif ($module === 'orders')
                        <th>Order Number</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Total</th>
                    @elseif ($module === 'invoices')
                        <th>Invoice Number</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Total</th>
                    @elseif ($module === 'credit')
                        <th>Customer Code</th>
                        <th>Customer</th>
                        <th>Credit Limit</th>
                        <th>State</th>
                    @elseif ($module === 'shipments')
                        <th>Shipment Number</th>
                        <th>Order ID</th>
                        <th>Warehouse ID</th>
                        <th>Status</th>
                    @elseif ($module === 'returns')
                        <th>Return Number</th>
                        <th>Customer ID</th>
                        <th>Invoice ID</th>
                        <th>Total</th>
                    @elseif ($module === 'payments')
                        <th>Payment ID</th>
                        <th>Customer ID</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                    @endif
                </tr>
            </thead>

            <tbody>
                @forelse ($data as $item)
                    <tr>
                        @if ($module === 'products')
                            <td class="fw-semibold text-primary">{{ $item->code }}</td>
                            <td class="fw-semibold">{{ $item->name }}</td>
                            <td>{{ $item->hsn_code }}</td>
                            <td>{{ $item->gst_rate }}%</td>
                            <td>{{ $item->unit }}</td>
                            <td class="fw-semibold">₹{{ number_format($item->base_price, 2) }}</td>

                        @elseif ($module === 'warehouses')
                            <td class="fw-semibold text-primary">{{ $item->code }}</td>
                            <td class="fw-semibold">{{ $item->name }}</td>
                            <td>{{ $item->location }}</td>

                        @elseif (in_array($module, ['inventory', 'reports']))
                            <td>{{ $item->warehouse->name ?? '-' }}</td>
                            <td class="fw-semibold">{{ $item->product->name ?? '-' }}</td>
                            <td>{{ $item->on_hand }}</td>
                            <td>{{ $item->reserved }}</td>
                            <td class="fw-bold text-success">{{ $item->available }}</td>

                        @elseif ($module === 'orders')
                            <td class="fw-semibold text-primary">{{ $item->order_number }}</td>
                            <td>{{ $item->customer->name ?? '-' }}</td>
                            <td>
                                <span class="badge bg-info-subtle text-info">
                                    {{ str_replace('_', ' ', $item->status) }}
                                </span>
                            </td>
                            <td class="fw-semibold">₹{{ number_format($item->grand_total, 2) }}</td>

                        @elseif ($module === 'invoices')
                            <td class="fw-semibold text-primary">{{ $item->invoice_number }}</td>
                            <td>{{ $item->customer->name ?? '-' }}</td>
                            <td>
                                <span class="badge bg-success-subtle text-success">
                                    {{ str_replace('_', ' ', $item->status) }}
                                </span>
                            </td>
                            <td class="fw-semibold">₹{{ number_format($item->grand_total, 2) }}</td>

                        @elseif ($module === 'credit')
                            <td class="fw-semibold text-primary">{{ $item->code }}</td>
                            <td class="fw-semibold">{{ $item->name }}</td>
                            <td>₹{{ number_format($item->credit_limit, 2) }}</td>
                            <td>{{ $item->state }}</td>

                        @elseif ($module === 'shipments')
                            <td class="fw-semibold">{{ $item->shipment_number ?? $item->id }}</td>
                            <td>{{ $item->sales_order_id ?? '-' }}</td>
                            <td>{{ $item->warehouse_id ?? '-' }}</td>
                            <td>{{ $item->status ?? '-' }}</td>

                        @elseif ($module === 'returns')
                            <td class="fw-semibold">{{ $item->return_number }}</td>
                            <td>{{ $item->customer_id }}</td>
                            <td>{{ $item->invoice_id }}</td>
                            <td>₹{{ number_format($item->grand_total, 2) }}</td>

                        @elseif ($module === 'payments')
                            <td class="fw-semibold">{{ $item->id }}</td>
                            <td>{{ $item->customer_id }}</td>
                            <td class="fw-semibold">₹{{ number_format($item->amount, 2) }}</td>
                            <td>{{ $item->payment_method ?? $item->method ?? '-' }}</td>
                            <td>{{ $item->payment_date ?? $item->created_at }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <div class="fw-semibold mb-1">No records found</div>
                            <small>There are currently no records in this module.</small>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="text-muted small mt-3">
    Showing {{ $recordCount }} record(s)
</div>

@endsection
