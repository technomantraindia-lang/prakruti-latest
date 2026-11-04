<?php

namespace Tests\Feature\FamilyPack;

use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\FamilyPackFixtures;
use Tests\TestCase;

/**
 * The Family Pack eager-loads batches for speed. That must never change what "available stock" means.
 */
class StockEagerLoadingTest extends TestCase
{
    use RefreshDatabase;
    use FamilyPackFixtures;

    private function batch(Product $p, ?int $variationId, int $qty, ?string $expiry, string $status = 'active'): void
    {
        ProductBatch::create([
            'product_id' => $p->id, 'product_variation_id' => $variationId, 'batch_number' => 'B' . uniqid(),
            'expiry_date' => $expiry, 'initial_qty' => $qty, 'current_qty' => $qty, 'status' => $status,
        ]);
    }

    private function assertSameStock(int $productId, int $variationId, int $expectedVariation): void
    {
        $lazy = Product::with('variations')->find($productId);
        $eager = Product::with(['batches', 'variations' => fn ($q) => $q->with('batches')])->find($productId);

        $this->assertSame($expectedVariation, $lazy->variations->first()->available_stock, 'lazy variation stock');
        $this->assertSame($lazy->variations->first()->available_stock, $eager->variations->first()->available_stock, 'eager variation stock');
        $this->assertSame($lazy->available_stock, $eager->available_stock, 'product stock');
    }

    public function test_no_batches_uses_plain_stock_quantity(): void
    {
        $p = $this->product('Toor Dal', ['1kg' => 7], 100);
        $this->assertSameStock($p->id, $p->variations->first()->id, 7);
    }

    public function test_sellable_batches_define_the_stock(): void
    {
        $p = $this->product('Toor Dal', ['1kg' => 99], 100);
        $v = $p->variations->first();
        $this->batch($p, $v->id, 6, now()->addYear()->toDateString());
        $this->batch($p, $v->id, 4, null);
        $this->assertSameStock($p->id, $v->id, 10);
    }

    public function test_only_expired_or_inactive_batches_mean_zero_not_the_plain_quantity(): void
    {
        $p = $this->product('Toor Dal', ['1kg' => 99], 100);
        $v = $p->variations->first();
        $this->batch($p, $v->id, 5, now()->addDays(10)->toDateString());              // expires inside the 60-day cut-off
        $this->batch($p, $v->id, 5, now()->addYear()->toDateString(), 'quarantined');  // not active
        $this->assertSameStock($p->id, $v->id, 0);
    }

    public function test_plan_makes_a_constant_number_of_queries_however_many_products_exist(): void
    {
        $count = function (int $extra) {
            for ($i = 0; $i < $extra; $i++) {
                $this->product("Extra Dal {$i}-" . uniqid(), ['500g' => 5, '1kg' => 5], 100);
            }
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->postJson('/api/v1/family-packs/plan', ['members' => $this->fatherMotherSonGrandmother()])->assertOk();
            $n = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $n;
        };

        $this->pantry();
        $small = $count(5);
        $large = $count(120);
        $this->assertLessThanOrEqual($small + 3, $large, "queries grew with catalog size: {$small} -> {$large}");
        $this->assertLessThan(40, $large);
    }
}
