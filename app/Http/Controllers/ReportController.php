<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\InventoryReportService;
use App\Services\CustomerReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(
        Request $request,
        InventoryReportService $inventoryReportService,
        CustomerReportService $customerReportService
    ) {
        $companyId = (int) $request->user()->company_id;

        $stockSummary = $inventoryReportService->getStockSummary($companyId);

        $fifoValuation = $inventoryReportService->getFifoValuation($companyId);

        $backorders = $inventoryReportService->getBackorderReport($companyId);

        $productMargins = $inventoryReportService->getProductMarginReport($companyId);

        $customers = Customer::where('company_id', $companyId)
            ->orderBy('code')
            ->get();

        $customerSummaries = [];
        $customerAgeing = [];

        foreach ($customers as $customer) {
            $customerSummaries[] = $customerReportService->getCustomerSummary(
                $companyId,
                $customer->id
            );

            $customerAgeing[] = $customerReportService->getAgeingReport(
                $companyId,
                $customer->id
            );
        }

        return view('reports.index', compact(
            'stockSummary',
            'fifoValuation',
            'backorders',
            'productMargins',
            'customerSummaries',
            'customerAgeing'
        ));
    }
}
