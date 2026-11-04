<?php

namespace Tests\Feature\FamilyPack;

use App\Services\FamilyPack\FamilyPackBasketBuilder;
use App\Support\PriceRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FamilyPackFixtures;
use Tests\TestCase;

class FamilyPackPlanTest extends TestCase
{
    use RefreshDatabase;
    use FamilyPackFixtures;

    private function plan(?array $members = null, array $household = []): array
    {
        $r = $this->postJson('/api/v1/family-packs/plan', ['members' => $members ?? $this->fatherMotherSonGrandmother(), 'household' => $household]);
        $r->assertOk()->assertJsonPath('success', true);

        return $r->json('data');
    }

    // ── The realistic four-member household ───────────────────────────

    public function test_full_household_basket_meets_each_food_group_within_stock(): void
    {
        $this->pantry();
        $d = $this->plan();
        $lines = $d['basket']['lines'];
        $req = $d['plan']['monthly_requirements'];

        // Needs come from the engine (hand-checked in the engine tests)
        $this->assertSame(32.52, $req['cereals_kg']);

        // Packs land close to the need (small overshoot allowed, shortfall within the 8% tolerance)
        $this->assertEqualsWithDelta(32.52, $this->suppliedKg($lines, 'cereals'), 32.52 * 0.08);
        $this->assertEqualsWithDelta(11.22, $this->suppliedKg($lines, 'pulses'), 11.22 * 0.10);

        // Nuts and seeds follow the per-person cap for concentrated seeds (chia 150 g/person/month)
        $chia = collect($lines)->firstWhere('name', 'Chia Seed');
        $this->assertNotNull($chia);
        $this->assertLessThanOrEqual(4 * 150 + 250, $chia['qty'] * $chia['pack_grams']);

        // Oil and ghee are planned separately
        $this->assertNotEmpty($this->linesByGroup($lines, 'oil'));
        $this->assertNotEmpty($this->linesByGroup($lines, 'ghee'));
        $this->assertNotEmpty($this->linesByGroup($lines, 'salt'));
        $this->assertNotEmpty($this->linesByGroup($lines, 'spices'));

        foreach ($lines as $l) {
            $this->assertGreaterThan(0, $l['qty']);
            $this->assertLessThanOrEqual($l['max_qty'], $l['qty'], "{$l['name']} exceeds stock");
            $this->assertNotEmpty($l['reason']);
        }
        $this->assertEqualsWithDelta($d['basket']['subtotal'], array_sum(array_column($lines, 'line_total')), 0.01);
    }

    public function test_millets_get_their_guideline_share_of_cereals(): void
    {
        $this->pantry();
        $d = $this->plan();
        $lines = $d['basket']['lines'];
        $millet = array_sum(array_map(fn ($l) => $l['qty'] * $l['pack_grams'], array_filter($lines, fn ($l) => $l['food_subgroup'] === 'millets'))) / 1000;
        $share = $millet / $this->suppliedKg($lines, 'cereals');
        $this->assertGreaterThan(0.18, $share);
        $this->assertLessThan(0.34, $share);
    }

    public function test_cereals_use_a_mix_not_a_single_product(): void
    {
        $this->pantry();
        $subs = array_unique(array_column($this->linesByGroup($this->plan()['basket']['lines'], 'cereals'), 'food_subgroup'));
        sort($subs);
        $this->assertSame(['millets', 'rice', 'wheat_flours'], $subs);
    }

    public function test_plan_is_deterministic(): void
    {
        $this->pantry();
        $a = $this->plan();
        $b = $this->plan();
        $this->assertSame($a['basket']['lines'], $b['basket']['lines']);
    }

    // ── Coverage is explained, never inflated ─────────────────────────

    public function test_coverage_shows_target_basket_gap_and_expected_outside_foods(): void
    {
        $this->pantry();
        $c = $this->plan()['basket']['coverage'];

        foreach (['energy_kcal', 'protein_g', 'carbs_g', 'fat_g', 'fiber_g'] as $k) {
            $n = $c['nutrients'][$k];
            $this->assertGreaterThan(0, $n['target_monthly']);
            $this->assertGreaterThan(0, $n['basket_monthly']);
            $this->assertEqualsWithDelta(max(0, $n['target_monthly'] - $n['basket_monthly']), $n['gap_monthly'], 0.2);
            $this->assertEqualsWithDelta(round(100 * $n['basket_monthly'] / $n['target_monthly']), $n['pct'], 1);
        }
        $this->assertStringContainsString('Fresh vegetables', $c['explanation']);
        $this->assertNotContains('Wheat Flour (Lokwan)', $c['estimated_products']);   // it has its own label values
        $this->assertContains('Sona Masoori - White Rice', $c['estimated_products']);  // no label → labelled estimate
    }

    public function test_label_values_are_separated_from_estimates_in_coverage(): void
    {
        $this->product('Toor Dal/Arhar Dal', ['1kg' => 50], 130, null, "Per 100g\nEnergy 345 kcal\nProtein 22g\nCarbohydrate 63g\nFat 1g\nFibre 15g");
        $this->product('Moong Dal', ['1kg' => 50], 120);                 // no label → labelled estimate
        $d = $this->plan();
        $lines = collect($d['basket']['lines']);

        $this->assertSame('label', $lines->firstWhere('name', 'Toor Dal/Arhar Dal')['nutrition']['basis']);
        $this->assertSame('estimate', $lines->firstWhere('name', 'Moong Dal')['nutrition']['basis']);
        $c = $d['basket']['coverage']['nutrients']['protein_g'];
        $this->assertGreaterThan(0, $c['from_label']);
        $this->assertGreaterThan(0, $c['from_estimate']);
        $this->assertContains('Moong Dal', $d['basket']['coverage']['estimated_products']);
    }

    public function test_products_without_any_nutrition_are_listed_not_silently_counted(): void
    {
        $this->product('Turmeric Powder', ['250g' => 20], 110, null, null, 'active', 'Indian Spices');
        $this->product('Toor Dal', ['1kg' => 50], 130);
        $d = $this->plan();
        $turmeric = collect($d['basket']['lines'])->firstWhere('name', 'Turmeric Powder');
        $this->assertNull($turmeric['nutrition']['per_100g']);
        $this->assertSame('none', $turmeric['nutrition']['basis']);
        $this->assertStringContainsString('not available', $turmeric['reason']);
    }

    // ── Stock, activity and availability ──────────────────────────────

    public function test_stock_limited_staples_are_capped_and_the_gap_is_reported(): void
    {
        // Live-like situation: only 5 packs of each 1 kg item
        $this->product('Wheat Flour (Lokwan)', ['1kg' => 5], 60);
        $this->product('Sona Masoori - White Rice', ['1kg' => 5], 90);
        $this->product('Ragi Whole/Finger Millet', ['500g' => 5, '1kg' => 5], 80);
        $d = $this->plan();
        $lines = $d['basket']['lines'];

        foreach ($lines as $l) {
            $this->assertLessThanOrEqual(5, $l['qty']);
        }
        $supplied = $this->suppliedKg($lines, 'cereals');
        $this->assertLessThanOrEqual(5 + 5 + 7.5 + 0.01, $supplied);
        $this->assertGreaterThan(14, $supplied);                       // uses all the stock there is
        $cereal = collect($d['basket']['groups'])->firstWhere('label', 'Grains, flours & millets');
        $this->assertSame('partly', $cereal['status']);
        $this->assertNotEmpty(array_filter($d['basket']['shortfalls'], fn ($s) => str_contains($s, 'Grains, flours & millets')));
    }

    public function test_stock_shortfall_of_one_product_is_passed_to_the_others(): void
    {
        $this->product('Wheat Flour', ['1kg' => 2], 60);                      // tiny stock
        $this->product('Multi Grain Flour', ['1kg' => 80], 100);
        $this->product('Sona Masoori - White Rice', ['1kg' => 80], 90);
        $this->product('Ragi Whole/Finger Millet', ['1kg' => 80], 80);
        $lines = $this->plan()['basket']['lines'];
        $this->assertEqualsWithDelta(32.52, $this->suppliedKg($lines, 'cereals'), 32.52 * 0.08);
        $wheat = collect($lines)->firstWhere('name', 'Wheat Flour');
        $this->assertLessThanOrEqual(2, $wheat['qty'] ?? 0);
    }

    public function test_out_of_stock_and_inactive_products_are_never_used(): void
    {
        $this->product('Toor Dal', ['1kg' => 50], 130);
        $this->product('Moong Dal', ['1kg' => 0], 120);
        $this->product('Chana Dal', ['1kg' => 50], 110, null, null, 'inactive');
        $names = array_column($this->plan()['basket']['lines'], 'name');
        $this->assertContains('Toor Dal', $names);
        $this->assertNotContains('Moong Dal', $names);
        $this->assertNotContains('Chana Dal', $names);
    }

    public function test_missing_food_group_is_reported_as_unavailable(): void
    {
        $this->product('Toor Dal', ['1kg' => 50], 130);                      // no oils, no cereals at all
        $d = $this->plan();
        $statuses = collect($d['basket']['groups'])->pluck('status', 'label');
        $this->assertSame('unavailable', $statuses['Cooking oil']);
        $this->assertSame('unavailable', $statuses['Grains, flours & millets']);
        $this->assertNotEmpty(array_filter($d['basket']['shortfalls'], fn ($s) => str_contains($s, 'Cooking oil')));
    }

    // ── Dynamic discovery ─────────────────────────────────────────────

    public function test_a_product_added_later_by_the_admin_is_discovered_automatically(): void
    {
        $this->pantry();
        $before = array_column($this->plan()['basket']['lines'], 'name');
        $this->assertNotContains('Urad Dal Chilka / Black Gram Split', $before);

        $this->product('Urad Dal Chilka / Black Gram Split', ['500g' => 60, '1kg' => 60], 130, null, "Per 100g\nEnergy 347 kcal\nProtein 25g\nCarbohydrate 59g\nFat 1.6g\nFibre 18g");
        $this->product('Barnyard Millet', ['500g' => 60], 90);
        $after = array_column($this->plan()['basket']['lines'], 'name');

        $this->assertContains('Urad Dal Chilka / Black Gram Split', $after);   // high protein+fibre label → well ranked
    }

    public function test_more_than_100_catalog_products_do_not_blow_up_the_basket(): void
    {
        $this->pantry();
        for ($i = 1; $i <= 120; $i++) {
            $this->product("Extra Pulse Variety {$i} Dal", ['500g' => 40, '1kg' => 40], 100 + $i);
        }
        for ($i = 1; $i <= 15; $i++) {
            $this->product("Soap Bar {$i}", ['100g' => 40], 50, null, null, 'active', 'Personal Care');
        }

        $start = microtime(true);
        $d = $this->plan();
        $seconds = microtime(true) - $start;

        $pulses = $this->linesByGroup($d['basket']['lines'], 'pulses');
        $this->assertLessThanOrEqual(6, count(array_unique(array_column($pulses, 'product_id'))), 'too many pulse products');
        $this->assertEqualsWithDelta(11.22, $this->suppliedKg($d['basket']['lines'], 'pulses'), 11.22 * 0.10);
        $this->assertEmpty(array_filter($d['basket']['lines'], fn ($l) => str_contains($l['name'], 'Soap')));
        $this->assertLessThan(20, $seconds, 'planning 150 products should be fast');
    }

    public function test_unclassified_and_non_food_products_never_enter_a_basket(): void
    {
        $this->pantry();
        $this->product('Mystery Item 7', ['1kg' => 50], 100);
        $this->product('Ashwagandha Gold Tablets Test', ['60 tablets' => 5], 350);
        $names = array_column($this->plan()['basket']['lines'], 'name');
        $this->assertNotContains('Mystery Item 7', $names);
        $this->assertNotContains('Ashwagandha Gold Tablets Test', $names);
    }

    // ── Pack sizes ────────────────────────────────────────────────────

    public function test_products_with_unknown_pack_size_are_excluded_and_reported(): void
    {
        $this->pantry();
        $this->product('A2 Cow Ghee Mystery Jar', ['jar' => 30], 800, null, null, 'active', 'Oils & Ghee');
        $d = $this->plan();
        $this->assertNotContains('A2 Cow Ghee Mystery Jar', array_column($d['basket']['lines'], 'name'));
        $this->assertNotEmpty(array_filter($d['basket']['data_quality'], fn ($m) => str_contains($m, 'A2 Cow Ghee Mystery Jar') && str_contains($m, 'pack size')));
    }

    public function test_bare_number_pack_labels_are_used_but_flagged(): void
    {
        $this->product('Toor Dal', ['500' => 50], 130);
        $d = $this->plan();
        $this->assertNotEmpty($this->linesByGroup($d['basket']['lines'], 'pulses'));
        $this->assertNotEmpty(array_filter($d['basket']['data_quality'], fn ($m) => str_contains($m, 'no unit')));
    }

    public function test_explicit_net_quantity_from_admin_is_used(): void
    {
        $this->product('Toor Dal', ['Big sack' => ['stock' => 50, 'net' => [5, 'kg']]], 600);
        $line = $this->linesByGroup($this->plan()['basket']['lines'], 'pulses')[0];
        $this->assertSame(5000.0, (float) $line['pack_grams']);
        $this->assertSame('explicit', $line['pack_basis']);
    }

    public function test_pack_choice_minimises_oversupply(): void
    {
        $builder = app(FamilyPackBasketBuilder::class);
        $product = ['variants' => [
            ['var_id' => 1, 'label' => '1kg', 'pack_text' => '1 kg', 'g' => 1000.0, 'ml' => null, 'basis' => 'label', 'price' => 60.0, 'regular' => 60.0, 'stock' => 20],
            ['var_id' => 2, 'label' => '5kg', 'pack_text' => '5 kg', 'g' => 5000.0, 'ml' => null, 'basis' => 'label', 'price' => 280.0, 'regular' => 280.0, 'stock' => 20],
        ]];
        $picked = $builder->choosePacks($product, 12700, 'g', null);
        $total = array_sum(array_map(fn ($p) => $p['qty'] * $p['variant']['g'], $picked));
        $this->assertGreaterThanOrEqual(12700 * 0.92, $total);
        $this->assertLessThanOrEqual(13000, $total);                  // 2 × 5 kg + 3 × 1 kg, not 3 × 5 kg
    }

    public function test_pack_choice_never_exceeds_stock(): void
    {
        $builder = app(FamilyPackBasketBuilder::class);
        $product = ['variants' => [['var_id' => 1, 'label' => '1kg', 'pack_text' => '1 kg', 'g' => 1000.0, 'ml' => null, 'basis' => 'label', 'price' => 60.0, 'regular' => 60.0, 'stock' => 5]]];
        $picked = $builder->choosePacks($product, 12700, 'g', null);
        $this->assertSame(5, $picked[0]['qty']);
    }

    public function test_litres_and_kilograms_are_not_confused_for_oils(): void
    {
        $this->pantry();
        $d = $this->plan();
        $req = $d['plan']['monthly_requirements'];
        $oilMl = array_sum(array_map(fn ($l) => $l['qty'] * ($l['pack_grams'] / 0.915), $this->linesByGroup($d['basket']['lines'], 'oil')));
        $this->assertEqualsWithDelta($req['cooking_oil_l'] * 1000, $oilMl, $req['cooking_oil_l'] * 1000 * 0.12);

        $gheeMl = array_sum(array_map(fn ($l) => $l['qty'] * ($l['pack_grams'] / 0.91), $this->linesByGroup($d['basket']['lines'], 'ghee')));
        $this->assertEqualsWithDelta($req['ghee_l'] * 1000, $gheeMl, $req['ghee_l'] * 1000 * 0.2);
    }

    public function test_ghee_sold_by_weight_is_converted_to_the_same_volume_plan(): void
    {
        $this->product('A2 Gir Cow Ghee Bilona', ['1 kg' => 40], 750, null, null, 'active', 'Oils & Ghee');
        $d = $this->plan();
        $line = $this->linesByGroup($d['basket']['lines'], 'ghee')[0];
        $this->assertSame(1000.0, (float) $line['pack_grams']);
        $litres = $d['plan']['monthly_requirements']['ghee_l'];
        $this->assertEqualsWithDelta($litres * 0.91, $line['qty'] * 1.0, 1.0);        // kg of ghee ≈ litres × density
    }

    // ── Prices ────────────────────────────────────────────────────────

    public function test_prices_use_the_shared_sale_price_rule(): void
    {
        $this->product('Toor Dal A', ['1kg' => 50], 100, 90);                // real sale
        $this->product('Moong Dal B', ['1kg' => 50], 100, 0);                // zero is not free
        $this->product('Chana Dal C', ['1kg' => 50], 100, 150);              // sale above regular is ignored
        $lines = collect($this->plan()['basket']['lines']);
        $this->assertEquals(90, $lines->firstWhere('name', 'Toor Dal A')['price']);
        $this->assertEquals(100, $lines->firstWhere('name', 'Moong Dal B')['price']);
        $this->assertEquals(100, $lines->firstWhere('name', 'Chana Dal C')['price']);
    }

    public function test_variation_price_beats_product_price(): void
    {
        $this->product('Toor Dal', ['500g' => ['stock' => 50, 'price' => 70, 'sale' => 65], '1kg' => ['stock' => 50, 'price' => 130]], 999);
        foreach ($this->plan()['basket']['lines'] as $l) {
            $this->assertContains((float) $l['price'], [65.0, 130.0]);
        }
    }

    // ── Validation ────────────────────────────────────────────────────

    public function test_missing_sex_for_an_adult_is_a_clear_422_not_a_crash(): void
    {
        $r = $this->postJson('/api/v1/family-packs/plan', ['members' => [['name' => 'A', 'age' => 30]]]);
        $r->assertStatus(422)->assertJsonValidationErrors(['members.0.sex']);
    }

    public function test_young_child_without_sex_is_accepted(): void
    {
        $this->pantry();
        $this->postJson('/api/v1/family-packs/plan', ['members' => [['name' => 'Kid', 'age' => 6]]])->assertOk();
    }

    public function test_invalid_ages_and_empty_family_are_rejected(): void
    {
        $this->postJson('/api/v1/family-packs/plan', ['members' => [['age' => 0, 'sex' => 'male']]])->assertStatus(422);
        $this->postJson('/api/v1/family-packs/plan', ['members' => [['age' => 130, 'sex' => 'male']]])->assertStatus(422);
        $this->postJson('/api/v1/family-packs/plan', ['members' => []])->assertStatus(422);
    }

    public function test_member_with_diabetes_and_bp_still_produces_a_valid_plan_with_notes(): void
    {
        $this->pantry();
        $d = $this->plan([['name' => 'A', 'age' => 58, 'sex' => 'male', 'activity' => 'moderate', 'diabetes' => true, 'bp' => true]]);
        $this->assertNotEmpty($d['plan']['safety_alerts']);
        $this->assertNotEmpty($d['basket']['lines']);
    }
}
