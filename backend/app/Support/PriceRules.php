<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariation;

/**
 * The ONE place that decides what a customer pays per unit.
 *
 * Rule: a sale price counts only when it is greater than 0 and strictly lower than the regular price.
 * Anything else (null, 0, equal to, or higher than the regular price) means "no sale" and the regular price is used.
 * A variation price wins over the product price; the product price is used only when the variation has no usable price.
 */
class PriceRules
{
    public static function effective(null|int|float|string $salePrice, null|int|float|string $regularPrice): float
    {
        $sale = (float) ($salePrice ?? 0);
        $regular = (float) ($regularPrice ?? 0);

        if ($sale > 0 && ($regular <= 0 || $sale < $regular)) {
            return round($sale, 2);
        }

        return round(max(0.0, $regular), 2);
    }

    public static function forItem(?Product $product, ?ProductVariation $variation = null): float
    {
        if ($variation) {
            $price = self::effective($variation->sale_price, $variation->price);
            if ($price > 0) {
                return $price;
            }
        }

        return $product ? self::effective($product->sale_price, $product->price) : 0.0;
    }

    public static function lineTotal(float $unitPrice, int $qty): float
    {
        return round($unitPrice * $qty, 2);
    }
}
