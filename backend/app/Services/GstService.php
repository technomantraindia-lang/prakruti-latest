<?php

namespace App\Services;

use App\Models\Product;

class GstService
{
    public function productGstPercentage(Product $product): float
    {
        $category = $product->subSubCategory ?: $product->subCategory ?: $product->category;

        return (float) ($category?->gst_percentage ?? $product->gst_percentage ?? 18);
    }

    /**
     * Reverse tax extraction for GST-inclusive product pricing.
     * Taxable = (Inclusive * 100) / (100 + R)
     * Tax = Inclusive - Taxable
     */
    public function extractReverseTax(float $inclusiveAmount, float $gstPct): array
    {
        if ($inclusiveAmount <= 0 || $gstPct <= 0) {
            return [
                'taxable' => $inclusiveAmount,
                'tax' => 0.0,
            ];
        }

        $taxable = round(($inclusiveAmount * 100) / (100 + $gstPct), 2);
        $tax = round($inclusiveAmount - $taxable, 2);

        return [
            'taxable' => $taxable,
            'tax' => $tax,
        ];
    }

    /**
     * Calculate Intra-State vs Inter-State GST split.
     */
    public function calculateSplit(float $totalGst, ?string $sellerState = null, ?string $customerState = null): array
    {
        $seller = strtolower(trim($sellerState ?: 'Gujarat'));
        $customer = strtolower(trim($customerState ?: 'Gujarat'));

        if ($seller === $customer || empty($customer)) {
            $cgst = round($totalGst / 2, 2);
            $sgst = round($totalGst - $cgst, 2);
            return [
                'cgst' => $cgst,
                'sgst' => $sgst,
                'igst' => 0.0,
            ];
        }

        return [
            'cgst' => 0.0,
            'sgst' => 0.0,
            'igst' => $totalGst,
        ];
    }

    /**
     * Calculate total GST across items using inclusive pricing formula.
     */
    public function calculateTotal(array $lines, float $subtotal, float $discount): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        return round(array_reduce($lines, function ($total, $line) use ($subtotal, $discount) {
            $lineTotal = (float) ($line['line_total'] ?? 0);
            $gstPct = (float) ($line['gst_pct'] ?? 0);
            $lineDiscount = $discount > 0 ? round($discount * ($lineTotal / $subtotal), 2) : 0.0;
            $netLineInclusive = max(0, $lineTotal - $lineDiscount);

            $extracted = $this->extractReverseTax($netLineInclusive, $gstPct);

            return $total + $extracted['tax'];
        }, 0.0), 2);
    }
}
