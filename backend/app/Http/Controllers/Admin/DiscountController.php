<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index()
    {
        $settings = Setting::query()->pluck('value', 'key');
        $rules = json_decode((string) ($settings['bill_discount_rules'] ?? '[]'), true);

        if (! is_array($rules) || empty($rules)) {
            $rules = [[
                'min_amount' => $settings['bill_discount_min_amount'] ?? '',
                'percent' => $settings['bill_discount_percent'] ?? '',
            ]];
        }

        $products = Product::where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku']);
        $productOffers = json_decode((string) ($settings['product_offers'] ?? '[]'), true);
        if (! is_array($productOffers)) {
            $productOffers = [];
        }

        return view('admin.discounts.index', [
            'settings' => $settings,
            'rules' => $rules,
            'products' => $products,
            'productOffers' => $productOffers,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'standard_shipping_charge' => ['required', 'numeric', 'min:0'],
            'free_shipping_min_amount' => ['required', 'numeric', 'min:0'],
            'bill_discount_min_amount' => ['nullable', 'array'],
            'bill_discount_min_amount.*' => ['nullable', 'numeric', 'min:0'],
            'bill_discount_percent' => ['nullable', 'array'],
            'bill_discount_percent.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'product_offers' => ['nullable', 'array'],
        ]);

        Setting::set('standard_shipping_charge', (string) $data['standard_shipping_charge']);
        Setting::set('free_shipping_min_amount', (string) $data['free_shipping_min_amount']);
        Setting::set('bill_discount_enabled', $request->boolean('bill_discount_enabled') ? '1' : '0');
        Setting::set('bill_discount_rules', json_encode($this->discountRules($request)));
        Setting::set('product_offers_enabled', $request->boolean('product_offers_enabled') ? '1' : '0');
        Setting::set('product_offers', json_encode($this->parseProductOffers($request)));

        return redirect()->route('admin.discounts.index')->with('success', 'Discount rules, product promotional offers, and delivery settings saved successfully.');
    }

    private function parseProductOffers(Request $request): array
    {
        $rawOffers = $request->input('product_offers', []);
        $parsed = [];

        foreach ($rawOffers as $offer) {
            $productId = trim((string) ($offer['product_id'] ?? ''));
            if ($productId === '') {
                continue;
            }

            $type = in_array($offer['offer_type'] ?? '', ['percentage', 'flat', 'bogo'], true)
                ? $offer['offer_type']
                : 'percentage';

            $discountVal = (float) ($offer['discount_val'] ?? 0);
            $buyQty = max(1, (int) ($offer['buy_qty'] ?? 1));
            $getQty = max(1, (int) ($offer['get_qty'] ?? 1));
            $title = trim((string) ($offer['title'] ?? ''));
            $badgeText = trim((string) ($offer['badge_text'] ?? ''));

            if (empty($badgeText)) {
                if ($type === 'percentage') {
                    $badgeText = "{$discountVal}% OFF";
                } elseif ($type === 'flat') {
                    $badgeText = "₹{$discountVal} OFF";
                } elseif ($type === 'bogo') {
                    $badgeText = "BUY {$buyQty} GET {$getQty} FREE";
                }
            }

            $parsed[] = [
                'id' => $offer['id'] ?? uniqid('offer_'),
                'title' => $title ?: $badgeText,
                'product_id' => $productId,
                'offer_type' => $type,
                'discount_val' => $discountVal,
                'buy_qty' => $buyQty,
                'get_qty' => $getQty,
                'badge_text' => $badgeText,
                'is_active' => ! empty($offer['is_active']),
            ];
        }

        return $parsed;
    }

    private function discountRules(Request $request): array
    {
        $amounts = $request->input('bill_discount_min_amount', []);
        $percents = $request->input('bill_discount_percent', []);
        $rules = [];

        foreach ($amounts as $index => $amount) {
            $minAmount = (float) ($amount ?: 0);
            $percent = (float) ($percents[$index] ?? 0);

            if ($minAmount <= 0 || $percent <= 0) {
                continue;
            }

            $rules[] = [
                'min_amount' => round($minAmount, 2),
                'percent' => round(min($percent, 100), 2),
            ];
        }

        usort($rules, fn (array $a, array $b) => $a['min_amount'] <=> $b['min_amount']);

        return array_values($rules);
    }
}
