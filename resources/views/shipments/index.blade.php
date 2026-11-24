
@extends('layouts.app')

@section('title', 'Picking & Shipping')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h3>Picking & Shipping</h3>
        <p class="text-muted mb-0">Manage warehouse picking, shipments and delivery challans.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <h5 class="mb-3">Orders Ready for Shipping</h5>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Order Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="fw-semibold">{{ $order->order_number }}</td>
                            <td>{{ $order->customer->customer_name ?? $order->customer->name ?? 'N/A' }}</td>
                            <td>{{ $order->items->count() }}</td>
                            <td>₹{{ number_format($order->grand_total, 2) }}</td>
                            <td>
                                <span class="badge {{ $order->status === 'PARTIALLY_SHIPPED' ? 'bg-info text-dark' : 'bg-success' }}">
                                    {{ str_replace('_', ' ', $order->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('shipments.create', $order->id) }}"
                                   class="btn btn-sm btn-primary">
                                    Pick & Ship
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No orders currently have reserved stock available for shipping.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <h5 class="mb-3">Shipment History</h5>

    <div class="card border-0 shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Shipment Number</th>
                        <th>Order Number</th>
                        <th>Customer</th>
                        <th>Warehouse</th>
                        <th>Shipment Date</th>
                        <th>Items</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shipments as $shipment)
                        <tr>
                            <td class="fw-semibold">{{ $shipment->shipment_number }}</td>
                            <td>{{ $shipment->salesOrder->order_number ?? 'N/A' }}</td>
                            <td>
                                {{ $shipment->salesOrder->customer->customer_name
                                    ?? $shipment->salesOrder->customer->name
                                    ?? 'N/A' }}
                            </td>
                            <td>{{ $shipment->warehouse->name ?? 'N/A' }}</td>
                            <td>{{ $shipment->shipment_date?->format('d M Y') }}</td>
                            <td>{{ $shipment->items->count() }}</td>
                            <td>
                                <a href="{{ route('shipments.show', $shipment->id) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    View Challan
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No shipments created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
