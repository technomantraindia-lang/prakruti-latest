<?php

namespace App\Services\FamilyPack;

use App\Models\Product;
use App\Services\ProductNutritionService;
use App\Support\PackSize;
use App\Support\PriceRules;

/**
 * Reads the live product tables and returns every product the Family Pack could use.
 * Nothing here is hard-coded to a product name: a product the admin adds tomorrow is picked up automatically
 * as long as it is active, in a food group, has a known pack size, a price and stock.
 */
class FamilyPackCatalog
{
    /** Groups the basket can fill automatically. */
    public const STAPLE_GROUPS = ['pulses_legumes', 'grains_cereals', 'nuts_seeds', 'cooking_oils', 'ghee', 'sweeteners', 'spices', 'salt'];

    /** Subgroups that are food-group members but never auto-added (flours of pulses, pseudo-cereals). They can still be added by hand. */
    private const MANUAL_ONLY_SUBGROUPS = ['pulse_flour', 'other_grains'];

    public function __construct(private ProductNutritionService $nutrition)
    {
    }

    /**
     * @return array{products: array<int,array>, data_quality: string[]}
     */
    public function load(): array
    {
        $products = [];
        $quality = [];

        Product::query()
            ->where('status', 'active')
            ->with(['category:id,name', 'images', 'batches', 'variations' => fn ($q) => $q->where('status', 'active')->orderBy('id')->with('batches')])
            ->orderBy('id')
            ->chunkById(200, function ($chunk) use (&$products, &$quality) {
                foreach ($chunk as $product) {
                    $item = $this->toCandidate($product, $quality);
                    if ($item !== null) {
                        $products[$item['product_id']] = $item;
                    }
                }
            });

        return ['products' => $products, 'data_quality' => array_values(array_unique($quality))];
    }

    /** Classification used everywhere: an admin override stored with the nutrition record wins over name matching. */
    public function classify(Product $product): array
    {
        $info = is_array($product->nutritional_info) ? $product->nutritional_info : [];
        if (! empty($info['manual_group']) && ! empty($info['food_group'])) {
            return [
                'food_group' => $info['food_group'],
                'food_subgroup' => $info['food_subgroup'] ?? 'general',
                'group_label' => $info['group_label'] ?? (ProductNutritionService::FOOD_GROUPS[$info['food_group']] ?? 'Other'),
            ];
        }

        return $this->nutrition->classifyFoodGroup((string) $product->name, $product->category?->name);
    }

    private function toCandidate(Product $product, array &$quality): ?array
    {
        $class = $this->classify($product);
        if (! in_array($class['food_group'], self::STAPLE_GROUPS, true)
            || in_array($class['food_subgroup'], self::MANUAL_ONLY_SUBGROUPS, true)) {
            return null;
        }

        $info = is_array($product->nutritional_info) ? $product->nutritional_info : null;
        $nutrition = $this->nutrition->nutritionForBasket($info, $class['food_group'], $class['food_subgroup']);

        $variants = [];
        $activeVariations = $product->variations;
        if ($activeVariations->isNotEmpty()) {
            foreach ($activeVariations as $v) {
                $pack = PackSize::parse($v->attr_val, $v->net_qty, $v->net_unit);
                $variants[] = $this->variant($product, $v, $pack, (string) ($v->attr_val ?: ''), $v->available_stock, $quality);
            }
        } else {
            $pack = PackSize::parse(null, $product->net_qty, $product->net_unit);
            $variants[] = $this->variant($product, null, $pack, '', $product->available_stock, $quality);
        }

        $variants = array_values(array_filter($variants));
        if (! $variants) {
            return null;
        }

        $img = $product->images->first();

        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'category' => $product->category?->name,
            'image' => $img ? ($img->image_url ?: asset('storage/' . ltrim((string) $img->image, '/'))) : null,
            'group' => $class['food_group'],
            'sub' => $class['food_subgroup'],
            'group_label' => $class['group_label'],
            'nutrition' => $nutrition,
            'variants' => $variants,
        ];
    }

    private function variant(Product $product, $variation, ?array $pack, string $label, int $stock, array &$quality): ?array
    {
        if ($pack === null) {
            $quality[] = "{$product->name}: pack size is not set, so it cannot be used in a basket (add the pack contents in admin).";

            return null;
        }
        $price = PriceRules::forItem($product, $variation);
        if ($price <= 0) {
            $quality[] = "{$product->name}: no valid price, skipped.";

            return null;
        }
        if ($stock <= 0) {
            return null; // out of stock is normal, not a data problem
        }
        if ($pack['basis'] === 'inferred') {
            $quality[] = "{$product->name}: pack size \"{$label}\" has no unit and was read as {$pack['text']}. Please confirm it in admin.";
        }

        return [
            'var_id' => $variation?->id,
            'label' => $label !== '' ? $label : $pack['text'],
            'pack_text' => $pack['text'],
            'g' => $pack['grams'],
            'ml' => $pack['ml'],
            'basis' => $pack['basis'],
            'price' => $price,
            'regular' => (float) ($variation?->price ?? $product->price),
            'stock' => $stock,
        ];
    }
}
