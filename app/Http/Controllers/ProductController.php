<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::where('company_id', $request->user()->company_id)
            ->orderBy('code')->get();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'code' => ['required', 'max:20',
                Rule::unique('products')->where('company_id', $companyId)],
            'name' => ['required', 'string', 'max:255'],
            'hsn_code' => ['required', 'string', 'max:20'],
            'gst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'unit' => ['required', 'string', 'max:20'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['company_id'] = $companyId;
        Product::create($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Request $request, int $id)
    {
        $product = Product::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        return view('products.edit', compact('product'));
    }

    public function update(Request $request, int $id)
    {
        $companyId = $request->user()->company_id;

        $product = Product::where('company_id', $companyId)
            ->findOrFail($id);

        $validated = $request->validate([
            'code' => ['required', 'max:20',
                Rule::unique('products')->where('company_id', $companyId)
                    ->ignore($product->id)],
            'name' => ['required', 'string', 'max:255'],
            'hsn_code' => ['required', 'string', 'max:20'],
            'gst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'unit' => ['required', 'string', 'max:20'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $product = Product::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        try {
            $product->delete();

            return redirect()->route('products.index')
                ->with('success', 'Product deleted successfully.');
        } catch (QueryException $e) {
            return back()->with('error',
                'This product is linked to inventory or transactions and cannot be deleted.');
        }
    }
}
