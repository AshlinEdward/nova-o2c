
@extends('layouts.app')

@section('title', 'Customers - Nova O2C')

@section('content')

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-1">Customer Master</h1>
        <p class="text-muted mb-0">Manage and view customer master records</p>
    </div>

    <a href="{{ route('customers.create') }}" class="btn btn-primary mt-3 mt-md-0">
        <i class="bi bi-plus-lg me-1"></i> Add Customer
    </a>
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
            <div class="stat-label">TOTAL CUSTOMERS</div>
            <div class="stat-value">{{ $customers->count() }}</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">ACTIVE CUSTOMERS</div>
            <div class="stat-value text-success">
                {{ $customers->where('is_active', true)->count() }}
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">INACTIVE CUSTOMERS</div>
            <div class="stat-value text-secondary">
                {{ $customers->where('is_active', false)->count() }}
            </div>
        </div>
    </div>
</div>

<div class="panel overflow-hidden">
    <div class="p-4 border-bottom">
        <h5 class="fw-bold mb-1">Customer Directory</h5>
        <small class="text-muted">Registered customers for your company</small>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Customer Code</th>
                    <th>Customer Name</th>
                    <th>State</th>
                    <th>Credit Limit</th>
                    <th>Payment Terms</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td class="fw-semibold text-primary">{{ $customer->code }}</td>
                        <td class="fw-semibold">{{ $customer->name }}</td>
                        <td>{{ $customer->state }}</td>
                        <td>₹{{ number_format($customer->credit_limit, 2) }}</td>
                        <td>{{ $customer->payment_terms_days }} days</td>
                        <td>
                            @if ($customer->is_active)
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('customers.edit', $customer->id) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Edit
                                </a>

                                <form action="{{ route('customers.destroy', $customer->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this customer?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            No customers found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
