<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'variations' => fn ($variationQuery) => $variationQuery->where('status', 'active')])
            ->orderByRaw('CASE WHEN stock_qty <= low_stock_qty THEN 0 ELSE 1 END')
            ->orderBy('stock_qty');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
        }

        $products = $query->paginate(20)->withQueryString();
        $lowStockCount = Product::whereColumn('stock_qty', '<=', 'low_stock_qty')->count();
        $outOfStockCount = Product::where('stock_qty', 0)->count();
        $recentLogs = InventoryLog::with(['product', 'user'])->latest()->limit(10)->get();

        return view('admin.inventory.index', compact('products', 'lowStockCount', 'outOfStockCount', 'recentLogs'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'action' => 'required|in:add,reduce,set',
            'quantity' => 'required|integer|min:0',
            'variation_id' => 'nullable|exists:product_variations,id',
            'note' => 'nullable|string|max:255',
        ]);

        $inventoryService = app(\App\Services\InventoryService::class);
        $note = $data['note'] ?? 'Manual stock adjustment via Admin';

        $target = $product;
        $activeVariations = $product->variations()->where('status', 'active')->get();
        if ($activeVariations->isNotEmpty()) {
            if (empty($data['variation_id'])) {
                return back()->with('error', 'Select the exact package/weight before changing stock.');
            }

            $target = ProductVariation::whereKey($data['variation_id'])
                ->where('product_id', $product->id)
                ->where('status', 'active')
                ->first();

            if (! $target) {
                return back()->with('error', 'The selected package does not belong to this product.');
            }
        }

        try {
            match ($data['action']) {
                'add' => $inventoryService->increaseStock($target, (int) $data['quantity'], $note),
                'reduce' => $inventoryService->decreaseStock($target, (int) $data['quantity'], $note),
                'set' => $inventoryService->adjustStock($target, (int) $data['quantity'], $note),
            };
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $packageLabel = $target instanceof ProductVariation ? (' (' . ($target->attr_val ?: ($target->weight ? $target->weight . ' kg' : 'package')) . ')') : '';
        return redirect()->route('admin.inventory.index')->with('success', "Stock updated successfully for {$product->name}{$packageLabel}.");
    }
}
