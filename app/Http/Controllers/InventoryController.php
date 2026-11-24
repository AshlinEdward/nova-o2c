<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $warehouses = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $balancesQuery = StockBalance::where('company_id', $companyId)
            ->with(['warehouse', 'product']);

        if ($request->filled('warehouse_id')) {
            $balancesQuery->where('warehouse_id', $request->integer('warehouse_id'));
        }

        if ($request->filled('product_id')) {
            $balancesQuery->where('product_id', $request->integer('product_id'));
        }

        $balances = $balancesQuery
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->get();

        $movements = InventoryMovement::where('company_id', $companyId)
            ->with(['warehouse', 'product', 'user'])
            ->latest('created_at')
            ->limit(100)
            ->get();

        return view('inventory.index', compact(
            'warehouses',
            'products',
            'balances',
            'movements'
        ));
    }

    public function createReceipt(Request $request)
    {
        $companyId = $request->user()->company_id;

        $warehouses = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('inventory.receive', compact('warehouses', 'products'));
    }

    public function storeReceipt(Request $request, InventoryService $inventoryService)
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')
                    ->where('company_id', $companyId)
                    ->where('is_active', true),
            ],
            'product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where('company_id', $companyId)
                    ->where('is_active', true),
            ],
            'quantity' => [
                'required',
                'numeric',
                'gt:0',
                'regex:/^\d+(\.\d{1,3})?$/',
            ],
            'unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $movement = $inventoryService->receiveStock(
                $companyId,
                (int) $validated['warehouse_id'],
                (int) $validated['product_id'],
                (string) $validated['quantity'],
                (string) $validated['unit_cost'],
                $request->user()->id,
                $validated['notes'] ?? 'Stock received'
            );

            return redirect()->route('inventory.index')
                ->with(
                    'success',
                    "Stock received successfully. Inventory movement #{$movement->id} was created."
                );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->with('error', $exception->getMessage());
        }
    }
}