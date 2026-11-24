@extends('layouts.app')

@section('title', 'Add Customer - Nova O2C')

@section('content')
<div class="mb-4">
    <h1 class="page-title">Add Customer</h1>
    <p class="text-muted">Create a new customer master record.</p>
</div>

@include('customers.form')
@endsection