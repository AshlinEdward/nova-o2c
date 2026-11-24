<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\ShipmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $orders = SalesOrder::where('company_id', $companyId)
            ->whereIn('status', ['CONFIRMED', 'PARTIALLY_SHIPPED'])
            ->whereHas('items.allocations', function ($query) {
                $query->whereColumn(
                    'reserved_quantity',
                    '>',
                    'shipped_quantity'
                );
            })
            ->with(['customer', 'items.product', 'items.allocations.warehouse'])
            ->latest()
            ->get();

        $shipments = Shipment::where('company_id', $companyId)
            ->with(['salesOrder.customer', 'warehouse', 'shippedBy', 'items'])
            ->latest()
            ->get();

        return view('shipments.index', compact('orders', 'shipments'));
    }

    public function create(Request $request, int $orderId)
    {
        $order = SalesOrder::where('company_id', $request->user()->company_id)
            ->with(['customer', 'items.product', 'items.allocations.warehouse'])
            ->findOrFail($orderId);

        if (!in_array($order->status, ['CONFIRMED', 'PARTIALLY_SHIPPED'])) {
            return redirect()->route('shipments.index')
                ->with('error', 'This order is not available for shipping.');
        }

        $warehouseIds = $order->items
            ->flatMap(function ($item) {
                return $item->allocations
                    ->filter(fn ($allocation) =>
                        (float) $allocation->reserved_quantity >
                        (float) $allocation->shipped_quantity
                    )
                    ->pluck('warehouse_id');
            })
            ->unique()
            ->values();

        $warehouses = Warehouse::where('company_id', $request->user()->company_id)
            ->whereIn('id', $warehouseIds)
            ->orderBy('code')
            ->get();

        if ($warehouses->isEmpty()) {
            return redirect()->route('shipments.index')
                ->with('error', 'No reserved stock is available for this order.');
        }

        return view('shipments.create', compact('order', 'warehouses'));
    }

    public function store(
        Request $request,
        int $orderId,
        ShipmentService $shipmentService
    ) {
        $order = SalesOrder::where('company_id', $request->user()->company_id)
            ->with('items')
            ->findOrFail($orderId);

        $items = collect($request->input('items', []))
            ->filter(fn ($item) =>
                filled($item['quantity'] ?? null) &&
                is_numeric($item['quantity']) &&
                (float) $item['quantity'] > 0
            )
            ->values()
            ->all();

        $request->merge(['items' => $items]);

        $validated = $request->validate([
            'warehouse_id' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_order_item_id' => ['required', 'integer'],
            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
                'regex:/^\d+(\.\d{1,3})?$/',
            ],
        ]);

        $warehouse = Warehouse::where('company_id', $request->user()->company_id)
            ->find($validated['warehouse_id']);

        if (!$warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'Please select a valid warehouse.',
            ]);
        }

        $orderItemIds = $order->items->pluck('id')->all();

        foreach ($validated['items'] as $item) {
            if (!in_array((int) $item['sales_order_item_id'], $orderItemIds, true)) {
                throw ValidationException::withMessages([
                    'items' => 'An invalid sales order item was submitted.',
                ]);
            }
        }

        $shipment = $shipmentService->createShipment(
            $order->id,
            (int) $validated['warehouse_id'],
            $validated['items'],
            $request->user()->id
        );

        return redirect()->route('shipments.show', $shipment->id)
            ->with('success', 'Shipment created successfully.');
    }

    public function show(Request $request, int $id)
    {
        $shipment = Shipment::where('company_id', $request->user()->company_id)
            ->with([
                'salesOrder.customer',
                'salesOrder.items',
                'warehouse',
                'shippedBy',
                'items.salesOrderItem.product',
            ])
            ->findOrFail($id);

        return view('shipments.show', compact('shipment'));
    }
}
