
@extends('layouts.app')

@section('title', 'Dashboard | Nova O2C')

@section('content')

<div class="mb-4">
    <h1 class="page-title mb-1">Dashboard</h1>
    <p class="text-muted small mb-0">Business overview and operational summary</p>
</div>

<div class="panel p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h5 class="fw-bold mb-1">Welcome, {{ $user->name }}</h5>
            <p class="text-muted small mb-1">
                {{ $company->name }} ({{ $company->code }})
            </p>
            <span class="badge bg-primary-subtle text-primary">
                {{ $roles->pluck('name')->implode(' / ') }}
            </span>
        </div>
        <div class="text-muted small">
            <strong>ERP Workspace</strong><br>
            {{ now()->format('d M Y') }}
        </div>
    </div>
</div>

<h6 class="fw-bold mb-3">Business Overview</h6>

<div class="row g-3 mb-4">
    @php
        $stats = [
            ['Customers', $customerCount, 'C'],
            ['Products', $productCount, 'P'],
            ['Warehouses', $warehouseCount, 'W'],
            ['Sales Orders', $orderCount, 'O'],
            ['Invoices', $invoiceCount, 'I'],
        ];
    @endphp

    @foreach($stats as [$label, $value, $icon])
        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <span class="stat-label">{{ $label }}</span>
                    <span class="module-icon">{{ $icon }}</span>
                </div>
                <div class="stat-value">{{ $value }}</div>
                <small class="text-muted">Current company records</small>
            </div>
        </div>
    @endforeach
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold mb-0">ERP Modules</h6>
    <small class="text-muted">Access business functions</small>
</div>

@php
    $modules = [
        ['Customers', 'Manage customer profiles and credit details', 'customers.index', null, 'C'],
        ['Products', 'Product masters, HSN and GST configuration', 'products.index', null, 'P'],
        ['Warehouses', 'Manage company warehouse locations', 'overview', 'warehouses', 'W'],
        ['Inventory', 'Stock balances, reservations and FIFO valuation', 'overview', 'inventory', 'I'],
        ['Sales Orders', 'Order creation, confirmation and fulfilment', 'overview', 'orders', 'O'],
        ['Credit Management', 'Credit exposure and credit hold monitoring', 'overview', 'credit', 'C'],
        ['Shipments', 'Picking, dispatch and shipment tracking', 'overview', 'shipments', 'S'],
        ['Invoices', 'Customer invoices and GST details', 'overview', 'invoices', 'I'],
        ['Payments', 'Payment recording and allocation', 'overview', 'payments', 'P'],
        ['Sales Returns', 'Returns processing and credit notes', 'overview', 'returns', 'R'],
        ['Reports', 'Stock, margin and ageing reports', 'overview', 'reports', 'R'],
    ];
@endphp

<div class="row g-3">
    @foreach($modules as [$name, $description, $routeName, $moduleName, $icon])
        <div class="col-xl-4 col-md-6">
            <div class="module-card">
                <div class="module-icon">{{ $icon }}</div>
                <h6 class="fw-bold mb-2">{{ $name }}</h6>
                <p class="text-muted small mb-3">{{ $description }}</p>

                <a href="{{ $routeName === 'overview'
                        ? route('overview', $moduleName)
                        : route($routeName) }}"
                   class="btn btn-sm btn-outline-primary">
                    Open Module →
                </a>
            </div>
        </div>
    @endforeach
</div>

@endsection
