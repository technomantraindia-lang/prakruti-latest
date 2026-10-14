<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductBatch;
use Illuminate\Http\Request;

class ProductBatchController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductBatch::with(['product', 'vendor', 'grnItem.grn']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('sku', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('near_expiry_only')) {
            $query->where('status', '!=', 'depleted')
                  ->whereNotNull('expiry_date')
                  ->where('expiry_date', '<=', now()->addDays(60))
                  ->where('expiry_date', '>=', now());
        }

        if ($request->boolean('expired_only')) {
            $query->where('status', '!=', 'depleted')
                  ->whereNotNull('expiry_date')
                  ->where('expiry_date', '<', now());
        }

        $batches = $query->latest('id')->paginate(20)->withQueryString();

        $stats = [
            'total_batches' => ProductBatch::count(),
            'active_batches' => ProductBatch::where('status', 'active')->where('current_qty', '>', 0)->count(),
            'near_expiry_count' => ProductBatch::where('status', '!=', 'depleted')
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays(60))
                ->where('expiry_date', '>=', now())
                ->count(),
            'expired_count' => ProductBatch::where('status', '!=', 'depleted')
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<', now())
                ->count(),
        ];

        return view('admin.inventory.batches', compact('batches', 'stats'));
    }
}
