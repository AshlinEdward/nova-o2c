<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Models\Customer;
use App\Models\Warehouse;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::where('company_id', $request->user()->company_id)
            ->orderBy('code')
            ->get();

        return view('customers.index', compact('customers'));
    }

    public function create(Request $request)
    {
        $warehouses = Warehouse::where('company_id', $request->user()->company_id)
            ->where('is_active', true)->get();

        return view('customers.create', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('customers')->where('company_id', $companyId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'size:15'],
            'state' => ['required', 'string', 'max:100'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'payment_terms_days' => ['required', 'integer', 'min:0'],
            'preferred_warehouse_id' => [
                'nullable',
                Rule::exists('warehouses', 'id')
                    ->where('company_id', $companyId),
            ],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['company_id'] = $companyId;

        Customer::create($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function edit(Request $request, int $id)
    {
        $customer = Customer::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        $warehouses = Warehouse::where('company_id', $request->user()->company_id)
            ->where('is_active', true)->get();

        return view('customers.edit', compact('customer', 'warehouses'));
    }

    public function update(Request $request, int $id)
    {
        $companyId = $request->user()->company_id;

        $customer = Customer::where('company_id', $companyId)
            ->findOrFail($id);

        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('customers')
                    ->where('company_id', $companyId)
                    ->ignore($customer->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'size:15'],
            'state' => ['required', 'string', 'max:100'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'payment_terms_days' => ['required', 'integer', 'min:0'],
            'preferred_warehouse_id' => [
                'nullable',
                Rule::exists('warehouses', 'id')
                    ->where('company_id', $companyId),
            ],
            'is_active' => ['required', 'boolean'],
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $customer = Customer::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        try {
            $customer->delete();

            return redirect()->route('customers.index')
                ->with('success', 'Customer deleted successfully.');
        } catch (QueryException $e) {
            return back()->with('error',
                'This customer is linked to existing transactions and cannot be deleted.');
        }
    }
}
