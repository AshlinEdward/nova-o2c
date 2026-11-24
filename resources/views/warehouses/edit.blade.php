@extends('layouts.app')

@section('title', 'Edit Warehouse')

@section('content')
<div class="mb-4">
    <h1 class="page-title mb-1">Edit Warehouse</h1>
    <p class="text-muted mb-0">Update {{ $warehouse->code }} — {{ $warehouse->name }}.</p>
</div>

<form method="POST" action="{{ route('warehouses.update', $warehouse->id) }}">
    @csrf
    @method('PUT')
    @include('warehouses.form')
</form>
@endsection