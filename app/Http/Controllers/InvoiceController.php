<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Shipment;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $shipments = Shipment::with([
                'salesOrder.customer',
                'warehouse',
                'invoice',
            ])
            ->where('company_id', $companyId)
            ->where('status', 'SHIPPED')
            ->whereDoesntHave('invoice')
            ->latest()
            ->get();

        $invoices = Invoice::with([
                'customer',
                'salesOrder',
                'shipment.warehouse',
            ])
            ->where('company_id', $companyId)
            ->latest()
            ->get();

        return view('invoices.index', compact('shipments', 'invoices'));
    }

    public function store(
        Request $request,
        int $shipmentId,
        InvoiceService $invoiceService
    ) {
        $shipment = Shipment::where('company_id', $request->user()->company_id)
            ->findOrFail($shipmentId);

        try {
            $invoice = $invoiceService->createInvoice(
                $shipment->id,
                $request->user()->id
            );
        } catch (ValidationException $e) {
            throw $e;
        }

        return redirect()
            ->route('invoices.show', $invoice->id)
            ->with('success', 'Invoice generated successfully.');
    }

    public function show(Request $request, int $id)
    {
        $invoice = Invoice::with([
                'company',
                'customer',
                'salesOrder',
                'shipment.warehouse',
                'items',
                'creator',
            ])
            ->where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        return view('invoices.show', compact('invoice'));
    }
}