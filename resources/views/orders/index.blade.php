
@extends('layouts.app')

@section('title', 'Sales Orders')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>Sales Orders</h3>
            <p class="text-muted mb-0">Manage orders for your company</p>
        </div>

        <a href="{{ route('orders.create') }}" class="btn btn-primary">
            + Create Order
        </a>
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

    @php
        $canReviewCredit = auth()->user()->roles
            ->whereIn('name', ['Admin', 'Sales Manager'])
            ->isNotEmpty();
    @endphp

    <div class="card border-0 shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Order Number</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="fw-semibold">
                                {{ $order->order_number }}
                            </td>

                            <td>
                                {{ $order->customer->customer_name ?? $order->customer->name ?? 'N/A' }}
                            </td>

                            <td>{{ $order->items->count() }}</td>

                            <td>
                                ₹{{ number_format($order->grand_total, 2) }}
                            </td>

                            <td>
                                @php
                                    $statusClass = match($order->status) {
                                        'DRAFT' => 'bg-secondary',
                                        'CONFIRMED' => 'bg-success',
                                        'CREDIT_HOLD' => 'bg-warning text-dark',
                                        'PARTIALLY_SHIPPED' => 'bg-info text-dark',
                                        'SHIPPED' => 'bg-primary',
                                        'CLOSED' => 'bg-dark',
                                        'CANCELLED', 'REJECTED' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp

                                <span class="badge {{ $statusClass }}">
                                    {{ str_replace('_', ' ', $order->status) }}
                                </span>
                            </td>

                            <td>
                                {{ $order->created_at->format('d M Y') }}
                            </td>

                            <td>
                                @if($order->status === 'DRAFT')
                                    <form method="POST"
                                          action="{{ route('orders.confirm', $order->id) }}">
                                        @csrf

                                        <button type="submit"
                                                class="btn btn-sm btn-success"
                                                onclick="return confirm('Confirm this order?')">
                                            Confirm
                                        </button>
                                    </form>

                                @elseif($order->status === 'CREDIT_HOLD' && $canReviewCredit)
                                    <button class="btn btn-sm btn-warning"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#credit-{{ $order->id }}">
                                        Review
                                    </button>

                                    <div class="collapse mt-2"
                                         id="credit-{{ $order->id }}"
                                         style="min-width: 230px;">

                                        <form method="POST"
                                              action="{{ route('orders.release-credit', $order->id) }}"
                                              class="mb-3">
                                            @csrf

                                            <label class="form-label small fw-semibold">
                                                Approval Comment
                                            </label>

                                            <textarea name="comment"
                                                      class="form-control mb-2"
                                                      rows="2"
                                                      maxlength="1000"
                                                      placeholder="Enter reason for releasing this order"
                                                      required></textarea>

                                            <button type="submit"
                                                    class="btn btn-sm btn-success">
                                                Release Order
                                            </button>
                                        </form>

                                        <hr>

                                        <form method="POST"
                                              action="{{ route('orders.reject-credit', $order->id) }}">
                                            @csrf

                                            <label class="form-label small fw-semibold">
                                                Rejection Comment
                                            </label>

                                            <textarea name="comment"
                                                      class="form-control mb-2"
                                                      rows="2"
                                                      maxlength="1000"
                                                      placeholder="Enter reason for rejecting this order"
                                                      required></textarea>

                                            <button type="submit"
                                                    class="btn btn-sm btn-danger">
                                                Reject Order
                                            </button>
                                        </form>
                                    </div>

                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"
                                class="text-center text-muted py-4">
                                No sales orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
