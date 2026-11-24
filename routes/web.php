
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WebhookTestController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SalesReturnController;
use App\Http\Controllers\ReportController;
use App\Models\Warehouse;
use App\Models\StockBalance;
use App\Models\SalesOrder;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\SalesReturn;
use App\Models\Payment;

Route::post('/webhook-test', [WebhookTestController::class, 'receive']);

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login')->middleware('guest');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->intended('/dashboard');
    }

    return back()->withErrors([
        'email' => 'Invalid email or password.',
    ])->onlyInput('email');
})->middleware('guest');

Route::middleware('auth')->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])
    ->middleware('role:Admin,Sales Manager,Accounts')
    ->name('reports.index');
    // Sales Returns: Admin and Accounts
    Route::middleware('role:Admin,Accounts')->group(function () {
        Route::get('/returns', [SalesReturnController::class, 'index'])
            ->name('returns.index');

        Route::get('/returns/invoices/{invoiceId}/create', [SalesReturnController::class, 'create'])
            ->name('returns.create');

        Route::post('/returns/invoices/{invoiceId}', [SalesReturnController::class, 'store'])
            ->name('returns.store');
    });
    // Invoice management: Admin and Accounts
    Route::middleware('role:Admin,Accounts')->group(function () {
        Route::get('/invoices', [InvoiceController::class, 'index'])
            ->name('invoices.index');

        Route::post('/invoices/shipments/{shipmentId}', [InvoiceController::class, 'store'])
            ->name('invoices.store');

        Route::get('/invoices/{id}', [InvoiceController::class, 'show'])
            ->name('invoices.show');
    });

    // Payments: Admin and Accounts
    Route::middleware('role:Admin,Accounts')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])
            ->name('payments.index');

        Route::post('/payments', [PaymentController::class, 'store'])
            ->name('payments.store');

        Route::get('/payments/{id}', [PaymentController::class, 'show'])
            ->name('payments.show');

        Route::post('/payments/{id}/allocate', [PaymentController::class, 'allocate'])
            ->name('payments.allocate');
    });
      // Warehouse viewing: Admin and Warehouse users.
    Route::middleware('role:Admin,Warehouse')->group(function () {
        Route::get('/warehouses', [WarehouseController::class, 'index'])
            ->name('warehouses.index');

        Route::get('/inventory', [InventoryController::class, 'index'])
            ->name('inventory.index');

        Route::get('/inventory/receive', [InventoryController::class, 'createReceipt'])
            ->name('inventory.receive.create');

        Route::post('/inventory/receive', [InventoryController::class, 'storeReceipt'])
            ->name('inventory.receive.store');
    });

    // Warehouse master changes: Admin only.
    Route::middleware('role:Admin')->group(function () {
        Route::get('/warehouses/create', [WarehouseController::class, 'create'])
            ->name('warehouses.create');

        Route::post('/warehouses', [WarehouseController::class, 'store'])
            ->name('warehouses.store');

        Route::get('/warehouses/{id}/edit', [WarehouseController::class, 'edit'])
            ->name('warehouses.edit');

        Route::put('/warehouses/{id}', [WarehouseController::class, 'update'])
            ->name('warehouses.update');

        Route::post('/warehouses/{id}/toggle-status', [WarehouseController::class, 'toggleStatus'])
            ->name('warehouses.toggle-status');
    });

    // Picking and Shipping: Admin and Warehouse users
    Route::middleware('role:Admin,Warehouse')->group(function () {
        Route::get('/shipments', [ShipmentController::class, 'index'])
            ->name('shipments.index');

        Route::get('/shipments/orders/{orderId}/create', [ShipmentController::class, 'create'])
            ->name('shipments.create');

        Route::post('/shipments/orders/{orderId}', [ShipmentController::class, 'store'])
            ->name('shipments.store');

        Route::get('/shipments/{id}', [ShipmentController::class, 'show'])
            ->name('shipments.show');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Customer CRUD: Admin, Sales Executive, Sales Manager
    Route::middleware('role:Admin,Sales Executive,Sales Manager')
        ->group(function () {
            Route::resource('customers', CustomerController::class)
                ->except(['show']);
        });

    // Products: all business roles can view
    Route::get('/products', [ProductController::class, 'index'])
        ->middleware('role:Admin,Sales Executive,Sales Manager,Warehouse,Accounts')
        ->name('products.index');

    // Only Admin and Sales Manager can modify products
    Route::middleware('role:Admin,Sales Manager')->group(function () {
        Route::resource('products', ProductController::class)
            ->except(['show', 'index']);
    });

    // Module access is checked server-side
    Route::get('/overview/{module}', function (
        Request $request,
        string $module
    ) {
        $companyId = $request->user()->company_id;

        $permissions = [
            'warehouses' => ['Admin', 'Warehouse'],
            'inventory' => ['Admin', 'Warehouse'],
            'orders' => ['Admin', 'Sales Executive', 'Sales Manager'],
            'invoices' => ['Admin', 'Accounts'],
            'credit' => ['Admin', 'Sales Manager'],
            'shipments' => ['Admin', 'Warehouse'],
            'returns' => ['Admin', 'Accounts'],
            'reports' => ['Admin', 'Sales Manager', 'Accounts'],
            'payments' => ['Admin', 'Accounts'],
        ];

        abort_unless(isset($permissions[$module]), 404);

        $hasPermission = $request->user()
            ->roles()
            ->whereIn('name', $permissions[$module])
            ->exists();

        abort_unless($hasPermission, 403);

        $data = match ($module) {
            'warehouses' => Warehouse::where('company_id', $companyId)->get(),

            'inventory' => StockBalance::with(['warehouse', 'product'])
                ->where('company_id', $companyId)->get(),

            'orders' => SalesOrder::where('company_id', $companyId)
                ->latest()->get(),

            'invoices' => Invoice::where('company_id', $companyId)
                ->latest()->get(),

            'credit' => Customer::where('company_id', $companyId)
                ->orderBy('code')->get(),

            'shipments' => Shipment::where('company_id', $companyId)
                ->latest()->get(),

            'returns' => SalesReturn::where('company_id', $companyId)
                ->latest()->get(),

            'reports' => StockBalance::with(['warehouse', 'product'])
                ->where('company_id', $companyId)->get(),

            'payments' => Payment::where('company_id', $companyId)
                ->latest()->get(),
        };

        return view('overview', compact('module', 'data'));

    })->name('overview');

    // Sales Order operations
    Route::middleware('role:Admin,Sales Executive,Sales Manager')->group(function () {
        Route::get('/orders', [SalesOrderController::class, 'index'])
            ->name('orders.index');

        Route::get('/orders/create', [SalesOrderController::class, 'create'])
            ->name('orders.create');

        Route::post('/orders', [SalesOrderController::class, 'store'])
            ->name('orders.store');

        Route::post('/orders/{id}/confirm', [SalesOrderController::class, 'confirm'])
            ->name('orders.confirm');
    });

    // Credit decisions: Sales Manager or Admin
    Route::middleware('role:Admin,Sales Manager')->group(function () {
        Route::post('/orders/{id}/release-credit', [SalesOrderController::class, 'releaseCreditHold'])
            ->name('orders.release-credit');

        Route::post('/orders/{id}/reject-credit', [SalesOrderController::class, 'rejectCreditHold'])
            ->name('orders.reject-credit');
    });



    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});
