<?php

namespace Tests\Feature\FamilyPack;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FamilyPackFixtures;
use Tests\TestCase;

/**
 * The basket must be balanced across products, not only correct in total.
 */
class FamilyPackDistributionTest extends TestCase
{
    use RefreshDatabase;
    use FamilyPackFixtures;

    private function lines(): array
    {
        $this->pantry();

        return $this->postJson('/api/v1/family-packs/plan', ['members' => $this->fatherMotherSonGrandmother()])->json('data.basket.lines');
    }

    private function kgByProduct(array $lines, string $group): array
    {
        $out = [];
        foreach ($this->linesByGroup($lines, $group) as $l) {
            $out[$l['name']] = ($out[$l['name']] ?? 0) + $l['qty'] * $l['pack_grams'] / 1000;
        }

        return $out;
    }

    public function test_pulses_are_spread_across_products_not_dumped_on_one(): void
    {
        $kg = $this->kgByProduct($this->lines(), 'pulses');
        $this->assertGreaterThanOrEqual(5, count($kg), 'all five pulses should appear');
        $total = array_sum($kg);
        foreach ($kg as $name => $v) {
            $this->assertLessThan(0.40 * $total, $v, "{$name} has more than 40% of all pulses");
            $this->assertGreaterThan(0.08 * $total, $v, "{$name} has a token amount");
        }
    }

    public function test_a_cheaper_or_sale_priced_product_does_not_hog_the_group(): void
    {
        $kg = $this->kgByProduct($this->lines(), 'pulses');
        $this->assertLessThan(0.40 * array_sum($kg), $kg['Masoor Dal/Split Red Lentil']);   // Masoor has a sale price in the fixture
    }

    public function test_seeds_and_nuts_respect_per_person_caps_within_one_pack(): void
    {
        $kg = $this->kgByProduct($this->lines(), 'nuts');
        $this->assertLessThanOrEqual(0.150 * 4 + 0.250, $kg['Chia Seed']);
        $this->assertLessThanOrEqual(0.300 * 4 + 0.250, $kg['Flax Seed']);
    }

    public function test_cereal_buckets_stay_balanced(): void
    {
        $lines = $this->lines();
        $bySub = [];
        foreach ($this->linesByGroup($lines, 'cereals') as $l) {
            $bySub[$l['food_subgroup']] = ($bySub[$l['food_subgroup']] ?? 0) + $l['qty'] * $l['pack_grams'] / 1000;
        }
        $total = array_sum($bySub);
        $this->assertEqualsWithDelta(0.25, $bySub['millets'] / $total, 0.05);
        $this->assertEqualsWithDelta(0.41, $bySub['wheat_flours'] / $total, 0.07);   // 75% of non-millet × 55%
        $this->assertEqualsWithDelta(0.34, $bySub['rice'] / $total, 0.07);
    }

    public function test_oil_is_split_over_at_most_two_products_and_ghee_over_one(): void
    {
        $lines = $this->lines();
        $this->assertLessThanOrEqual(2, count(array_unique(array_column($this->linesByGroup($lines, 'oil'), 'product_id'))));
        $this->assertCount(1, array_unique(array_column($this->linesByGroup($lines, 'ghee'), 'product_id')));
    }
}
