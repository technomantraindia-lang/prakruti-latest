<?php

namespace Tests\Feature\FamilyPack;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FamilyPackFixtures;
use Tests\TestCase;

/**
 * Admin creates a product → nutrition is processed and stored → product API exposes it →
 * Family Pack discovers it → it enters the basket → customer edits, saves, adds to cart → totals reconcile.
 * No payment is triggered.
 */
class FamilyPackEndToEndTest extends TestCase
{
    use RefreshDatabase;
    use FamilyPackFixtures;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'Super Admin'], ['guard_name' => 'web']);

        return User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password123'), 'role_id' => $role->id, 'status' => 'active']);
    }

    private function createViaAdmin(array $overrides = []): Product
    {
        Storage::fake('public');
        $category = $this->category('Cereal & Pulses');
        $this->actingAs($this->admin())->post(route('admin.products.store'), array_merge([
            'name' => 'Urad Dal Chilka / Black Gram Split',
            'sku' => 'E2E-URAD-1',
            'category_id' => $category->id,
            'price' => 130,
            'status' => 'active',
            'packages' => [
                ['label' => '500g', 'price' => 70, 'stock_qty' => 40, 'status' => 'active'],
                ['label' => '1kg', 'price' => 130, 'sale_price' => 120, 'stock_qty' => 40, 'status' => 'active'],
            ],
            'gallery' => [UploadedFile::fake()->image('pack.jpg')],
            'nutrition_source' => 'unverified',   // what the admin form submits by default
            'nutritional_info' => "Serving Size: 100g\nEnergy: 347 kcal\nProtein: 25g\nCarbohydrates: 59g\nTotal Fat: 1.6g\nDietary Fibre: 18g\nSodium: 38mg",
        ], $overrides))->assertSessionHasNoErrors();

        return Product::where('sku', 'E2E-URAD-1')->firstOrFail();
    }

    public function test_admin_creates_product_and_nutrition_is_stored_honestly(): void
    {
        $p = $this->createViaAdmin();
        $info = $p->nutritional_info;

        $this->assertSame('label_unreviewed', $info['source']);          // not "verified" just because it parsed
        $this->assertFalse($info['verified']);
        $this->assertSame('pulses_legumes', $info['food_group']);
        $this->assertEquals(25, $info['per_100g']['protein_g']);
        $this->assertEquals(18, $info['per_100g']['fiber_g']);
        $this->assertCount(2, $p->variations);
    }

    public function test_pasted_label_in_per_serving_form_is_normalised_through_the_admin_form(): void
    {
        $p = $this->createViaAdmin(['sku' => 'E2E-URAD-1', 'nutritional_info' => "Serving Size: 30g\nEnergy: 104 kcal\nProtein: 7.5g\nCarbohydrates: 17.7g\nTotal Fat: 0.5g\nDietary Fibre: 5.4g"]);
        $this->assertEquals(25, $p->nutritional_info['per_100g']['protein_g']);
        $this->assertEquals(59, $p->nutritional_info['per_100g']['carbs_g']);
    }

    public function test_admin_can_explicitly_mark_values_verified_and_override_food_group(): void
    {
        $p = $this->createViaAdmin(['nutrition_source' => 'packaging_verified', 'name' => 'Mystery Legume Mix', 'food_group' => 'pulses_legumes']);
        $this->assertSame('packaging_verified', $p->nutritional_info['source']);
        $this->assertTrue($p->nutritional_info['verified']);
        $this->assertSame('pulses_legumes', $p->nutritional_info['food_group']);
        $this->assertTrue($p->nutritional_info['manual_group']);
    }

    public function test_product_without_label_is_stored_as_unverified_with_no_numbers(): void
    {
        $p = $this->createViaAdmin(['nutritional_info' => '']);
        $this->assertSame('unverified', $p->nutritional_info['source']);
        $this->assertNull($p->nutritional_info['per_100g']['protein_g']);
    }

    public function test_product_api_exposes_the_new_product_with_its_nutrition_and_packs(): void
    {
        $this->createViaAdmin();
        $r = $this->getJson('/api/v1/products?per_page=100')->assertOk();
        $row = collect($r->json('data'))->firstWhere('name', 'Urad Dal Chilka / Black Gram Split');

        $this->assertNotNull($row);
        $this->assertSame('label_unreviewed', $row['nutritional_info']['source']);
        $this->assertCount(2, $row['variations']);
        $this->assertEquals(120, collect($row['variations'])->firstWhere('label', '1kg')['sale_price']);
    }

    public function test_the_full_journey_from_admin_product_to_cart(): void
    {
        $this->pantry();
        $urad = $this->createViaAdmin();
        [$customer, $headers] = $this->customer();

        // Family Pack discovers and uses the new product without any code change
        $plan = $this->postJson('/api/v1/family-packs/plan', ['members' => $this->fatherMotherSonGrandmother()])->assertOk()->json('data');
        $uradLines = array_values(array_filter($plan['basket']['lines'], fn ($l) => $l['product_id'] === $urad->id));
        $this->assertNotEmpty($uradLines, 'new admin product should be in the basket');
        $this->assertSame('label', $uradLines[0]['nutrition']['basis']);      // uses its own label values
        $this->assertEqualsWithDelta(11.22, $this->suppliedKg($plan['basket']['lines'], 'pulses'), 11.22 * 0.10);
        // sale price of the 1 kg pack (120) is used by the basket
        $oneKg = collect($uradLines)->firstWhere('package_size', '1kg');
        if ($oneKg) {
            $this->assertEquals(120, $oneKg['price']);
        }

        // Customer adjusts: one more pack of the first line, drops the last line
        $lines = $plan['basket']['lines'];
        $lines[0]['qty'] += 1;
        array_pop($lines);
        $items = array_map(fn ($l) => ['product_id' => $l['product_id'], 'var_id' => $l['var_id'], 'qty' => $l['qty']], $lines);

        // Authoritative quote for the edited basket, with coverage
        $quote = $this->postJson('/api/v1/family-packs/quote', ['items' => $items, 'monthly_nutrients' => $plan['plan']['monthly_nutrients']])->assertOk();
        $this->assertTrue($quote->json('data.all_ok'));
        $expected = array_sum(array_map(fn ($l) => $l['price'] * $l['qty'], $lines));
        $this->assertEqualsWithDelta($expected, $quote->json('data.subtotal'), 0.001);
        $this->assertNotNull($quote->json('data.coverage.nutrients.protein_g'));

        // Save, then add the complete basket to the cart
        $this->postJson('/api/v1/family-packs', [
            'profile' => ['members' => $this->fatherMotherSonGrandmother(), 'household' => []],
            'recommendations' => array_map(fn ($l) => ['product_id' => $l['product_id'], 'var_id' => $l['var_id'], 'name' => $l['name'], 'qty' => $l['qty'], 'price' => $l['price']], $lines),
            'monthly_total' => $expected,
        ], $headers)->assertOk();

        $this->postJson('/api/v1/family-packs/add-to-cart', ['items' => $items], $headers)->assertOk();

        $cart = Cart::where('user_id', $customer->id)->firstOrFail();
        $this->assertSame(count($items), CartItem::where('cart_id', $cart->id)->count());
        $this->assertEquals(array_sum(array_column($items, 'qty')), CartItem::where('cart_id', $cart->id)->sum('qty'));
        $cartSubtotal = $this->getJson('/api/v1/cart', $headers)->json('data.subtotal');
        $this->assertEqualsWithDelta($expected, $cartSubtotal, 0.001, 'Family Pack total must equal the cart subtotal');

        // Saved pack restores the edited quantities
        $saved = $this->getJson('/api/v1/family-packs/latest', $headers)->json('data');
        $this->assertSame($lines[0]['qty'], $saved['recommendations'][0]['qty']);
    }

    public function test_a_product_made_inactive_by_admin_leaves_the_basket(): void
    {
        $this->pantry();
        $urad = $this->createViaAdmin();
        $in = array_column($this->postJson('/api/v1/family-packs/plan', ['members' => $this->fatherMotherSonGrandmother()])->json('data.basket.lines'), 'product_id');
        $this->assertContains($urad->id, $in);

        $urad->update(['status' => 'inactive']);
        $out = array_column($this->postJson('/api/v1/family-packs/plan', ['members' => $this->fatherMotherSonGrandmother()])->json('data.basket.lines'), 'product_id');
        $this->assertNotContains($urad->id, $out);
    }

    public function test_stock_change_is_reflected_in_the_next_plan(): void
    {
        $this->pantry();
        $urad = $this->createViaAdmin();
        $urad->variations()->update(['stock_qty' => 0]);
        $out = array_column($this->postJson('/api/v1/family-packs/plan', ['members' => $this->fatherMotherSonGrandmother()])->json('data.basket.lines'), 'product_id');
        $this->assertNotContains($urad->id, $out);
    }
}
