<?php

namespace Tests\Unit\FamilyPack;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Support\PriceRules;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PriceRulesTest extends TestCase
{
    public static function cases(): array
    {
        return [
            'real sale below regular'      => [90, 100, 90.0],
            'no sale (null)'               => [null, 100, 100.0],
            'sale of zero is not free'     => [0, 100, 100.0],
            'sale equal to regular'        => [100, 100, 100.0],
            'sale above regular is ignored' => [150, 100, 100.0],
            'no regular, sale present'     => [80, 0, 80.0],
            'both empty'                   => [null, null, 0.0],
            'string decimals from DB'      => ['675.00', '750.00', 675.0],
            'rounding to paise'            => [99.999, 120, 100.0],
        ];
    }

    #[DataProvider('cases')]
    public function test_effective_price(mixed $sale, mixed $regular, float $expected): void
    {
        $this->assertSame($expected, PriceRules::effective($sale, $regular));
    }

    public function test_variation_price_wins_over_product_price(): void
    {
        $product = new Product(['price' => 500, 'sale_price' => null]);
        $variation = new ProductVariation(['price' => 120, 'sale_price' => 110]);

        $this->assertSame(110.0, PriceRules::forItem($product, $variation));
    }

    public function test_product_price_is_used_when_variation_has_no_usable_price(): void
    {
        $product = new Product(['price' => 500, 'sale_price' => 450]);
        $variation = new ProductVariation(['price' => 0, 'sale_price' => null]);

        $this->assertSame(450.0, PriceRules::forItem($product, $variation));
        $this->assertSame(450.0, PriceRules::forItem($product, null));
    }

    public function test_line_total_is_rounded(): void
    {
        $this->assertSame(333.3, PriceRules::lineTotal(111.1, 3));
    }
}
