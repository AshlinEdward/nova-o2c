
@php
    $editing = isset($customer);
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Please correct the following:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="panel p-4">
    <form method="POST"
          action="{{ $editing ? route('customers.update', $customer->id) : route('customers.store') }}">
        @csrf

        @if($editing)
            @method('PUT')
        @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Customer Code *</label>
                <input type="text" name="code" class="form-control"
                       value="{{ old('code', $customer->code ?? '') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Customer Name *</label>
                <input type="text" name="name" class="form-control"
                       value="{{ old('name', $customer->name ?? '') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">GSTIN</label>
                <input type="text" name="gstin" maxlength="15" class="form-control"
                       value="{{ old('gstin', $customer->gstin ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">State *</label>
                <input type="text" name="state" class="form-control"
                       value="{{ old('state', $customer->state ?? '') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Credit Limit (₹) *</label>
                <input type="number" name="credit_limit" min="0" step="0.01"
                       class="form-control"
                       value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Payment Terms (Days) *</label>
                <input type="number" name="payment_terms_days" min="0"
                       class="form-control"
                       value="{{ old('payment_terms_days', $customer->payment_terms_days ?? 30) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Preferred Warehouse</label>
                <select name="preferred_warehouse_id" class="form-select">
                    <option value="">No preference</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}"
                            @selected(old('preferred_warehouse_id', $customer->preferred_warehouse_id ?? '') == $warehouse->id)>
                            {{ $warehouse->code }} - {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Status *</label>
                <select name="is_active" class="form-select" required>
                    <option value="1" @selected(old('is_active', $customer->is_active ?? 1) == 1)>Active</option>
                    <option value="0" @selected(old('is_active', $customer->is_active ?? 1) == 0)>Inactive</option>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
                {{ $editing ? 'Update Customer' : 'Save Customer' }}
            </button>

            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>
