@extends('layouts.app')

@section('title', 'Receive Stock')

@section('content')
<div class="mb-4">
    <h1 class="page-title mb-1">Receive Stock</h1>
    <p class="text-muted mb-0">
        Record incoming stock. This creates an inventory movement and FIFO cost layer.
    </p>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="panel p-4">
    @if($warehouses->isEmpty() || $products->isEmpty())
        <div class="alert alert-warning mb-0">
            You need at least one active warehouse and one active product before receiving stock.
        </div>
    @else
        <form method="POST" action="{{ route('inventory.receive.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Warehouse *</label>
                    <select name="warehouse_id" class="form-select" required>
                        <option value="">Select Warehouse</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}"
                                {{ (string) old('warehouse_id') === (string) $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->code }} — {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('warehouse_id') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Product *</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">Select Product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                {{ (string) old('product_id') === (string) $product->id ? 'selected' : '' }}>
                                {{ $product->code }} — {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Quantity *</label>
                    <input type="number"
                           name="quantity"
                           class="form-control"
                           min="0.001"
                           step="0.001"
                           value="{{ old('quantity') }}"
                           required>
                    <small class="text-muted">Up to 3 decimal places.</small>
                    @error('quantity') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Unit Cost (₹) *</label>
                    <input type="number"
                           name="unit_cost"
                           class="form-control"
                           min="0"
                           step="0.01"
                           value="{{ old('unit_cost') }}"
                           required>
                    @error('unit_cost') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes"
                              class="form-control"
                              rows="3"
                              maxlength="1000"
                              placeholder="Enter purchase reference or receipt details">{{ old('notes') }}</textarea>
                    @error('notes') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    Record Stock Receipt
                </button>

                <a href="{{ route('inventory.index') }}" class="btn btn-light border">
                    Cancel
                </a>
            </div>
        </form>
    @endif
</div>
@endsection