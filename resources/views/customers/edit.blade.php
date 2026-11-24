@extends('layouts.app')

@section('title', 'Edit Customer - Nova O2C')

@section('content')
<div class="mb-4">
    <h1 class="page-title">Edit Customer</h1>
    <p class="text-muted">Update customer master information.</p>
</div>

@include('customers.form')
@endsection