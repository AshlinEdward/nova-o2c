<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\SalesOrder;
use App\Models\Invoice;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id;

        return view('dashboard', [
            'user' => $user,
            'company' => $user->company,
            'roles' => $user->roles,

            'customerCount' => Customer::where('company_id', $companyId)->count(),
            'productCount' => Product::where('company_id', $companyId)->count(),
            'warehouseCount' => Warehouse::where('company_id', $companyId)->count(),
            'orderCount' => SalesOrder::where('company_id', $companyId)->count(),
            'invoiceCount' => Invoice::where('company_id', $companyId)->count(),
        ]);
    }
}
