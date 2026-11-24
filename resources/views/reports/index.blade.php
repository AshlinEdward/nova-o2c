
@extends('layouts.app')

@section('title', 'Reports | Nova O2C')

@section('content')

@php
    $money = fn($amount) => '₹' . number_format((float) $amount, 2);
@endphp

<div class="mb-4">
    <h1 class="page-title mb-1">Business Reports</h1>
    <p class="text-muted small mb-0">
        Inventory, sales, profitability and customer financial reports
    </p>
</div>

{{-- STOCK SUMMARY --}}
<div class="panel mb-4 overflow-hidden">
    <div class="p-4 border-bottom">
        <h5 class="fw-bold mb-1">1. Inventory Stock Summary</h5>
        <small class="text-muted">Warehouse-wise stock position</small>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Warehouse</th>
                    <th>Product Code</th>
                    <th>Product</th>
                    <th>On Hand</th>
                    <th>Reserved</th>
                    <th>Available</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stockSummary as $row)
                    <tr>
                        <td>{{ $row['warehouse'] }}</td>
                        <td>{{ $row['product_code'] }}</td>
                        <td class="fw-semibold">{{ $row['product_name'] }}</td>
                        <td>{{ number_format($row['on_hand'], 3) }}</td>
                        <td>{{ number_format($row['reserved'], 3) }}</td>
                        <td class="fw-bold text-success">
                            {{ number_format($row['available'], 3) }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-3 text-muted">No stock records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- FIFO VALUATION --}}
<div class="panel mb-4 overflow-hidden">
    <div class="p-4 border-bottom">
        <h5 class="fw-bold mb-1">2. FIFO Inventory Valuation</h5>
        <small class="text-muted">Remaining inventory cost layers and stock value</small>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Warehouse</th>
                    <th>Product</th>
                    <th>Received At</th>
                    <th>Remaining Qty</th>
                    <th>Unit Cost</th>
                    <th>Stock Value</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fifoValuation as $row)
                    <tr>
                        <td>{{ $row['warehouse'] }}</td>
                        <td class="fw-semibold">{{ $row['product_name'] }}</td>
                        <td>{{ $row['received_at'] }}</td>
                        <td>{{ number_format($row['remaining_quantity'], 3) }}</td>
                        <td>{{ $money($row['unit_cost']) }}</td>
                        <td class="fw-bold">{{ $money($row['stock_value']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-3 text-muted">No FIFO cost layers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- BACKORDERS --}}
<div class="panel mb-4 overflow-hidden">
    <div class="p-4 border-bottom">
        <h5 class="fw-bold mb-1">3. Backorder Report</h5>
        <small class="text-muted">Pending quantities against confirmed sales orders</small>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Warehouse</th>
                    <th>Backorder Qty</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backorders as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['order_number'] }}</td>
                        <td>{{ $row['customer'] }}</td>
                        <td>{{ $row['product_name'] }}</td>
                        <td>{{ $row['warehouse'] }}</td>
                        <td class="fw-bold text-warning">
                            {{ number_format($row['backorder_quantity'], 3) }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-3 text-muted">No pending backorders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- PRODUCT MARGIN --}}
<div class="panel mb-4 overflow-hidden">
    <div class="p-4 border-bottom">
        <h5 class="fw-bold mb-1">4. Product Sales & Gross Margin</h5>
        <small class="text-muted">Invoiced sales value compared with FIFO cost</small>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Product Code</th>
                    <th>Product</th>
                    <th>Invoiced Qty</th>
                    <th>Sales Value</th>
                    <th>FIFO Cost</th>
                    <th>Gross Margin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productMargins as $row)
                    <tr>
                        <td>{{ $row['product_code'] }}</td>
                        <td class="fw-semibold">{{ $row['product_name'] }}</td>
                        <td>{{ number_format($row['invoiced_quantity'], 3) }}</td>
                        <td>{{ $money($row['sales_value']) }}</td>
                        <td>{{ $money($row['fifo_cost']) }}</td>
                        <td class="fw-bold text-success">{{ $money($row['gross_margin']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-3 text-muted">No invoiced sales found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- CUSTOMER SUMMARY --}}
<div class="panel mb-4 overflow-hidden">
    <div class="p-4 border-bottom">
        <h5 class="fw-bold mb-1">5. Customer Outstanding Summary</h5>
        <small class="text-muted">Customer-wise invoicing, payments and advances</small>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Customer Code</th>
                    <th>Customer</th>
                    <th>Total Invoiced</th>
                    <th>Total Payments</th>
                    <th>Outstanding</th>
                    <th>Advance Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customerSummaries as $row)
                    <tr>
                        <td>{{ $row['customer_code'] }}</td>
                        <td class="fw-semibold">{{ $row['customer_name'] }}</td>
                        <td>{{ $money($row['total_invoiced']) }}</td>
                        <td>{{ $money($row['total_payments']) }}</td>
                        <td class="fw-bold text-danger">{{ $money($row['total_outstanding']) }}</td>
                        <td>{{ $money($row['advance_balance']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-3 text-muted">No customer records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- CUSTOMER AGEING --}}
<div class="panel mb-4 overflow-hidden">
    <div class="p-4 border-bottom">
        <h5 class="fw-bold mb-1">6. Customer Receivables Ageing</h5>
        <small class="text-muted">Outstanding invoice balances grouped by overdue days</small>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Customer Code</th>
                    <th>Customer</th>
                    <th>As of Date</th>
                    <th>Current</th>
                    <th>1–30 Days</th>
                    <th>31–60 Days</th>
                    <th>61–90 Days</th>
                    <th>Over 90 Days</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customerAgeing as $row)
                    <tr>
                        <td>{{ $row['customer_code'] }}</td>
                        <td class="fw-semibold">{{ $row['customer_name'] }}</td>
                        <td>{{ $row['as_of_date'] }}</td>
                        <td>{{ $money($row['ageing']['current']) }}</td>
                        <td>{{ $money($row['ageing']['1_30_days']) }}</td>
                        <td>{{ $money($row['ageing']['31_60_days']) }}</td>
                        <td>{{ $money($row['ageing']['61_90_days']) }}</td>
                        <td class="fw-bold text-danger">{{ $money($row['ageing']['over_90_days']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-3 text-muted">No customer ageing records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
