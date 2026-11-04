<?php

namespace App\Services\FamilyPack;

use App\Models\Product;
use App\Support\PackSize;
use App\Support\PriceRules;

/**
 * Prices and checks a list of {product_id, var_id, qty} using the same rules as the cart:
 * active product/variation, stock, and PriceRules for the unit price.
 * The Family Pack page never calculates money; it shows what this returns.
 */
class FamilyPackQuote
{
    public function __construct(private FamilyPackCatalog $catalog, private \App\Services\ProductNutritionService $nutrition)
    {
    }

    /**
     * @param array<int,array{product_id:int,var_id:?int,qty:int}> $items
     * @return array{lines: array, subtotal: float, all_ok: bool, errors: string[]}
     */
    public function quote(array $items): array
    {
        $ids = array_values(array_unique(array_map(fn ($i) => (int) $i['product_id'], $items)));
        $products = Product::query()
            ->with(['category:id,name', 'images', 'batches', 'variations' => fn ($q) => $q->where('status', 'active')->orderBy('id')->with('batches')])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $lines = [];
        $errors = [];
        $dens = config('family_pack.density_kg_per_l');

        foreach ($items as $i) {
            $pid = (int) $i['product_id'];
            $vid = isset($i['var_id']) && $i['var_id'] !== '' && $i['var_id'] !== null ? (int) $i['var_id'] : null;
            $qty = max(1, (int) $i['qty']);
            $product = $products->get($pid);
            $line = ['product_id' => $pid, 'var_id' => $vid, 'qty' => $qty, 'ok' => false, 'error' => null];

            if (! $product || $product->status !== 'active') {
                $line['error'] = 'This product is no longer available.';
                $line['name'] = $product?->name ?? "Product #{$pid}";
            } else {
                $line['name'] = $product->name;
                $variation = null;
                $active = $product->variations;
                if ($vid !== null) {
                    $variation = $active->firstWhere('id', $vid);
                    if (! $variation) {
                        $line['error'] = 'This pack size is no longer available.';
                    }
                } elseif ($active->count() === 1) {
                    $variation = $active->first();
                } elseif ($active->count() > 1) {
                    $line['error'] = 'Please choose a pack size.';
                }

                if ($line['error'] === null) {
                    $stock = $variation ? (int) $variation->available_stock : (int) $product->available_stock;
                    $unit = PriceRules::forItem($product, $variation);
                    $pack = PackSize::parse($variation?->attr_val, $variation?->net_qty ?? $product->net_qty, $variation?->net_unit ?? $product->net_unit);
                    $class = $this->catalog->classify($product);
                    $nutrition = $this->nutrition->nutritionForBasket(is_array($product->nutritional_info) ? $product->nutritional_info : null, $class['food_group'], $class['food_subgroup']);
                    $density = $dens[$class['food_group']] ?? null;
                    $packG = $pack ? ($pack['grams'] ?? ($pack['ml'] !== null && $density ? $pack['ml'] * $density : null)) : null;

                    if ($stock <= 0) {
                        $line['error'] = 'Out of stock.';
                    } elseif ($qty > $stock) {
                        $line['error'] = "Only {$stock} available.";
                    } elseif ($unit <= 0) {
                        $line['error'] = 'This product has no valid price.';
                    } else {
                        $img = $product->images->first();
                        $line = array_merge($line, [
                            'ok' => true,
                            'var_id' => $variation?->id,
                            'price' => $unit,
                            'regular_price' => (float) ($variation?->price ?? $product->price),
                            'line_total' => PriceRules::lineTotal($unit, $qty),
                            'max_qty' => $stock,
                            'package_size' => $variation?->attr_val ?: ($pack['text'] ?? ''),
                            'pack_grams' => $packG !== null ? round($packG, 1) : null,
                            'food_group' => $class['food_group'],
                            'group_key' => $this->groupKey($class),
                            'nutrition' => $nutrition,
                            'image' => $img ? ($img->image_url ?: asset('storage/' . ltrim((string) $img->image, '/'))) : null,
                        ]);
                    }
                }
            }
            if (! $line['ok']) {
                $errors[] = "{$line['name']}: {$line['error']}";
            }
            $lines[] = $line;
        }

        $subtotal = round(array_sum(array_map(fn ($l) => $l['ok'] ? $l['line_total'] : 0, $lines)), 2);

        return ['lines' => $lines, 'subtotal' => $subtotal, 'all_ok' => ! $errors, 'errors' => $errors];
    }

    private function groupKey(array $class): string
    {
        return match ($class['food_group']) {
            'grains_cereals' => 'cereals',
            'pulses_legumes' => 'pulses',
            'nuts_seeds' => 'nuts',
            'cooking_oils' => 'oil',
            'sweeteners' => 'sweetener',
            default => $class['food_group'],
        };
    }
}
