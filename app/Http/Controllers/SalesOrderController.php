<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = SalesOrder::with(['customer', 'items'])
            ->where('company_id', $request->user()->company_id)
            ->latest()
            ->get();

        return view('orders.index', compact('orders'));
    }

    public function create(Request $request)
    {
        $companyId = $request->user()->company_id;

        $customers = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('orders.create', compact('customers', 'products'));
    }

    public function store(Request $request, SalesOrderService $service)
    {
        $items = collect($request->input('items', []))
            ->filter(fn ($item) =>
                filled($item['product_id'] ?? null) ||
                filled($item['quantity'] ?? null)
            )
            ->values()
            ->all();

        $request->merge(['items' => $items]);
        $validated = $request->validate([
            'customer_id' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
        ]);

        $customer = Customer::where('company_id', $request->user()->company_id)
            ->findOrFail($validated['customer_id']);

        foreach ($validated['items'] as $item) {
            Product::where('company_id', $request->user()->company_id)
                ->findOrFail($item['product_id']);
        }

        try {
            $order = $service->createDraft(
                $customer,
                $validated['items'],
                $request->user()->id
            );

            return redirect()->route('orders.index')
                ->with('success', "Draft order {$order->order_number} created successfully.");
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function confirm(
        Request $request,
        int $id,
        SalesOrderService $service
    ) {
        $order = SalesOrder::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        try {
            $service->confirmOrder($order->id, $request->user()->id);

            return back()->with('success', 'Order confirmation processed successfully.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function releaseCreditHold(
        Request $request,
        int $id,
        SalesOrderService $service
    ) {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $order = SalesOrder::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        try {
            $service->releaseCreditHold(
                $order->id,
                $request->user()->id,
                $validated['comment']
            );

            return back()->with('success', 'Credit hold released successfully.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function rejectCreditHold(
        Request $request,
        int $id,
        SalesOrderService $service
    ) {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $order = SalesOrder::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        try {
            $service->rejectCreditHold(
                $order->id,
                $request->user()->id,
                $validated['comment']
            );

            return back()->with('success', 'Credit-held order rejected.');
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
