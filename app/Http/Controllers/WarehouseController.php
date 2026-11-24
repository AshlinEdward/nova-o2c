<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $warehouses = Warehouse::where('company_id', $request->user()->company_id)
            ->orderBy('code')
            ->get();

        return view('warehouses.index', compact('warehouses'));
    }

    public function create()
    {
        return view('warehouses.create');
    }

    public function store(Request $request)
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('warehouses')->where('company_id', $companyId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_active'] = true;

        Warehouse::create($validated);

        return redirect()->route('warehouses.index')
            ->with('success', 'Warehouse created successfully.');
    }

    public function edit(Request $request, int $id)
    {
        $warehouse = Warehouse::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, int $id)
    {
        $companyId = $request->user()->company_id;

        $warehouse = Warehouse::where('company_id', $companyId)
            ->findOrFail($id);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('warehouses')
                    ->where('company_id', $companyId)
                    ->ignore($warehouse->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $warehouse->update($validated);

        return redirect()->route('warehouses.index')
            ->with('success', 'Warehouse updated successfully.');
    }

    public function toggleStatus(Request $request, int $id)
    {
        $warehouse = Warehouse::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        $warehouse->update([
            'is_active' => !$warehouse->is_active,
        ]);

        return redirect()->route('warehouses.index')
            ->with(
                'success',
                $warehouse->is_active
                    ? 'Warehouse activated successfully.'
                    : 'Warehouse deactivated successfully.'
            );
    }
}
