
@extends('layouts.app')

@section('title', 'Products - Nova O2C')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-1">Product Master</h1>
        <p class="text-muted mb-0">Manage product catalogue and pricing.</p>
    </div>
    <a href="{{ route('products.create') }}" class="btn btn-primary">+ Add Product</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">TOTAL PRODUCTS</div>
            <div class="stat-value">{{ $products->count() }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">ACTIVE PRODUCTS</div>
            <div class="stat-value text-success">{{ $products->where('is_active', true)->count() }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">INACTIVE PRODUCTS</div>
            <div class="stat-value text-secondary">{{ $products->where('is_active', false)->count() }}</div>
        </div>
    </div>
</div>

<div class="panel overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Product Name</th>
                    <th>HSN</th>
                    <th>GST</th>
                    <th>Unit</th>
                    <th>Base Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($products as $product)
                <tr>
                    <td class="fw-semibold text-primary">{{ $product->code }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->hsn_code }}</td>
                    <td>{{ number_format($product->gst_rate, 2) }}%</td>
                    <td>{{ $product->unit }}</td>
                    <td>₹{{ number_format($product->base_price, 2) }}</td>
                    <td>
                        <span class="badge {{ $product->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="{{ route('products.edit', $product->id) }}"
                               class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('products.destroy', $product->id) }}"
                                  onsubmit="return confirm('Delete this product?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center py-5 text-muted">No products found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
