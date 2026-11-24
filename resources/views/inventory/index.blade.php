@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-1">Inventory</h1>
        <p class="text-muted mb-0">Stock balances and inventory movement history.</p>
    </div>

    <a href="{{ route('inventory.receive.create') }}" class="btn btn-primary">
        + Receive Stock
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="panel p-3 mb-4">
    <h5 class="mb-3">Filter Stock</h5>

    <form method="GET" action="{{ route('inventory.index') }}">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Warehouse</label>
                <select name="warehouse_id" class="form-select">
                    <option value="">All Warehouses</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}"
                            {{ (string) request('warehouse_id') === (string) $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->code }} — {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-5">
                <label class="form-label">Product</label>
                <select name="product_id" class="form-select">
                    <option value="">All Products</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}"
                            {{ (string) request('product_id') === (string) $product->id ? 'selected' : '' }}>
                            {{ $product->code }} — {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100" type="submit">Filter</button>
                <a href="{{ route('inventory.index') }}" class="btn btn-light border">Clear</a>
            </div>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Total On-hand Quantity</div>
            <div class="stat-value">
                {{ number_format((float) $balances->sum('on_hand'), 3) }}
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Total Reserved Quantity</div>
            <div class="stat-value">
                {{ number_format((float) $balances->sum('reserved'), 3) }}
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Total Available Quantity</div>
            <div class="stat-value">
                {{ number_format((float) $balances->sum('available'), 3) }}
            </div>
        </div>
    </div>
</div>

<div class="panel p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Stock Balances</h5>
        <span class="text-muted small">{{ $balances->count() }} records</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Warehouse</th>
                    <th>Product Code</th>
                    <th>Product</th>
                    <th class="text-end">On Hand</th>
                    <th class="text-end">Reserved</th>
                    <th class="text-end">Available</th>
                </tr>
            </thead>
            <tbody>
                @forelse($balances as $balance)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $balance->warehouse->code }}</div>
                            <small class="text-muted">{{ $balance->warehouse->name }}</small>
                        </td>
                        <td>{{ $balance->product->code }}</td>
                        <td>{{ $balance->product->name }}</td>
                        <td class="text-end">{{ number_format((float) $balance->on_hand, 3) }}</td>
                        <td class="text-end">{{ number_format((float) $balance->reserved, 3) }}</td>
                        <td class="text-end fw-semibold">{{ number_format((float) $balance->available, 3) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No stock balances found for the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="panel p-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Recent Inventory Movements</h5>
        <span class="text-muted small">Latest 100 records</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Movement</th>
                    <th>Warehouse</th>
                    <th>Product</th>
                    <th class="text-end">Quantity</th>
                    <th>User</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at->format('d M Y, h:i A') }}</td>
                        <td>
                            <span class="badge
                                {{ $movement->movement_type === 'INWARD' ? 'bg-success' : 'bg-secondary' }}">
                                {{ $movement->movement_type }}
                            </span>
                        </td>
                        <td>{{ $movement->warehouse->code }}</td>
                        <td>
                            {{ $movement->product->code }}
                            <small class="text-muted d-block">{{ $movement->product->name }}</small>
                        </td>
                        <td class="text-end">{{ number_format((float) $movement->quantity, 3) }}</td>
                        <td>{{ $movement->user->name ?? 'System' }}</td>
                        <td>{{ $movement->notes ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No inventory movements recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection