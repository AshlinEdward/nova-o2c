@extends('layouts.app')

@section('title', 'Create Shipment')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>Pick & Ship</h3>
            <p class="text-muted mb-0">
                Order: {{ $order->order_number }}
            </p>
        </div>

        <a href="{{ route('shipments.index') }}" class="btn btn-outline-secondary">
            Back
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Customer</strong>
                    <p>{{ $order->customer->customer_name ?? $order->customer->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-4">
                    <strong>Order Date</strong>
                    <p>{{ $order->order_date?->format('d M Y') }}</p>
                </div>
                <div class="col-md-4">
                    <strong>Order Status</strong>
                    <p>{{ str_replace('_', ' ', $order->status) }}</p>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('shipments.store', $order->id) }}">
        @csrf

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="mb-4">
                    <label for="warehouse_id" class="form-label fw-semibold">
                        Select Picking Warehouse *
                    </label>

                    <select name="warehouse_id" id="warehouse_id"
                            class="form-select" required>
                        <option value="">Select warehouse</option>

                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}"
                                {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->code }} - {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <h5 class="mb-3">Order Items</h5>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Ordered Qty</th>
                                <th>Reserved Remaining in Warehouse</th>
                                <th>Ship Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $index => $item)
                                @php
                                    $remainingByWarehouse = $item->allocations
                                        ->mapWithKeys(function ($allocation) {
                                            return [
                                                $allocation->warehouse_id => max(
                                                    0,
                                                    (float) $allocation->reserved_quantity -
                                                    (float) $allocation->shipped_quantity
                                                )
                                            ];
                                        });
                                @endphp

                                <tr>
                                    <td>
                                        <strong>{{ $item->product_name }}</strong>
                                        <div class="small text-muted">
                                            {{ $item->product_code }}
                                        </div>
                                    </td>

                                    <td>{{ number_format((float) $item->quantity, 3) }}</td>

                                    <td>
                                        <span class="remaining-label text-muted"
                                              data-remaining="{{ $remainingByWarehouse->toJson() }}">
                                            Select a warehouse
                                        </span>
                                    </td>

                                    <td style="min-width: 150px;">
                                        <input type="hidden"
                                               name="items[{{ $index }}][sales_order_item_id]"
                                               value="{{ $item->id }}">

                                        <input type="number"
                                               name="items[{{ $index }}][quantity]"
                                               class="form-control ship-quantity"
                                               min="0"
                                               step="0.001"
                                               max="0"
                                               value="{{ old('items.' . $index . '.quantity') }}"
                                               placeholder="0.000"
                                               disabled>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="small text-muted mb-0">
                    Leave the quantity blank or zero for products you do not want to ship in this shipment.
                    You can create another shipment later for the remaining reserved quantity.
                </p>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"
                onclick="return confirm('Create this shipment and deduct the shipped stock?')">
            Create Shipment
        </button>

        <a href="{{ route('shipments.index') }}"
           class="btn btn-outline-secondary">
            Cancel
        </a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const warehouseSelect = document.getElementById('warehouse_id');

    function updateAvailableQuantities() {
        const warehouseId = warehouseSelect.value;

        document.querySelectorAll('.ship-quantity').forEach(function (input) {
            const row = input.closest('tr');
            const label = row.querySelector('.remaining-label');

            const remainingByWarehouse = JSON.parse(
                label.dataset.remaining || '{}'
            );

            const remaining = Number(
                remainingByWarehouse[warehouseId] || 0
            );

            label.textContent = remaining.toFixed(3);

            input.max = remaining;
            input.disabled = !warehouseId || remaining <= 0;

            if (remaining <= 0) {
                input.value = '';
            }
        });
    }

    warehouseSelect.addEventListener('change', updateAvailableQuantities);

    updateAvailableQuantities();
});
</script>
@endsection
