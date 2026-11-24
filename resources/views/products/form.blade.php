
@php $editing = isset($product); @endphp

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="panel p-4">
    <form method="POST"
          action="{{ $editing ? route('products.update', $product->id) : route('products.store') }}">
        @csrf
        @if($editing)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Product Code *</label>
                <input name="code" class="form-control" required
                       value="{{ old('code', $product->code ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Product Name *</label>
                <input name="name" class="form-control" required
                       value="{{ old('name', $product->name ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">HSN Code *</label>
                <input name="hsn_code" class="form-control" required
                       value="{{ old('hsn_code', $product->hsn_code ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">GST Rate (%) *</label>
                <input type="number" name="gst_rate" min="0" max="100" step="0.01"
                       class="form-control" required
                       value="{{ old('gst_rate', $product->gst_rate ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Unit *</label>
                <input name="unit" class="form-control" required
                       placeholder="BOX, PCS, KG"
                       value="{{ old('unit', $product->unit ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Base Price (₹) *</label>
                <input type="number" name="base_price" min="0" step="0.01"
                       class="form-control" required
                       value="{{ old('base_price', $product->base_price ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Status *</label>
                <select name="is_active" class="form-select" required>
                    <option value="1" @selected(old('is_active', $product->is_active ?? 1) == 1)>Active</option>
                    <option value="0" @selected(old('is_active', $product->is_active ?? 1) == 0)>Inactive</option>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary">{{ $editing ? 'Update Product' : 'Save Product' }}</button>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
