
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h3 class="mb-4">Create Sales Order</h3>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('orders.store') }}">
                @csrf

                <div class="mb-4">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">Select customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}"
                                {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->code }} - {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <h5>Order Items</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th style="width:65%">Product</th>
                                <th style="width:25%">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 5; $i++)
                                <tr>
                                    <td>
                                        <select name="items[{{ $i }}][product_id]" class="form-select">
                                            <option value="">Select product</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}"
                                                    {{ old("items.$i.product_id") == $product->id ? 'selected' : '' }}>
                                                    {{ $product->code }} - {{ $product->name }}
                                                    (₹{{ number_format($product->base_price, 2) }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number"
                                               name="items[{{ $i }}][quantity]"
                                               class="form-control"
                                               min="0.001"
                                               step="0.001"
                                               value="{{ old("items.$i.quantity") }}"
                                               placeholder="Quantity">
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Draft Order</button>
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
