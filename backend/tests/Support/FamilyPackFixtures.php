<?php

namespace Tests\Support;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Role;
use App\Models\User;
use App\Services\ProductNutritionService;

/**
 * Deterministic catalogue builders for Family Pack tests. No test depends on the live site or the dev database.
 */
trait FamilyPackFixtures
{
    private int $fixtureSeq = 0;

    protected function category(string $name = 'Cereal & Pulses'): Category
    {
        return Category::firstOrCreate(['slug' => str($name)->slug()->toString()], ['name' => $name, 'status' => 'active']);
    }

    /**
     * @param array<string,array{price?:float,sale?:?float,stock?:int,net?:array}|int> $packs  label => stock (int) or details
     * @param string|null $label   optional nutrition label text typed by an admin
     */
    protected function product(string $name, array $packs, float $price = 100, ?float $sale = null, ?string $label = null, string $status = 'active', string $category = 'Cereal & Pulses'): Product
    {
        $this->fixtureSeq++;
        $nutrition = $label !== null ? app(ProductNutritionService::class)->buildStructuredPayload($label, $name) : null;

        $product = Product::create([
            'name' => $name,
            'slug' => str($name)->slug() . '-' . $this->fixtureSeq,
            'sku' => 'FX-' . str_pad((string) $this->fixtureSeq, 4, '0', STR_PAD_LEFT),
            'category_id' => $this->category($category)->id,
            'price' => $price,
            'sale_price' => $sale,
            'stock_qty' => 0,
            'status' => $status,
            'nutritional_info' => $nutrition,
        ]);

        foreach ($packs as $packLabel => $spec) {
            $d = is_array($spec) ? $spec : ['stock' => $spec];
            ProductVariation::create([
                'product_id' => $product->id,
                'attr_val' => (string) $packLabel,
                'sku' => $product->sku . '-' . str_replace(' ', '', (string) $packLabel),
                'price' => $d['price'] ?? $price,
                'sale_price' => $d['sale'] ?? $sale,
                'stock_qty' => $d['stock'] ?? 0,
                'net_qty' => $d['net'][0] ?? null,
                'net_unit' => $d['net'][1] ?? null,
                'status' => 'active',
            ]);
        }

        return $product->fresh(['variations']);
    }

    /** A realistic Indian pantry: several pack sizes, plenty of stock. Prices are fixtures, not live prices. */
    protected function pantry(int $stock = 60): array
    {
        $p = [];
        $p['wheat'] = $this->product('Wheat Flour (Lokwan)', ['1kg' => $stock, '5kg' => $stock], 60, null, "Per 100g\nEnergy 346 kcal\nProtein 12g\nCarbohydrate 71g\nFat 1.5g\nFibre 12g");
        $p['multigrain'] = $this->product('Multi Grain Flour', ['1kg' => $stock], 100);
        $p['sona'] = $this->product('Sona Masoori - White Rice', ['1kg' => $stock, '5kg' => $stock], 90);
        $p['brown'] = $this->product('Basmati Rice Brown', ['1kg' => $stock], 150);
        $p['ragi'] = $this->product('Ragi Whole/Finger Millet', ['500g' => $stock, '1kg' => $stock], 80);
        $p['jowar'] = $this->product('Jowar Atta/Sorghum Flour', ['500g' => $stock, '1kg' => $stock], 75);
        $p['bajra'] = $this->product('Bajra Whole / Pearl Millet', ['1kg' => $stock], 75);
        $p['toor'] = $this->product('Toor Dal/Arhar Dal', ['500g' => $stock, '1kg' => $stock], 130, null, "Per 100g\nEnergy 345 kcal\nProtein 22g\nCarbohydrate 63g\nFat 1g\nFibre 15g");
        $p['moong'] = $this->product('Green Gram Whole (Moong)', ['500g' => $stock, '1kg' => $stock], 120);
        $p['chana'] = $this->product('Channa Dal/Split Bengal Gram', ['500g' => $stock, '1kg' => $stock], 110);
        $p['masoor'] = $this->product('Masoor Dal/Split Red Lentil', ['500g' => $stock, '1kg' => $stock], 120, 108);
        $p['rajma'] = $this->product('Rajma', ['500g' => $stock, '1kg' => $stock], 150);
        $p['chia'] = $this->product('Chia Seed', ['250g' => $stock], 180);
        $p['flax'] = $this->product('Flax Seed', ['250g' => $stock], 110);
        $p['pumpkin'] = $this->product('Pumpkin Seeds', ['250g' => $stock], 170);
        $p['sunflower'] = $this->product('Sunflower Seed', ['250g' => $stock], 120);
        $p['oil'] = $this->product('Groundnut Oil (Cold Pressed)', ['1 L' => $stock, '500ml' => $stock], 220, null, null, 'active', 'Oils & Ghee');
        $p['sesame'] = $this->product('Black Sesame Oil (Cold Pressed)', ['1 L' => $stock], 290, null, null, 'active', 'Oils & Ghee');
        $p['ghee'] = $this->product('A2 Gir Cow Ghee Bilona', ['500ml' => $stock, '1 L' => $stock], 750, 675, null, 'active', 'Oils & Ghee');
        $p['jaggery'] = $this->product('Jaggery Powder', ['1kg' => $stock], 110, null, null, 'active', 'Sweeteners');
        $p['sugar'] = $this->product('Off White Sugar Light', ['1kg' => $stock], 90, null, null, 'active', 'Sweeteners');
        $p['salt'] = $this->product('Pink Rock Salt / Sendha Namak', ['1kg' => $stock], 55, null, null, 'active', 'Indian Spices');
        $p['turmeric'] = $this->product('Turmeric Powder', ['250g' => $stock, '500g' => $stock], 110, 99, null, 'active', 'Indian Spices');
        $p['chilli'] = $this->product('Red Chilli Powder', ['250g' => $stock, '500g' => $stock], 110, null, null, 'active', 'Indian Spices');
        $p['coriander'] = $this->product('Dhania Powder/Coriander Powder', ['250g' => $stock, '500g' => $stock], 110, null, null, 'active', 'Indian Spices');
        $p['cumin'] = $this->product('Cumin Powder/Jeera Powder', ['250g' => $stock, '500g' => $stock], 110, null, null, 'active', 'Indian Spices');

        return $p;
    }

    /** The four-member household used throughout the project documentation. */
    protected function fatherMotherSonGrandmother(): array
    {
        return [
            ['id' => 'f', 'name' => 'Father', 'age' => 45, 'sex' => 'male', 'activity' => 'high'],
            ['id' => 'm', 'name' => 'Mother', 'age' => 40, 'sex' => 'female', 'activity' => 'sedentary'],
            ['id' => 's', 'name' => 'Son', 'age' => 12, 'sex' => 'male', 'activity' => 'moderate'],
            ['id' => 'g', 'name' => 'Grandmother', 'age' => 70, 'sex' => 'female', 'activity' => 'sedentary', 'bp' => true],
        ];
    }

    protected function customer(): array
    {
        $role = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);
        $user = User::create(['name' => 'Test Customer', 'email' => 'cust' . uniqid() . '@example.com', 'password' => bcrypt('secret123'), 'role_id' => $role->id]);

        return [$user, ['Authorization' => 'Bearer ' . $user->createToken('t')->plainTextToken]];
    }

    protected function linesByGroup(array $lines, string $groupKey): array
    {
        return array_values(array_filter($lines, fn ($l) => $l['group_key'] === $groupKey));
    }

    protected function suppliedKg(array $lines, string $groupKey): float
    {
        return array_sum(array_map(fn ($l) => $l['qty'] * $l['pack_grams'], $this->linesByGroup($lines, $groupKey))) / 1000;
    }
}
