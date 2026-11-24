@extends('layouts.app')

@section('title', 'Create Warehouse')

@section('content')
<div class="mb-4">
    <h1 class="page-title mb-1">Create Warehouse</h1>
    <p class="text-muted mb-0">Add a warehouse to your company.</p>
</div>

<form method="POST" action="{{ route('warehouses.store') }}">
    @csrf
    @include('warehouses.form')
</form>
@endsection
