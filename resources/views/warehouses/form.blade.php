
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="mb-3">
            <label for="code" class="form-label">Warehouse Code <span class="text-danger">*</span></label>
            <input type="text" name="code" id="code"
                   class="form-control @error('code') is-invalid @enderror"
                   value="{{ old('code', $warehouse->code ?? '') }}"
                   maxlength="20" required>
            @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="name" class="form-label">Warehouse Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $warehouse->name ?? '') }}"
                   maxlength="150" required>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="location" class="form-label">Location</label>
            <input type="text" name="location" id="location"
                   class="form-control @error('location') is-invalid @enderror"
                   value="{{ old('location', $warehouse->location ?? '') }}"
                   maxlength="255">
            @error('location')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        @if(isset($warehouse))
            <div class="form-check mb-3">
                <input type="checkbox" name="is_active" id="is_active"
                       class="form-check-input" value="1"
                       {{ old('is_active', $warehouse->is_active) ? 'checked' : '' }}>
                <label for="is_active" class="form-check-label">Active</label>
            </div>
        @endif
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary">{{ $buttonText ?? 'Save Warehouse' }}</button>
    <a href="{{ route('warehouses.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
