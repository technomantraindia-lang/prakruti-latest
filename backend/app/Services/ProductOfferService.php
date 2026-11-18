<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;

class ProductOfferService
{
    /**
     * Get all configured offers and enabled status
     */
    public function getOfferConfig(): array
    {
        $enabled = filter_var(Setting::get('product_offers_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
        $raw = json_decode((string) Setting::get('product_offers', '[]'), true);

        if (! is_array($raw)) {
            $raw = [];
        }

        return [
            'enabled' => $enabled,
            'offers' => $raw,
        ];
    }

    /**
     * Get active offers list
     */
    public function getActiveOffers(): array
    {
        $config = $this->getOfferConfig();
        if (! $config['enabled']) {
            return [];
        }

        return array_values(array_filter($config['offers'], function ($offer) {
            return ! empty($offer['is_active']);
        }));
    }

    /**
     * Find active offer for a product
     */
    public function getOfferForProduct(int|string|null $productId): ?array
    {
        if (! $productId) {
            return null;
        }

        $activeOffers = $this->getActiveOffers();

        // 1. First look for specific product match
        foreach ($activeOffers as $offer) {
            if (! empty($offer['product_id']) && (string) $offer['product_id'] === (string) $productId) {
                return $this->formatOffer($offer);
            }
        }

        // 2. Next look for "all" products offer
        foreach ($activeOffers as $offer) {
            if (! empty($offer['product_id']) && (string) $offer['product_id'] === 'all') {
                return $this->formatOffer($offer);
            }
        }

        return null;
    }

    /**
     * Format offer details with helpful labels
     */
    private function formatOffer(array $offer): array
    {
        $type = $offer['offer_type'] ?? 'percentage';
        $discountVal = (float) ($offer['discount_val'] ?? 0);
        $buyQty = max(1, (int) ($offer['buy_qty'] ?? 1));
        $getQty = max(1, (int) ($offer['get_qty'] ?? 1));

        $badge = ! empty($offer['badge_text']) ? trim($offer['badge_text']) : '';
        if (empty($badge) || is_numeric($badge)) {
            if ($type === 'percentage') {
                $badge = "{$discountVal}% OFF";
            } elseif ($type === 'flat') {
                $badge = "₹{$discountVal} OFF";
            } elseif ($type === 'bogo') {
                $badge = "BUY {$buyQty} GET {$getQty} FREE";
            }
        }

        return [
            'id' => $offer['id'] ?? uniqid('offer_'),
            'title' => $offer['title'] ?? $badge,
            'offer_type' => $type,
            'product_id' => $offer['product_id'] ?? 'all',
            'discount_val' => $discountVal,
            'buy_qty' => $buyQty,
            'get_qty' => $getQty,
            'badge_text' => $badge,
            'is_active' => ! empty($offer['is_active']),
        ];
    }

    /**
     * Calculate discount and free units for cart item
     */
    public function applyToItem(int|string $productId, float $unitPrice, int $qty): array
    {
        $offer = $this->getOfferForProduct($productId);

        if (! $offer) {
            return [
                'has_offer' => false,
                'offer' => null,
                'discount_per_unit' => 0.0,
                'total_discount' => 0.0,
                'effective_unit_price' => $unitPrice,
                'free_qty' => 0,
                'offer_label' => null,
            ];
        }

        $type = $offer['offer_type'];
        $discountPerUnit = 0.0;
        $totalDiscount = 0.0;
        $freeQty = 0;
        $label = $offer['title'] ?: $offer['badge_text'];

        if ($type === 'percentage') {
            $pct = min(100, max(0, $offer['discount_val']));
            $discountPerUnit = round($unitPrice * ($pct / 100), 2);
            $totalDiscount = round($discountPerUnit * $qty, 2);
            $label = "{$pct}% Offer Discount";
        } elseif ($type === 'flat') {
            $discountPerUnit = min($unitPrice, max(0, $offer['discount_val']));
            $totalDiscount = round($discountPerUnit * $qty, 2);
            $label = "₹{$discountPerUnit} Off per unit";
        } elseif ($type === 'bogo') {
            $buy = $offer['buy_qty'];
            $get = $offer['get_qty'];
            if ($qty >= $buy) {
                $sets = floor($qty / $buy);
                $freeQty = (int) ($sets * $get);
                $label = "BUY {$buy} GET {$get} FREE ({$freeQty} free item" . ($freeQty > 1 ? 's' : '') . " included!)";
            } else {
                $needed = $buy - $qty;
                $label = "Buy {$needed} more to unlock Buy {$buy} Get {$get} FREE!";
            }
        }

        $effectiveUnitPrice = max(0, round($unitPrice - $discountPerUnit, 2));

        return [
            'has_offer' => true,
            'offer' => $offer,
            'discount_per_unit' => $discountPerUnit,
            'total_discount' => $totalDiscount,
            'effective_unit_price' => $effectiveUnitPrice,
            'free_qty' => $freeQty,
            'offer_label' => $label,
            'badge' => $offer['badge_text'],
        ];
    }
}
