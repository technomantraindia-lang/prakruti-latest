<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceivedNote;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseBill;
use App\Models\PurchaseReturn;
use App\Models\Vendor;
use App\Models\VendorDebitNote;
use App\Services\ProcurementService;
use Illuminate\Http\Request;

class PurchaseReturnController extends Controller
{
    protected ProcurementService $procurementService;

    public function __construct(ProcurementService $procurementService)
    {
        $this->procurementService = $procurementService;
    }

    public function index(Request $request)
    {
        $query = PurchaseReturn::with(['vendor', 'debitNote', 'items.product'])->latest();

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('return_number', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('debitNote', fn ($dq) => $dq->where('debit_note_number', 'like', "%{$search}%"));
            });
        }

        $returns = $query->paginate(15)->withQueryString();
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();

        return view('admin.purchase_returns.index', compact('returns', 'vendors'));
    }

    public function create(Request $request)
    {
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $batches = ProductBatch::with('product')
            ->where('current_qty', '>', 0)
            ->latest()
            ->get();

        $selectedVendorId = $request->get('vendor_id');

        return view('admin.purchase_returns.create', compact('vendors', 'products', 'batches', 'selectedVendorId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'reason' => 'required|string|max:255',
            'grn_id' => 'nullable|exists:goods_received_notes,id',
            'purchase_bill_id' => 'nullable|exists:purchase_bills,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.return_qty' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $return = $this->procurementService->createPurchaseReturn($validated, auth()->user());

        return redirect()->route('admin.purchase-returns.show', $return->id)
            ->with('success', "Purchase Return {$return->return_number} and Debit Note {$return->debitNote?->debit_note_number} created successfully.");
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['vendor', 'items.product', 'debitNote', 'purchaseBill', 'grn']);
        return view('admin.purchase_returns.show', compact('purchaseReturn'));
    }
}
