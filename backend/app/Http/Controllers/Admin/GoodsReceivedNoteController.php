<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceivedNote;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Vendor;
use App\Services\ProcurementService;
use App\Services\InwardStockImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GoodsReceivedNoteController extends Controller
{
    protected ProcurementService $procurementService;
    protected InwardStockImportService $importService;

    public function __construct(ProcurementService $procurementService, InwardStockImportService $importService)
    {
        $this->procurementService = $procurementService;
        $this->importService = $importService;
    }

    public function index(Request $request)
    {
        $query = GoodsReceivedNote::with(['vendor', 'receiver', 'items.product', 'purchaseBill']);

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('grn_number', 'like', "%{$search}%")
                  ->orWhere('vendor_invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('vendor', function ($vq) use ($search) {
                      $vq->where('name', 'like', "%{$search}%")
                         ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('received_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('received_date', '<=', $request->date_to);
        }

        $grns = $query->latest('received_date')->latest('id')->paginate(15)->withQueryString();
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();

        return view('admin.inward.index', compact('grns', 'vendors'));
    }

    public function downloadImportTemplate()
    {
        $path = $this->importService->ensureTemplateExists();
        abort_unless(is_file($path), 404, 'Import template is not available.');

        return response()->download($path, 'prakruti-inward-stock-import-template.xlsx');
    }

    public function importStore(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:xlsx,csv,txt',
        ]);

        try {
            $count = $this->importService->import($request->file('file'), Auth::user());

            return redirect()->route('admin.inward.index')
                ->with('success', "{$count} inward GRN group(s) imported successfully. Stock, MRP, batches and expiry dates are now reflected in Products.");
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('error', 'Excel import failed: ' . $e->getMessage());
        }
    }

    public function create(Request $request)
    {
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('status', 'active')
            ->with(['variations' => fn ($query) => $query->where('status', 'active')->orderBy('id')])
            ->orderBy('name')
            ->get();
        $productOptions = $products->mapWithKeys(fn (Product $product) => [(string) $product->id => [
            'mrp' => (float) $product->price,
            'variations' => $product->variations->map(fn (ProductVariation $variation) => [
                'id' => $variation->id,
                'label' => $variation->attr_val,
                'sku' => $variation->sku,
                'weight' => $this->variationWeight($variation),
                'mrp' => (float) $variation->price,
            ])->values()->all(),
        ]])->all();
        $selectedVendorId = $request->vendor_id;

        return view('admin.inward.create', compact('vendors', 'products', 'productOptions', 'selectedVendorId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'vendor_invoice_no' => 'nullable|string|max:100',
            'invoice_date' => 'nullable|date',
            'received_date' => 'required|date',
            'inward_freight_charge' => 'nullable|numeric|min:0',
            'inward_handling_charge' => 'nullable|numeric|min:0',
            'payment_status' => 'nullable|in:paid,unpaid',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variation_id' => 'nullable|exists:product_variations,id',
            'items.*.mrp' => 'required|numeric|min:0',
            'items.*.received_qty' => 'required|integer|min:1',
            'items.*.accepted_qty' => 'required|integer|min:0',
            'items.*.rejected_qty' => 'nullable|integer|min:0',
            'items.*.batch_number' => 'required|string|max:100',
            'items.*.mfg_date' => 'nullable|date',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.unit_purchase_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.rejection_reason' => 'nullable|string|max:255',
        ]);

        foreach ($validated['items'] as $index => $item) {
            $product = Product::with('variations')->findOrFail($item['product_id']);
            $hasActivePackaging = $product->variations->where('status', 'active')->isNotEmpty();

            if ($hasActivePackaging && empty($item['product_variation_id'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_variation_id" => 'Select the packaging size received for this product.',
                ]);
            }

            if (! empty($item['product_variation_id']) && ! ProductVariation::whereKey($item['product_variation_id'])
                ->where('product_id', $product->id)
                ->where('status', 'active')
                ->exists()) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_variation_id" => 'The selected packaging does not belong to the selected product.',
                ]);
            }
        }

        if (empty($validated['vendor_invoice_no'])) {
            $validated['vendor_invoice_no'] = GoodsReceivedNote::generateVendorInvoiceNumber();
        }

        try {
            $grn = $this->procurementService->processGoodsReceivedNote($validated, Auth::user());

            return redirect()->route('admin.inward.show', $grn)
                ->with('success', "Stock Inward GRN #{$grn->grn_number} processed successfully! Physical inventory and batches have been updated.");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Error processing inward stock: ' . $e->getMessage());
        }
    }

    public function show(GoodsReceivedNote $grn)
    {
        $grn->load([
            'vendor',
            'receiver',
            'items.product',
            'items.variation',
            'items.batch',
            'purchaseBill',
        ]);

        return view('admin.inward.show', compact('grn'));
    }

    public function edit(GoodsReceivedNote $grn)
    {
        $grn->load(['vendor', 'receiver', 'items.product', 'items.variation', 'items.batch', 'purchaseBill']);

        return view('admin.inward.edit', compact('grn'));
    }

    public function update(Request $request, GoodsReceivedNote $grn)
    {
        $validated = $request->validate([
            'vendor_invoice_no' => 'nullable|string|max:100',
            'invoice_date' => 'nullable|date',
            'received_date' => 'required|date',
            'payment_status' => 'required|in:paid,unpaid',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.batch_number' => 'required|string|max:100',
            'items.*.mfg_date' => 'nullable|date',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.rejection_reason' => 'nullable|string|max:255',
        ]);

        $grn->load(['items.batch', 'purchaseBill']);
        $existingItems = $grn->items->keyBy('id');
        $submittedIds = collect($validated['items'])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $existingIds = $existingItems->keys()->map(fn ($id) => (int) $id)->sort()->values()->all();

        if ($submittedIds !== $existingIds) {
            throw ValidationException::withMessages([
                'items' => 'All existing inward stock lines must be submitted. Quantities and packages remain locked after stock is inwarded.',
            ]);
        }

        DB::transaction(function () use ($validated, $grn, $existingItems) {
            $grn->update([
                'vendor_invoice_no' => $validated['vendor_invoice_no'] ?? null,
                'invoice_date' => $validated['invoice_date'] ?? null,
                'received_date' => $validated['received_date'],
                'payment_status' => $validated['payment_status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $row) {
                $item = $existingItems->get((int) $row['id']);
                $item->update([
                    'batch_number' => $row['batch_number'],
                    'mfg_date' => $row['mfg_date'] ?? null,
                    'expiry_date' => $row['expiry_date'] ?? null,
                    'rejection_reason' => $row['rejection_reason'] ?? null,
                ]);

                if ($item->batch) {
                    $item->batch->update([
                        'batch_number' => $row['batch_number'],
                        'mfg_date' => $row['mfg_date'] ?? null,
                        'expiry_date' => $row['expiry_date'] ?? null,
                    ]);
                }
            }

            if ($grn->purchaseBill) {
                $grn->purchaseBill->update([
                    'vendor_invoice_no' => $validated['vendor_invoice_no'] ?? null,
                    'invoice_date' => $validated['invoice_date'] ?? null,
                    'payment_status' => $validated['payment_status'],
                    'paid_amount' => $validated['payment_status'] === 'paid'
                        ? $grn->purchaseBill->total_amount
                        : 0,
                ]);
            }
        });

        return redirect()->route('admin.inward.show', $grn)
            ->with('success', "GRN #{$grn->grn_number} updated successfully. Stock quantities and package assignments were kept unchanged.");
    }

    public function print(GoodsReceivedNote $grn)
    {
        $grn->load([
            'vendor',
            'receiver',
            'items.product',
            'items.variation',
            'items.batch',
            'purchaseBill',
        ]);

        return view('admin.inward.print', compact('grn'));
    }

    public function updatePaymentStatus(Request $request, GoodsReceivedNote $grn)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:paid,unpaid',
        ]);

        $status = $validated['payment_status'];
        $grn->update(['payment_status' => $status]);

        if ($grn->purchaseBill) {
            $billTotal = (float) $grn->purchaseBill->total_amount;
            $grn->purchaseBill->update([
                'payment_status' => $status,
                'paid_amount' => $status === 'paid' ? $billTotal : 0.0,
            ]);
        }

        return back()->with('success', 'GRN #' . $grn->grn_number . ' payment status marked as ' . strtoupper($status) . ' successfully.');
    }

    public function getVendorProducts(Vendor $vendor)
    {
        $vendorProducts = $vendor->vendorProducts()->with('product')->get();
        return response()->json($vendorProducts);
    }

    private function variationWeight(ProductVariation $variation): ?string
    {
        if ($variation->net_qty !== null) {
            $quantity = rtrim(rtrim(number_format((float) $variation->net_qty, 3, '.', ''), '0'), '.');
            return trim($quantity . ' ' . ($variation->net_unit ?: 'g'));
        }

        return $variation->attr_val ?: null;
    }
}
