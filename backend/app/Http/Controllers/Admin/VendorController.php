<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::withCount(['vendorProducts', 'goodsReceivedNotes', 'purchaseBills']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('gstin', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $vendors = $query->latest()->paginate(15)->withQueryString();

        return view('admin.vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('admin.vendors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'gstin' => 'nullable|string|size:15',
            'pan' => 'nullable|string|size:10',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'state_code' => 'nullable|string|max:2',
            'pincode' => 'nullable|string|max:10',
            'payment_terms_days' => 'nullable|integer|min:0',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:100',
            'bank_ifsc' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['payment_terms_days'] = $validated['payment_terms_days'] ?? 30;

        // Auto extract state code from GSTIN if state_code empty
        if (empty($validated['state_code']) && ! empty($validated['gstin'])) {
            $validated['state_code'] = substr($validated['gstin'], 0, 2);
        }

        $vendor = Vendor::create($validated);

        return redirect()->route('admin.vendors.show', $vendor)
            ->with('success', "Vendor '{$vendor->display_name}' created successfully.");
    }

    public function show(Vendor $vendor)
    {
        $vendor->load([
            'vendorProducts.product',
            'vendorProducts.variation',
            'goodsReceivedNotes.items',
            'purchaseBills',
        ]);

        $allProducts = Product::where('status', 'active')->orderBy('name')->get();

        return view('admin.vendors.show', compact('vendor', 'allProducts'));
    }

    public function edit(Vendor $vendor)
    {
        return view('admin.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'gstin' => 'nullable|string|size:15',
            'pan' => 'nullable|string|size:10',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'state_code' => 'nullable|string|max:2',
            'pincode' => 'nullable|string|max:10',
            'payment_terms_days' => 'nullable|integer|min:0',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:100',
            'bank_ifsc' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['payment_terms_days'] = $validated['payment_terms_days'] ?? 30;

        if (empty($validated['state_code']) && ! empty($validated['gstin'])) {
            $validated['state_code'] = substr($validated['gstin'], 0, 2);
        }

        $vendor->update($validated);

        return redirect()->route('admin.vendors.show', $vendor)
            ->with('success', 'Vendor details updated successfully.');
    }

    public function destroy(Vendor $vendor)
    {
        if ($vendor->goodsReceivedNotes()->exists() || $vendor->purchaseBills()->exists()) {
            return back()->with('error', 'Cannot delete vendor because existing inward stock or purchase bills are linked.');
        }

        $vendorName = $vendor->display_name;
        $vendor->vendorProducts()->delete();
        $vendor->delete();

        return redirect()->route('admin.vendors.index')
            ->with('success', "Vendor '{$vendorName}' deleted successfully.");
    }

    public function storeProductMapping(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variation_id' => 'nullable|exists:product_variations,id',
            'vendor_sku' => 'nullable|string|max:100',
            'purchase_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'moq' => 'nullable|integer|min:1',
            'lead_time_days' => 'nullable|integer|min:1',
            'is_primary' => 'nullable|boolean',
        ]);

        $isPrimary = $request->boolean('is_primary');

        if ($isPrimary) {
            VendorProduct::where('product_id', $validated['product_id'])
                ->update(['is_primary' => false]);
        }

        VendorProduct::updateOrCreate(
            [
                'vendor_id' => $vendor->id,
                'product_id' => $validated['product_id'],
                'product_variation_id' => $validated['product_variation_id'] ?? null,
            ],
            [
                'vendor_sku' => $validated['vendor_sku'] ?? null,
                'purchase_price' => $validated['purchase_price'],
                'tax_rate' => $validated['tax_rate'] ?? 0,
                'moq' => $validated['moq'] ?? 1,
                'lead_time_days' => $validated['lead_time_days'] ?? 7,
                'is_primary' => $isPrimary,
            ]
        );

        return back()->with('success', 'Product mapped to vendor successfully.');
    }

    public function destroyProductMapping(Vendor $vendor, VendorProduct $vendorProduct)
    {
        if ($vendorProduct->vendor_id !== $vendor->id) {
            abort(403);
        }

        $vendorProduct->delete();

        return back()->with('success', 'Product mapping removed.');
    }
}
