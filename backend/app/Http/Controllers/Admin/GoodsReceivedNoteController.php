<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceivedNote;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\ProcurementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoodsReceivedNoteController extends Controller
{
    protected ProcurementService $procurementService;

    public function __construct(ProcurementService $procurementService)
    {
        $this->procurementService = $procurementService;
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

    public function create(Request $request)
    {
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $selectedVendorId = $request->vendor_id;

        return view('admin.inward.create', compact('vendors', 'products', 'selectedVendorId'));
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
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variation_id' => 'nullable|exists:product_variations,id',
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

    public function getVendorProducts(Vendor $vendor)
    {
        $vendorProducts = $vendor->vendorProducts()->with('product')->get();
        return response()->json($vendorProducts);
    }
}
