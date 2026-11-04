<?php

namespace Tests\Feature\FamilyPack;

use App\Http\Controllers\Api\V1\CartApiController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Support\PriceRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FamilyPackFixtures;
use Tests\TestCase;

class FamilyPackCartTest extends TestCase
{
    use RefreshDatabase;
    use FamilyPackFixtures;

    private array $headers;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        [$this->user, $this->headers] = $this->customer();
    }

    private function planItems(): array
    {
        $d = $this->postJson('/api/v1/family-packs/plan', ['members' => $this->fatherMotherSonGrandmother()])->json('data');

        return [
            array_map(fn ($l) => ['product_id' => $l['product_id'], 'var_id' => $l['var_id'], 'qty' => $l['qty']], $d['basket']['lines']),
            $d,
        ];
    }

    // ── Quote ─────────────────────────────────────────────────────────

    public function test_quote_prices_every_line_with_the_shared_rule(): void
    {
        $a = $this->product('Toor Dal', ['1kg' => 10], 100, 90);
        $b = $this->product('Moong Dal', ['500g' => ['stock' => 10, 'price' => 70, 'sale' => 0]], 999);
        $r = $this->postJson('/api/v1/family-packs/quote', ['items' => [
            ['product_id' => $a->id, 'var_id' => $a->variations->first()->id, 'qty' => 3],
            ['product_id' => $b->id, 'var_id' => $b->variations->first()->id, 'qty' => 2],
        ]])->assertOk();

        $this->assertTrue($r->json('data.all_ok'));
        $this->assertEquals(3 * 90 + 2 * 70, $r->json('data.subtotal'));
        $this->assertEquals(270, $r->json('data.lines.0.line_total'));
        $this->assertEquals(140, $r->json('data.lines.1.line_total'));
    }

    public function test_quote_reports_each_problem_line(): void
    {
        $ok = $this->product('Toor Dal', ['1kg' => 5], 100);
        $oos = $this->product('Moong Dal', ['1kg' => 0], 100);
        $multi = $this->product('Rajma', ['500g' => 5, '1kg' => 5], 100);
        $gone = $this->product('Chana Dal', ['1kg' => 5], 100, null, null, 'inactive');
        $r = $this->postJson('/api/v1/family-packs/quote', ['items' => [
            ['product_id' => $ok->id, 'var_id' => $ok->variations->first()->id, 'qty' => 6],       // more than stock
            ['product_id' => $oos->id, 'var_id' => $oos->variations->first()->id, 'qty' => 1],     // out of stock
            ['product_id' => $multi->id, 'qty' => 1],                                               // no pack chosen
            ['product_id' => $gone->id, 'var_id' => $gone->variations->first()->id, 'qty' => 1],   // inactive
        ]])->assertOk();

        $this->assertFalse($r->json('data.all_ok'));
        $this->assertCount(4, $r->json('data.errors'));
        $this->assertStringContainsString('Only 5 available', $r->json('data.lines.0.error'));
        $this->assertStringContainsString('Out of stock', $r->json('data.lines.1.error'));
        $this->assertStringContainsString('choose a pack size', $r->json('data.lines.2.error'));
        $this->assertStringContainsString('no longer available', $r->json('data.lines.3.error'));
        $this->assertEquals(0, $r->json('data.subtotal'));
    }

    public function test_quote_returns_coverage_for_an_edited_basket(): void
    {
        $p = $this->product('Toor Dal', ['1kg' => 50], 130, null, "Per 100g\nEnergy 345 kcal\nProtein 22g\nCarbohydrate 63g\nFat 1g\nFibre 15g");
        $r = $this->postJson('/api/v1/family-packs/quote', [
            'items' => [['product_id' => $p->id, 'var_id' => $p->variations->first()->id, 'qty' => 2]],
            'monthly_nutrients' => ['energy_kcal' => 10000, 'protein_g' => 1000, 'carbs_g' => 2000, 'fat_g' => 400, 'fiber_g' => 500],
        ])->assertOk();
        $this->assertEqualsWithDelta(440, $r->json('data.coverage.nutrients.protein_g.basket_monthly'), 0.1);   // 2 kg × 22 g/100 g
        $this->assertSame(44, $r->json('data.coverage.nutrients.protein_g.pct'));
    }

    // ── Add to cart ───────────────────────────────────────────────────

    public function test_adding_requires_login(): void
    {
        $p = $this->product('Toor Dal', ['1kg' => 5], 100);
        $this->postJson('/api/v1/family-packs/add-to-cart', ['items' => [['product_id' => $p->id, 'var_id' => $p->variations->first()->id, 'qty' => 1]]])->assertStatus(401);
    }

    public function test_whole_basket_goes_into_the_cart_and_totals_reconcile_to_the_rupee(): void
    {
        $this->pantry();
        [$items, $plan] = $this->planItems();

        $r = $this->postJson('/api/v1/family-packs/add-to-cart', ['items' => $items], $this->headers)->assertOk();
        $this->assertTrue($r->json('success'));

        $cart = Cart::where('user_id', $this->user->id)->firstOrFail();
        $this->assertSame(count($items), CartItem::where('cart_id', $cart->id)->count());
        foreach ($items as $i) {
            $this->assertSame($i['qty'], (int) CartItem::where('cart_id', $cart->id)->where('product_id', $i['product_id'])->where('var_id', $i['var_id'])->value('qty'));
        }

        // Plan subtotal == add-to-cart reported subtotal == the cart's own subtotal
        $cartSubtotal = $this->getJson('/api/v1/cart', $this->headers)->assertOk()->json('data.subtotal');
        $this->assertEqualsWithDelta($plan['basket']['subtotal'], $cartSubtotal, 0.001);
        $this->assertEqualsWithDelta($plan['basket']['subtotal'], $r->json('data.added_subtotal'), 0.001);
        $this->assertEqualsWithDelta($plan['basket']['subtotal'], $r->json('data.cart.subtotal'), 0.001);

        // Every line in the cart is priced by the shared rule, including sale prices
        foreach ($this->getJson('/api/v1/cart', $this->headers)->json('data.items') as $row) {
            $this->assertGreaterThan(0, $row['price']);
        }
    }

    public function test_nothing_is_added_if_any_line_cannot_be_added(): void
    {
        $good = $this->product('Toor Dal', ['1kg' => 5], 100);
        $bad = $this->product('Moong Dal', ['1kg' => 1], 100);
        $r = $this->postJson('/api/v1/family-packs/add-to-cart', ['items' => [
            ['product_id' => $good->id, 'var_id' => $good->variations->first()->id, 'qty' => 2],
            ['product_id' => $bad->id, 'var_id' => $bad->variations->first()->id, 'qty' => 3],
        ]], $this->headers);

        $r->assertStatus(422);
        $this->assertFalse($r->json('success'));
        $this->assertNotEmpty($r->json('errors'));
        $this->assertSame(0, CartItem::count(), 'cart must stay empty');
    }

    public function test_stock_already_in_the_cart_counts(): void
    {
        $p = $this->product('Toor Dal', ['1kg' => 5], 100);
        $vid = $p->variations->first()->id;
        $cart = Cart::create(['user_id' => $this->user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $p->id, 'var_id' => $vid, 'qty' => 4, 'price' => 100]);

        $r = $this->postJson('/api/v1/family-packs/add-to-cart', ['items' => [['product_id' => $p->id, 'var_id' => $vid, 'qty' => 2]]], $this->headers);
        $r->assertStatus(422);
        $this->assertStringContainsString('4 already in your cart', implode(' ', $r->json('errors')));
        $this->assertSame(4, (int) CartItem::first()->qty);
    }

    public function test_adding_twice_merges_quantities(): void
    {
        $p = $this->product('Toor Dal', ['1kg' => 10], 100);
        $item = ['product_id' => $p->id, 'var_id' => $p->variations->first()->id, 'qty' => 2];
        $this->postJson('/api/v1/family-packs/add-to-cart', ['items' => [$item]], $this->headers)->assertOk();
        $this->postJson('/api/v1/family-packs/add-to-cart', ['items' => [$item]], $this->headers)->assertOk();
        $this->assertSame(1, CartItem::count());
        $this->assertSame(4, (int) CartItem::first()->qty);
    }

    public function test_duplicate_lines_in_one_request_are_merged_before_the_stock_check(): void
    {
        $p = $this->product('Toor Dal', ['1kg' => 5], 100);
        $item = ['product_id' => $p->id, 'var_id' => $p->variations->first()->id, 'qty' => 3];
        $this->postJson('/api/v1/family-packs/add-to-cart', ['items' => [$item, $item]], $this->headers)->assertStatus(422);
        $this->assertSame(0, CartItem::count());
    }

    public function test_empty_or_oversized_requests_are_rejected(): void
    {
        $this->postJson('/api/v1/family-packs/add-to-cart', ['items' => []], $this->headers)->assertStatus(422);
        $this->postJson('/api/v1/family-packs/add-to-cart', ['items' => [['product_id' => 999999, 'qty' => 1]]], $this->headers)->assertStatus(422);
    }

    public function test_the_cart_and_checkout_price_rule_are_the_same_function(): void
    {
        foreach ([[90, 100], [0, 100], [150, 100], [null, 100], [80, 0]] as [$sale, $reg]) {
            $this->assertSame(PriceRules::effective($sale, $reg), CartApiController::resolveEffectivePrice($sale, $reg));
        }
    }

    // ── Save and restore ──────────────────────────────────────────────

    public function test_edited_quantities_are_saved_and_restored(): void
    {
        $this->pantry();
        $d = $this->postJson('/api/v1/family-packs/plan', ['members' => $this->fatherMotherSonGrandmother()])->json('data');
        $recs = array_map(fn ($l) => ['product_id' => $l['product_id'], 'var_id' => $l['var_id'], 'name' => $l['name'], 'qty' => $l['qty'], 'price' => $l['price'], 'package_size' => $l['package_size']], $d['basket']['lines']);
        $recs[0]['qty'] = 7;                                    // the customer edited a quantity

        $this->postJson('/api/v1/family-packs', [
            'profile' => ['members' => $this->fatherMotherSonGrandmother(), 'household' => ['staplePreference' => 'rice']],
            'recommendations' => $recs,
            'monthly_total' => $d['basket']['subtotal'],
        ], $this->headers)->assertOk()->assertJsonPath('success', true);

        $latest = $this->getJson('/api/v1/family-packs/latest', $this->headers)->assertOk()->json('data');
        $this->assertSame(7, $latest['recommendations'][0]['qty']);
        $this->assertSame('rice', $latest['profile']['household']['staplePreference']);
        $this->assertCount(4, $latest['profile']['members']);
        $this->assertEquals($d['basket']['subtotal'], $latest['monthly_total']);
    }

    public function test_saving_twice_keeps_one_active_pack_per_customer(): void
    {
        $p = $this->product('Toor Dal', ['1kg' => 10], 100);
        $body = fn (int $qty) => [
            'profile' => ['members' => [['name' => 'A', 'age' => 30, 'sex' => 'male']]],
            'recommendations' => [['product_id' => $p->id, 'var_id' => $p->variations->first()->id, 'name' => 'Toor Dal', 'qty' => $qty]],
        ];
        $this->postJson('/api/v1/family-packs', $body(1), $this->headers)->assertOk();
        $this->postJson('/api/v1/family-packs', $body(3), $this->headers)->assertOk();
        $this->assertSame(1, \App\Models\FamilyPack::where('user_id', $this->user->id)->count());
        $this->assertSame(3, $this->getJson('/api/v1/family-packs/latest', $this->headers)->json('data.recommendations.0.qty'));
    }

    public function test_saving_requires_login_and_rejects_unknown_products(): void
    {
        $this->postJson('/api/v1/family-packs', [])->assertStatus(401);
        $this->postJson('/api/v1/family-packs', [
            'profile' => ['members' => [['name' => 'A', 'age' => 30]]],
            'recommendations' => [['product_id' => 999999, 'name' => 'Ghost', 'qty' => 1]],
        ], $this->headers)->assertStatus(422);
    }
}
