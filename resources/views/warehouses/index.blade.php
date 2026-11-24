@extends('layouts.app')

@section('title', 'Warehouses')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title mb-1">Warehouses</h1>
        <p class="text-muted mb-0">Manage warehouses for your company.</p>
    </div>

    @if(auth()->user()->roles()->where('name', 'Admin')->exists())
        <a href="{{ route('warehouses.create') }}" class="btn btn-primary">
            + Add Warehouse
        </a>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="panel p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Warehouse Name</th>
                    <th>Location</th>
                    <th>Status</th>
                    @if(auth()->user()->roles()->where('name', 'Admin')->exists())
                        <th class="text-end">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($warehouses as $warehouse)
                    <tr>
                        <td class="fw-semibold">{{ $warehouse->code }}</td>
                        <td>{{ $warehouse->name }}</td>
                        <td>{{ $warehouse->location ?: '—' }}</td>
                        <td>
                            @if($warehouse->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>

                        @if(auth()->user()->roles()->where('name', 'Admin')->exists())
                            <td class="text-end">
                                <a href="{{ route('warehouses.edit', $warehouse->id) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    Edit
                                </a>

                                <form method="POST"
                                      action="{{ route('warehouses.toggle-status', $warehouse->id) }}"
                                      class="d-inline">
                                    @csrf
                                    <button type="submit"
                                            class="btn btn-sm {{ $warehouse->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                            onclick="return confirm('Change this warehouse status?')">
                                        {{ $warehouse->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No warehouses found for this company.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection