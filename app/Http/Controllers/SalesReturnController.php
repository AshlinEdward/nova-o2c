<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Services\ReturnService;
use Illuminate\Http\Request;

class SalesReturnController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $eligibleInvoices = Invoice::with('customer')
            ->where('company_id', $companyId)
            ->whereIn('status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])
            ->latest()
            ->get();

        $returns = SalesReturn::with([
                'customer',
                'invoice',
                'creditNote',
            ])
            ->where('company_id', $companyId)
            ->latest()
            ->get();

        return view('returns.index', compact(
            'eligibleInvoices',
            'returns'
        ));
    }

    public function create(Request $request, int $invoiceId)
    {
        $invoice = Invoice::with([
                'customer',
                'items',
            ])
            ->where('company_id', $request->user()->company_id)
            ->whereIn('status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])
            ->findOrFail($invoiceId);

        $returnedQuantities = SalesReturnItem::whereIn(
                'invoice_item_id',
                $invoice->items->pluck('id')
            )
            ->selectRaw('invoice_item_id, SUM(quantity) as returned_quantity')
            ->groupBy('invoice_item_id')
            ->pluck('returned_quantity', 'invoice_item_id');

        return view('returns.create', compact(
            'invoice',
            'returnedQuantities'
        ));
    }

    public function store(
        Request $request,
        int $invoiceId,
        ReturnService $returnService
    ) {
        $invoice = Invoice::where('company_id', $request->user()->company_id)
            ->whereIn('status', ['POSTED', 'PARTIALLY_PAID', 'PAID'])
            ->findOrFail($invoiceId);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.invoice_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $salesReturn = $returnService->processReturn(
            $invoice->id,
            $data['items'],
            $data['reason'],
            $request->user()->id
        );

        return redirect()
            ->route('returns.index')
            ->with(
                'success',
                'Return ' . $salesReturn->return_number .
                ' processed successfully. Credit note generated.'
            );
    }
}
