<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $rangeKey = $request->input('range', 'monthly');
        $allowedRanges = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'];
        if (! in_array($rangeKey, $allowedRanges, true)) {
            $rangeKey = 'monthly';
        }

        [$from, $to] = $this->resolveRange($rangeKey, $request);
        [$trendLabels, $trendKeys, $granularity] = $this->buildTrendBuckets($from, $to, $rangeKey);

        $periodOrders = Order::with(['items.product.category', 'items.variation', 'user'])
            ->whereNotIn('status', ['cancelled', 'failed', 'refunded'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        $trend = collect($trendKeys)->mapWithKeys(fn ($key, $index) => [
            $key => ['label' => $trendLabels[$index], 'sales' => 0, 'orders' => 0, 'units' => 0],
        ])->all();

        $productMetrics = [];
        $categorySales = [];

        foreach ($periodOrders as $order) {
            $bucket = $this->bucketKey($order->created_at, $granularity);
            if (isset($trend[$bucket])) {
                $trend[$bucket]['sales'] += (float) $order->total;
                $trend[$bucket]['orders']++;
            }

            foreach ($order->items as $item) {
                $qty = (int) $item->qty;
                $revenue = (float) ($item->line_total ?? ((float) $item->price * $qty));
                $costPrice = (float) ($item->variation?->cost_price ?? $item->product?->cost_price ?? 0);
                $cost = $costPrice * $qty;
                $productId = (int) $item->product_id;

                if (! isset($productMetrics[$productId])) {
                    $productMetrics[$productId] = [
                        'id' => $productId,
                        'name' => $item->product_name ?: ($item->product?->name ?? 'Unknown product'),
                        'sku' => $item->sku ?: ($item->product?->sku ?? '-'),
                        'units' => 0,
                        'revenue' => 0,
                        'cost' => 0,
                        'margin' => 0,
                        'margin_pct' => 0,
                    ];
                }

                $productMetrics[$productId]['units'] += $qty;
                $productMetrics[$productId]['revenue'] += $revenue;
                $productMetrics[$productId]['cost'] += $cost;
                $productMetrics[$productId]['margin'] += $revenue - $cost;

                $categoryName = $item->product?->category?->name ?? 'Uncategorized';
                $categorySales[$categoryName] = ($categorySales[$categoryName] ?? 0) + $revenue;
                if (isset($trend[$bucket])) {
                    $trend[$bucket]['units'] += $qty;
                }
            }
        }

        foreach ($productMetrics as &$metric) {
            $metric['margin_pct'] = $metric['revenue'] > 0
                ? round(($metric['margin'] / $metric['revenue']) * 100, 1)
                : 0;
            $metric['revenue'] = round($metric['revenue'], 2);
            $metric['cost'] = round($metric['cost'], 2);
            $metric['margin'] = round($metric['margin'], 2);
        }
        unset($metric);

        $allProducts = Product::with(['category', 'variations', 'batches.variation'])->orderBy('name')->get();
        $allMetrics = $allProducts->map(function ($product) use ($productMetrics) {
            return $productMetrics[$product->id] ?? [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku ?: '-',
                'units' => 0,
                'revenue' => 0,
                'cost' => 0,
                'margin' => 0,
                'margin_pct' => 0,
            ];
        });

        // Rankings describe products that actually sold in the selected
        // period. Showing every catalog item with zero units makes an empty
        // period look like real sales data.
        $soldMetrics = $allMetrics->filter(fn ($metric) => $metric['units'] > 0)->values();
        $highestSelling = $soldMetrics->sortByDesc('units')->take(5)->values()->all();
        $highMargin = $soldMetrics->sortByDesc('margin')->take(5)->values()->all();
        $lowSelling = $soldMetrics->sort(function ($a, $b) {
            return ($a['units'] <=> $b['units']) ?: ($a['revenue'] <=> $b['revenue']);
        })->take(5)->values()->all();

        $categorySales = collect($categorySales)->sortDesc();
        $categoryChart = $categorySales->take(5)->all();
        if ($categorySales->count() > 5) {
            $categoryChart['Other'] = $categorySales->slice(5)->sum();
        }

        $inventoryValue = 0.0;
        $inventoryRows = $allProducts->map(function (Product $product) use (&$inventoryValue) {
            $activeBatches = $product->batches
                ->where('status', 'active')
                ->where('current_qty', '>', 0)
                ->sortBy(fn ($batch) => $batch->expiry_date?->timestamp ?? PHP_INT_MAX)
                ->values();

            if ($activeBatches->isNotEmpty()) {
                foreach ($activeBatches as $batch) {
                    $inventoryValue += (int) $batch->current_qty
                        * (float) ($batch->landed_unit_cost ?? $batch->unit_purchase_price ?? 0);
                }
            } else {
                $activeVariations = $product->variations->where('status', 'active');
                if ($activeVariations->isNotEmpty()) {
                    foreach ($activeVariations as $variation) {
                        $inventoryValue += (int) $variation->stock_qty
                            * (float) ($variation->cost_price ?? $product->cost_price ?? 0);
                    }
                } else {
                    $inventoryValue += (int) $product->stock_qty * (float) ($product->cost_price ?? 0);
                }
            }

            $batch = $activeBatches->first();
            $variation = $batch?->variation ?: $product->variations->where('status', 'active')->first();

            return [
                'product_name' => $product->name,
                'sku' => $variation?->sku ?: ($product->sku ?: '-'),
                'package' => $variation?->attr_val ?: ($product->unit ?: 'Product'),
                'stock' => (int) $product->stock_qty,
                'mrp' => (float) ($variation?->price ?? $product->price ?? 0),
                'batch_number' => $batch?->batch_number,
                'expiry_date' => $batch?->expiry_date?->format('d M Y'),
                'expiry_sort' => $batch?->expiry_date?->timestamp ?? PHP_INT_MAX,
            ];
        })->sortBy('expiry_sort')->take(12)->values()->all();

        $expiryBatches = ProductBatch::with(['product', 'variation'])
            ->where('status', 'active')
            ->where('current_qty', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(60))
            ->orderBy('expiry_date')
            ->limit(10)
            ->get();

        $stats = [
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'total_customers' => User::whereHas('role', fn ($query) => $query->where('name', 'Customer'))->count(),
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'completed_orders' => Order::where('status', 'delivered')->count(),
            'total_sales' => round($periodOrders->sum(fn ($order) => (float) $order->total), 2),
            'period_orders' => $periodOrders->count(),
            'period_units' => $periodOrders->sum(fn ($order) => $order->items->sum('qty')),
            'period_margin' => round(collect($productMetrics)->sum('margin'), 2),
            'low_stock_products' => $allProducts->filter(fn (Product $product) => $product->isLowStock())->count(),
            'expiry_alerts' => $expiryBatches->count(),
            'inventory_value' => round($inventoryValue, 2),
        ];

        $recentOrders = Order::with(['user', 'items'])->latest()->limit(6)->get();

        return view('admin.dashboard.index', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'rangeKey' => $rangeKey,
            'rangeLabel' => $this->rangeLabel($rangeKey, $from, $to),
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
            'trend' => array_values($trend),
            'categoryChart' => $categoryChart,
            'highestSelling' => $highestSelling,
            'highMargin' => $highMargin,
            'lowSelling' => $lowSelling,
            'inventoryRows' => $inventoryRows,
            'expiryBatches' => $expiryBatches,
        ]);
    }

    private function resolveRange(string $rangeKey, Request $request): array
    {
        $now = now();

        return match ($rangeKey) {
            'daily' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'weekly' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'quarterly' => [$now->copy()->firstOfQuarter()->startOfDay(), $now->copy()->lastOfQuarter()->endOfDay()],
            'yearly' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom' => $this->customRange($request, $now),
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    private function customRange(Request $request, Carbon $now): array
    {
        try {
            $from = Carbon::parse($request->input('date_from', $now->copy()->subDays(29)->toDateString()))->startOfDay();
            $to = Carbon::parse($request->input('date_to', $now->toDateString()))->endOfDay();
            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }
            return [$from, $to];
        } catch (\Throwable) {
            return [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()];
        }
    }

    private function buildTrendBuckets(Carbon $from, Carbon $to, string $rangeKey): array
    {
        $days = $from->diffInDays($to);
        $granularity = in_array($rangeKey, ['quarterly', 'yearly'], true) || $days > 31 ? 'month' : 'day';
        $cursor = $granularity === 'month' ? $from->copy()->startOfMonth() : $from->copy()->startOfDay();
        $end = $granularity === 'month' ? $to->copy()->startOfMonth() : $to->copy()->startOfDay();
        $labels = [];
        $keys = [];

        while ($cursor->lte($end)) {
            $keys[] = $granularity === 'month' ? $cursor->format('Y-m') : $cursor->format('Y-m-d');
            $labels[] = $granularity === 'month' ? $cursor->format('M Y') : $cursor->format('d M');
            $granularity === 'month' ? $cursor->addMonth() : $cursor->addDay();
        }

        return [$labels, $keys, $granularity];
    }

    private function bucketKey($createdAt, string $granularity): string
    {
        $date = $createdAt instanceof Carbon ? $createdAt : Carbon::parse($createdAt);
        return $granularity === 'month' ? $date->format('Y-m') : $date->format('Y-m-d');
    }

    private function rangeLabel(string $rangeKey, Carbon $from, Carbon $to): string
    {
        if ($rangeKey === 'custom') {
            return $from->format('d M Y') . ' – ' . $to->format('d M Y');
        }

        return ucfirst($rangeKey) . ' · ' . $from->format('d M Y') . ' – ' . $to->format('d M Y');
    }
}
