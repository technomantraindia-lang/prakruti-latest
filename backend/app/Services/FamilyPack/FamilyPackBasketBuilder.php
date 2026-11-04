<?php

namespace App\Services\FamilyPack;

use App\Support\PriceRules;

/**
 * Turns monthly food-group needs into a list of real products and pack counts.
 *
 * Method (also shown to customers in plain words):
 *  1. Each food group has a need in grams (or ml for oils) from the nutrition engine.
 *  2. Every eligible product in that group gets a weight from its protein and fibre density (label values if present,
 *     otherwise a clearly labelled food-group estimate). Higher-fibre products get a little more of the share.
 *  3. Shares are filled "water-filling" style: a product that is limited by stock or by a sensible per-person cap
 *     passes its unfilled share to the others.
 *  4. Products that would get only a token amount are dropped, and only a limited number of products per group are used
 *     so the basket stays practical.
 *  5. Real pack sizes are chosen to land close to the need, never above stock.
 * Anything that cannot be filled is reported as a shortfall. Nothing is silently invented.
 */
class FamilyPackBasketBuilder
{
    private const EPS = 1e-9;

    /**
     * @param array $catalog   result of FamilyPackCatalog::load()
     * @param array $req       engine monthly_requirements
     * @param array $context   ['members' => int, 'has_diabetes' => bool, 'household' => array]
     */
    public function build(array $catalog, array $req, array $context): array
    {
        $cfg = config('family_pack');
        $products = $catalog['products'];
        $members = max(1, (int) ($context['members'] ?? 1));
        $hasDiabetes = (bool) ($context['has_diabetes'] ?? false);
        $household = $context['household'] ?? [];
        $dens = $cfg['density_kg_per_l'];

        $byGroup = fn (string $g, ?array $subs = null) => array_values(array_filter($products, fn ($p) => $p['group'] === $g && ($subs === null || in_array($p['sub'], $subs, true))));

        $allocations = [];   // product_id => ['amount' => float, 'need_group' => string]
        $groups = [];        // group summaries

        // ── Cereals: millets / wheat flours / rice ──────────────────────────
        $needG = $req['cereals_kg'] * 1000;
        $milletNeed = $needG * $req['millet_share'];
        $rest = $needG - $milletNeed;
        $buckets = [
            'millets' => ['label' => 'millets', 'need' => $milletNeed, 'products' => $byGroup('grains_cereals', ['millets'])],
            'wheat'   => ['label' => 'wheat & multigrain flours', 'need' => $rest * $req['wheat_share_of_non_millet'], 'products' => $byGroup('grains_cereals', ['wheat_flours'])],
            'rice'    => ['label' => 'rice', 'need' => $rest * $req['rice_share_of_non_millet'], 'products' => $byGroup('grains_cereals', ['rice'])],
        ];
        $want = [];
        $cap = [];
        foreach ($buckets as $k => $b) {
            $want[$k] = $b['need'];
            $cap[$k] = array_sum(array_map(fn ($p) => $this->capacity($p, 'g'), $b['products']));
        }
        [$bucketAlloc, $bucketUnmet] = $this->waterFill($want, $cap);
        $cerealSupplied = 0.0;
        foreach ($buckets as $k => $b) {
            $max = $cfg['basket']['max_products'][$k === 'millets' ? 'millets' : ($k === 'wheat' ? 'wheat_flours' : 'rice')];
            [$alloc] = $this->allocateProducts($b['products'], $bucketAlloc[$k], 'g', $max, fn ($p) => $this->weight($p, $hasDiabetes));
            foreach ($alloc as $pid => $amt) {
                $allocations[$pid] = ['amount' => $amt, 'unit' => 'g', 'group_need' => $needG, 'group_key' => 'cereals', 'bucket' => $b['label'], 'bucket_need' => $b['need']];
                $cerealSupplied += $amt;
            }
        }
        $groups['cereals'] = $this->summary('Grains, flours & millets', $needG, 'g', $cerealSupplied, count($byGroup('grains_cereals', ['millets', 'wheat_flours', 'rice'])) > 0);

        // ── Pulses ──────────────────────────────────────────────────────────
        $pulses = $byGroup('pulses_legumes');
        $needG = $req['pulses_kg'] * 1000;
        [$alloc] = $this->allocateProducts($pulses, $needG, 'g', $cfg['basket']['max_products']['pulses_legumes'], fn ($p) => $this->weight($p, $hasDiabetes, true));
        foreach ($alloc as $pid => $amt) {
            $allocations[$pid] = ['amount' => $amt, 'unit' => 'g', 'group_need' => $needG, 'group_key' => 'pulses'];
        }
        $groups['pulses'] = $this->summary('Pulses & legumes', $needG, 'g', array_sum($alloc), count($pulses) > 0);

        // ── Nuts & seeds (with sensible per-person caps for concentrated seeds) ──
        $nuts = $byGroup('nuts_seeds');
        $needG = $req['nuts_kg'] * 1000;
        [$alloc] = $this->allocateProducts($nuts, $needG, 'g', $cfg['basket']['max_products']['nuts_seeds'],
            fn ($p) => $this->weight($p, false),
            fn ($p) => $this->personCap($p, $members));
        foreach ($alloc as $pid => $amt) {
            $allocations[$pid] = ['amount' => $amt, 'unit' => 'g', 'group_need' => $needG, 'group_key' => 'nuts'];
        }
        $groups['nuts'] = $this->summary('Nuts & seeds', $needG, 'g', array_sum($alloc), count($nuts) > 0);

        // ── Cooking oil and ghee (planned in ml) ────────────────────────────
        $oilPref = strtolower((string) ($household['oil_preference'] ?? $household['oilPreference'] ?? ''));
        $oils = $byGroup('cooking_oils');
        $needMl = $req['cooking_oil_l'] * 1000;
        [$alloc] = $this->allocateProducts($oils, $needMl, 'ml', $cfg['basket']['max_products']['cooking_oils'],
            function ($p) use ($oilPref) {
                $w = 1.0;
                if ($oilPref !== '' && $oilPref !== 'cold_pressed_rotation' && str_contains(strtolower($p['name']), $oilPref)) {
                    $w *= 2.0;
                }

                return $w;
            }, null, $dens['cooking_oils']);
        foreach ($alloc as $pid => $amt) {
            $allocations[$pid] = ['amount' => $amt, 'unit' => 'ml', 'group_need' => $needMl, 'group_key' => 'oil'];
        }
        $groups['oil'] = $this->summary('Cooking oil', $needMl, 'ml', array_sum($alloc), count($oils) > 0);

        $ghee = $byGroup('ghee');
        $needMl = $req['ghee_l'] * 1000;
        [$alloc] = $this->allocateProducts($ghee, $needMl, 'ml', 1, fn ($p) => 1.0, null, $dens['ghee']);
        foreach ($alloc as $pid => $amt) {
            $allocations[$pid] = ['amount' => $amt, 'unit' => 'ml', 'group_need' => $needMl, 'group_key' => 'ghee'];
        }
        $groups['ghee'] = $this->summary('Ghee', $needMl, 'ml', array_sum($alloc), count($ghee) > 0);

        // ── Single-pick groups: cheapest per kg in stock ────────────────────
        foreach ([['sweeteners', 'sugar_kg', 'sweetener', 'Sweetener'], ['salt', 'salt_kg', 'salt', 'Salt']] as [$grp, $reqKey, $gk, $label]) {
            $list = $byGroup($grp);
            usort($list, fn ($a, $b) => [$this->pricePerKg($a), $a['name']] <=> [$this->pricePerKg($b), $b['name']]);
            $needG = $req[$reqKey] * 1000;
            [$alloc] = $this->allocateProducts(array_slice($list, 0, 1), $needG, 'g', 1, fn ($p) => 1.0);
            foreach ($alloc as $pid => $amt) {
                $allocations[$pid] = ['amount' => $amt, 'unit' => 'g', 'group_need' => $needG, 'group_key' => $gk];
            }
            $groups[$gk] = $this->summary($label, $needG, 'g', array_sum($alloc), count($list) > 0);
        }

        // ── Spices: one pack each of the household's core spices that exist ─
        $spices = $byGroup('spices');
        $core = [];
        foreach ($cfg['basket']['core_spices'] as $kw) {
            foreach ($spices as $p) {
                if (str_contains(strtolower($p['name']), $kw) && ! isset($core[$p['product_id']])) {
                    $family = in_array($kw, ['turmeric', 'haldi']) ? 'turmeric' : (in_array($kw, ['chilli', 'mirch']) ? 'chilli' : (in_array($kw, ['coriander', 'dhania']) ? 'coriander' : 'cumin'));
                    if (! in_array($family, array_column($core, 'family'), true)) {
                        $core[$p['product_id']] = ['p' => $p, 'family' => $family];
                    }
                }
            }
        }
        $needG = $req['spices_kg'] * 1000;
        if ($core) {
            $each = $needG / count($core);
            foreach ($core as $pid => $c) {
                $allocations[$pid] = ['amount' => min($each, $this->capacity($c['p'], 'g')), 'unit' => 'g', 'group_need' => $needG, 'group_key' => 'spices'];
            }
        }
        $groups['spices'] = $this->summary('Core spices', $needG, 'g', array_sum(array_map(fn ($a) => $a['group_key'] === 'spices' ? $a['amount'] : 0, $allocations)), count($spices) > 0);

        // ── Pack selection (per product), then group-level refinement, then lines ──
        $state = [];
        foreach ($allocations as $pid => $a) {
            $p = $products[$pid];
            $density = $dens[$p['group']] ?? null;
            $counts = array_fill(0, count($p['variants']), 0);
            foreach ($this->choosePacks($p, $a['amount'], $a['unit'], $density, $a['group_key'] === 'spices') as $choice) {
                $idx = array_search($choice['variant'], $p['variants'], true);
                $counts[$idx] = $choice['qty'];
            }
            $sizes = array_filter(array_map(fn ($v) => $this->sizeOf($v, $a['unit'], $density), $p['variants']));
            $smallest = $sizes ? min($sizes) : 0;
            $state[$pid] = [
                'p' => $p, 'a' => $a, 'unit' => $a['unit'], 'density' => $density, 'counts' => $counts,
                'max_total' => $a['group_key'] === 'nuts' ? max($this->personCap($p, $members), $smallest) : INF,
            ];
        }
        $refineGroups = [];
        foreach ($state as $pid => $st) {
            $key = $st['a']['group_key'] === 'cereals' ? 'cereals:' . $st['a']['bucket'] : $st['a']['group_key'];
            $refineGroups[$key][] = $pid;
        }
        foreach ($refineGroups as $key => $pids) {
            if (count($pids) > 1 && $key !== 'spices') {
                $this->refine($state, $pids, array_sum(array_map(fn ($pid) => $state[$pid]['a']['amount'], $pids)));
            }
        }

        $lines = [];
        $supplied = [];
        foreach ($state as $pid => $st) {
            $p = $st['p'];
            $a = $st['a'];
            $unit = $st['unit'];
            $density = $st['density'];
            $productSupply = 0.0;
            foreach ($p['variants'] as $idx => $vv) {
                $productSupply += $st['counts'][$idx] * $this->sizeOf($vv, $unit, $density);
            }
            foreach ($p['variants'] as $idx => $v) {
                $qty = $st['counts'][$idx];
                if ($qty <= 0) {
                    continue;
                }
                $packG = $v['g'] ?? (($v['ml'] !== null && $density) ? $v['ml'] * $density : null); // grams in one pack
                $size = $this->sizeOf($v, $unit, $density);
                $supplied[$a['group_key']] = ($supplied[$a['group_key']] ?? 0.0) + $qty * $size;
                $lines[] = [
                    'key' => 'p' . $pid . '-v' . ($v['var_id'] ?? 0),
                    'product_id' => $pid,
                    'var_id' => $v['var_id'],
                    'name' => $p['name'],
                    'image' => $p['image'],
                    'category' => $p['category'],
                    'food_group' => $p['group'],
                    'food_subgroup' => $p['sub'],
                    'group_key' => $a['group_key'],
                    'group_label' => $p['group_label'],
                    'package_size' => $v['label'],
                    'pack_text' => $v['pack_text'],
                    'pack_basis' => $v['basis'],
                    'pack_grams' => $packG !== null ? round($packG, 1) : null,
                    'qty' => $qty,
                    'max_qty' => $v['stock'],
                    'price' => $v['price'],
                    'regular_price' => $v['regular'],
                    'line_total' => PriceRules::lineTotal($v['price'], $qty),
                    'nutrition' => $p['nutrition'],
                    'allocated' => round($a['amount'], 1),
                    'allocated_unit' => $unit,
                    'reason' => $this->reason($p, $a, $productSupply, $unit, $v),
                ];
            }
        }

        // update supplied amounts using real packs
        foreach ($groups as $gk => &$g) {
            $g = $this->summary($g['label'], $g['need'], $g['unit'], $supplied[$gk] ?? 0.0, $g['has_products']);
        }
        unset($g);

        usort($lines, fn ($a, $b) => [$this->order($a['group_key']), $a['name']] <=> [$this->order($b['group_key']), $b['name']]);

        $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);
        $regularTotal = round(array_sum(array_map(fn ($l) => $l['regular_price'] * $l['qty'], $lines)), 2);

        return [
            'lines' => $lines,
            'groups' => array_values($groups),
            'shortfalls' => $this->shortfalls($groups),
            'subtotal' => $subtotal,
            'regular_total' => $regularTotal,
            'savings' => round(max(0, $regularTotal - $subtotal), 2),
            'data_quality' => $catalog['data_quality'] ?? [],
            'method' => [
                'Each food group starts from the nutrition need of your family for 30 days.',
                'Products are weighted by their protein and fibre content (from the label where available, otherwise a labelled food-group estimate).',
                'We use real pack sizes and never more than the stock available. Only a few products per group are used so the basket stays practical.',
                'Millets make up part of your grains, following ICMR-NIN guidance.',
            ],
        ];
    }

    // ───────────────────────── allocation helpers ─────────────────────────

    private function order(string $key): int
    {
        return array_search($key, ['cereals', 'pulses', 'nuts', 'oil', 'ghee', 'sweetener', 'spices', 'salt'], true) ?: 0;
    }

    private function summary(string $label, float $need, string $unit, float $supplied, bool $hasProducts): array
    {
        $pct = $need > 0 ? round(100 * $supplied / $need) : 100;

        return [
            'label' => $label,
            'need' => round($need, 1),
            'supplied' => round($supplied, 1),
            'unit' => $unit,
            'pct' => (int) $pct,
            'has_products' => $hasProducts,
            'status' => ! $hasProducts && $need > 0 ? 'unavailable' : ($pct >= 90 ? 'met' : 'partly'),
        ];
    }

    private function shortfalls(array $groups): array
    {
        $out = [];
        foreach ($groups as $g) {
            if ($g['need'] <= 0) {
                continue;
            }
            $fmt = fn (float $v, string $u) => $u === 'ml' ? number_format($v / 1000, 1) . ' L' : number_format($v / 1000, 1) . ' kg';
            if ($g['status'] === 'unavailable') {
                $out[] = "{$g['label']}: no suitable product is available right now (about " . $fmt($g['need'], $g['unit']) . ' needed).';
            } elseif ($g['status'] === 'partly') {
                $out[] = "{$g['label']}: the basket supplies " . $fmt($g['supplied'], $g['unit']) . ' of about ' . $fmt($g['need'], $g['unit']) . " needed ({$g['pct']}%) because of stock, pack sizes or serving limits.";
            }
        }

        return $out;
    }

    /** Max amount (g or ml) a product can supply from its stock. */
    private function capacity(array $p, string $unit, ?float $density = null): float
    {
        $total = 0.0;
        foreach ($p['variants'] as $v) {
            $total += $v['stock'] * ($this->sizeOf($v, $unit, $density) ?? 0.0);
        }

        return $total;
    }

    /** Amount in one pack in the given unit (g or ml). Oils/ghee are converted with their density. */
    private function sizeOf(array $v, string $unit, ?float $density): float
    {
        if ($unit === 'g') {
            return (float) ($v['g'] ?? 0.0);
        }
        if ($v['ml'] !== null) {
            return (float) $v['ml'];
        }

        return ($v['g'] !== null && $density) ? (float) $v['g'] / $density : 0.0; // grams → ml
    }

    private function personCap(array $p, int $members): float
    {
        $caps = config('family_pack.basket.per_person_month_cap_g');
        $name = strtolower($p['name']);
        foreach ($caps as $kw => $g) {
            if ($kw !== 'default' && str_contains($name, $kw)) {
                return $g * $members;
            }
        }

        return $caps['default'] * $members;
    }

    private function weight(array $p, bool $fibreFocus, bool $pulse = false): float
    {
        $per = $p['nutrition']['per_100g'] ?? null;
        if (! $per) {
            return 1.0;
        }
        $w = 1.0 + ($per['protein_g'] ?? 0) / ($pulse ? 25.0 : 30.0) + ($per['fiber_g'] ?? 0) / 20.0;
        if ($fibreFocus) {
            $w += ($per['fiber_g'] ?? 0) / 20.0;
        }

        return max(0.2, $w);
    }

    private function pricePerKg(array $p): float
    {
        $best = INF;
        foreach ($p['variants'] as $v) {
            if (($v['g'] ?? 0) > 0) {
                $best = min($best, $v['price'] / ($v['g'] / 1000));
            }
        }

        return $best === INF ? 1e9 : $best;
    }

    /**
     * @return array{0: array<int,float>, 1: float}  product_id => amount, unmet amount
     */
    private function allocateProducts(array $products, float $need, string $unit, int $maxProducts, callable $weightFn, ?callable $capFn = null, ?float $density = null): array
    {
        if ($need <= self::EPS || ! $products) {
            return [[], max(0.0, $need)];
        }
        $cfg = config('family_pack.basket');

        usort($products, fn ($a, $b) => [-$weightFn($a), $a['name']] <=> [-$weightFn($b), $b['name']]);
        $pool = array_slice($products, 0, max(1, $maxProducts));

        $run = function (array $set) use ($need, $unit, $weightFn, $capFn, $density, $cfg) {
            for ($i = 0; $i < 8; $i++) {
                $want = [];
                $cap = [];
                foreach ($set as $p) {
                    $want[$p['product_id']] = $weightFn($p);
                    $c = $this->capacity($p, $unit, $density);
                    $cap[$p['product_id']] = $capFn ? min($c, $capFn($p)) : $c;
                }
                [$alloc, $unmet] = $this->waterFill($want, $cap, $need);

                // drop tokens: less than half the smallest pack, or under the minimum share of the group
                $drop = [];
                foreach ($set as $p) {
                    $a = $alloc[$p['product_id']] ?? 0.0;
                    $smallest = min(array_map(fn ($v) => $this->sizeOf($v, $unit, $density), $p['variants']));
                    if ($a < 0.5 * $smallest || $a < $cfg['min_line_share'] * $need) {
                        $drop[] = $p['product_id'];
                    }
                }
                if (! $drop || count($drop) >= count($set)) {
                    if (count($drop) >= count($set) && $set) {
                        $best = $set[0]; // keep the highest-weighted product rather than nothing
                        $set = [$best];
                        $want = [$best['product_id'] => 1.0];
                        $cap = [$best['product_id'] => $capFn ? min($this->capacity($best, $unit, $density), $capFn($best)) : $this->capacity($best, $unit, $density)];
                        [$alloc, $unmet] = $this->waterFill($want, $cap, $need);
                    }

                    return [array_filter($alloc, fn ($v) => $v > self::EPS), $unmet, $set];
                }
                $set = array_values(array_filter($set, fn ($p) => ! in_array($p['product_id'], $drop, true)));
            }

            return [[], $need, $set];
        };

        [$alloc, $unmet] = $run($pool);
        // If stock or caps left a real gap, widen the pool to every eligible product.
        if ($unmet > 0.01 * $need && count($products) > count($pool)) {
            [$alloc2, $unmet2] = $run($products);
            if ($unmet2 < $unmet) {
                [$alloc, $unmet] = [$alloc2, $unmet2];
            }
        }

        return [$alloc, $unmet];
    }

    /**
     * Distribute `$total` (default: sum of wants) in proportion to `$want`, never exceeding `$cap` per key.
     * Whatever a capped key cannot take is passed to the others.
     *
     * @return array{0: array<int|string,float>, 1: float} allocation, unmet
     */
    private function waterFill(array $want, array $cap, ?float $total = null): array
    {
        $total ??= array_sum($want);
        $alloc = array_fill_keys(array_keys($want), 0.0);
        $active = array_keys(array_filter($want, fn ($w, $k) => $w > self::EPS && ($cap[$k] ?? 0) > self::EPS, ARRAY_FILTER_USE_BOTH));
        $remaining = $total;

        while ($active && $remaining > self::EPS) {
            $wsum = array_sum(array_map(fn ($k) => $want[$k], $active));
            $capped = [];
            foreach ($active as $k) {
                if ($alloc[$k] + $remaining * $want[$k] / $wsum >= $cap[$k] - self::EPS) {
                    $capped[] = $k;
                }
            }
            if (! $capped) {
                foreach ($active as $k) {
                    $alloc[$k] += $remaining * $want[$k] / $wsum;
                }
                $remaining = 0.0;
                break;
            }
            foreach ($capped as $k) {
                $remaining -= ($cap[$k] - $alloc[$k]);
                $alloc[$k] = $cap[$k];
            }
            $active = array_values(array_diff($active, $capped));
        }

        return [$alloc, max(0.0, $remaining)];
    }

    /**
     * Group-level pack refinement. Each product was rounded to its own packs, so overshoot can add up.
     * Try adding or removing single packs (and pairs of such moves) anywhere in the group while it brings the
     * group total closer to the target. Stock, per-product caps and "at least one product" are respected.
     */
    private function refine(array &$state, array $pids, float $target): void
    {
        $productTotal = function (int $pid) use (&$state): float {
            $st = $state[$pid];
            $t = 0.0;
            foreach ($st['p']['variants'] as $i => $v) {
                $t += $st['counts'][$i] * $this->sizeOf($v, $st['unit'], $st['density']);
            }

            return $t;
        };
        $score = function () use ($pids, &$state, $target): array {
            $sum = 0.0;
            $packs = 0;
            $price = 0.0;
            $drift = 0.0;   // how far each product has moved from its own planned share
            foreach ($pids as $pid) {
                $st = $state[$pid];
                $own = 0.0;
                foreach ($st['p']['variants'] as $i => $v) {
                    $c = $st['counts'][$i];
                    $own += $c * $this->sizeOf($v, $st['unit'], $st['density']);
                    $packs += $c;
                    $price += $c * $v['price'];
                }
                $sum += $own;
                $drift += abs($own - $st['a']['amount']);
            }

            return [max(0.0, $sum - $target) + 2 * max(0.0, $target - $sum) + 0.5 * $drift, $packs, $price];
        };
        $feasible = function () use ($pids, &$state, $productTotal): bool {
            foreach ($pids as $pid) {
                $st = $state[$pid];
                foreach ($st['p']['variants'] as $i => $v) {
                    if ($st['counts'][$i] < 0 || $st['counts'][$i] > $v['stock']) {
                        return false;
                    }
                }
                $t = $productTotal($pid);
                if ($t > $st['max_total'] + self::EPS) {
                    return false;
                }
                if ($t <= self::EPS) {   // a product already chosen for variety keeps at least one pack
                    return false;
                }
            }

            return true;
        };

        $moves = [];
        foreach ($pids as $pid) {
            foreach (array_keys($state[$pid]['p']['variants']) as $i) {
                foreach ([1, -1] as $d) {
                    $moves[] = [$pid, $i, $d];
                }
            }
        }
        $apply = function (array $m, int $sign) use (&$state): void {
            $state[$m[0]]['counts'][$m[1]] += $m[2] * $sign;
        };

        for ($iter = 0; $iter < 30; $iter++) {
            $best = null;
            $bestScore = $score();
            foreach ($moves as $a => $m1) {
                $apply($m1, 1);
                if ($feasible()) {
                    $sc = $score();
                    if ($sc < $bestScore) {
                        $best = [$m1];
                        $bestScore = $sc;
                    }
                }
                foreach ($moves as $b => $m2) {
                    if ($b <= $a) {
                        continue;
                    }
                    $apply($m2, 1);
                    if ($feasible()) {
                        $sc = $score();
                        if ($sc < $bestScore) {
                            $best = [$m1, $m2];
                            $bestScore = $sc;
                        }
                    }
                    $apply($m2, -1);
                }
                $apply($m1, -1);
            }
            if ($best === null) {
                break;
            }
            foreach ($best as $m) {
                $apply($m, 1);
            }
        }
    }

    /**
     * Choose how many of which real pack sizes to buy (at most two sizes of one product).
     * Aim for the need; prefer slight overshoot over shortfall; never exceed stock.
     *
     * @return array<int,array{variant: array, qty: int}>
     */
    public function choosePacks(array $product, float $need, string $unit, ?float $density, bool $atLeastOne = false): array
    {
        $variants = array_values(array_filter($product['variants'], fn ($v) => $this->sizeOf($v, $unit, $density) > 0));
        if (! $variants || ($need <= self::EPS && ! $atLeastOne)) {
            return [];
        }
        $need = max($need, 0.0);
        $tol = config('family_pack.basket.undershoot_tolerance');

        $best = null;
        $consider = function (array $combo) use (&$best, $need, $unit, $density, $tol) {
            $total = 0.0;
            $packs = 0;
            $price = 0.0;
            foreach ($combo as [$v, $c]) {
                $total += $c * $this->sizeOf($v, $unit, $density);
                $packs += $c;
                $price += $c * $v['price'];
            }
            if ($packs === 0) {
                return;
            }
            $under = max(0.0, $need - $total);
            $over = max(0.0, $total - $need);
            $penalty = ($under > $tol * $need ? 1 : 0);
            $score = [$penalty, $over + 3 * $under, $packs, $price];
            if ($best === null || $score < $best['score']) {
                $best = ['score' => $score, 'combo' => $combo];
            }
        };

        $limit = function (array $v) use ($need, $unit, $density) {
            $s = $this->sizeOf($v, $unit, $density);

            return min($v['stock'], (int) ceil($need / $s) + 1);
        };

        foreach ($variants as $i => $a) {
            for ($ca = 0; $ca <= $limit($a); $ca++) {
                $consider([[$a, $ca]]);
                foreach ($variants as $j => $b) {
                    if ($j <= $i) {
                        continue;
                    }
                    for ($cb = 1; $cb <= $limit($b); $cb++) {
                        if ($ca === 0) {
                            continue;
                        }
                        $consider([[$a, $ca], [$b, $cb]]);
                    }
                }
            }
        }
        if ($best === null) {
            return [];
        }

        $out = [];
        foreach ($best['combo'] as [$v, $c]) {
            if ($c > 0) {
                $out[] = ['variant' => $v, 'qty' => $c];
            }
        }

        return $out;
    }

    private function reason(array $p, array $a, float $suppliedAmount, string $unit, array $variant): string
    {
        $fmt = fn (float $v) => $unit === 'ml' ? ($v >= 1000 ? number_format($v / 1000, 1) . ' L' : round($v) . ' ml') : ($v >= 1000 ? number_format($v / 1000, 1) . ' kg' : round($v) . ' g');
        $part = match ($a['group_key']) {
            'cereals' => "Part of your {$a['bucket']} share: {$fmt($a['bucket_need'])} planned.",
            'pulses'  => 'One of the pulses chosen for variety and protein/fibre content.',
            'nuts'    => 'Seeds and nuts are kept to practical daily amounts.',
            'oil'     => 'Cooking oil share of your visible-fat need.',
            'ghee'    => 'Ghee share of your visible-fat need.',
            'sweetener' => 'Lowest-cost sweetener in stock for your added-sugar allowance.',
            'salt'    => 'Salt for the month within the 5 g per person per day guideline.',
            'spices'  => 'A core everyday spice.',
            default   => '',
        };
        $n = $p['nutrition'];
        $nut = '';
        if ($n['per_100g']) {
            $nut = ' Per 100 g: ' . $n['per_100g']['protein_g'] . ' g protein, ' . $n['per_100g']['fiber_g'] . ' g fibre'
                . ($n['basis'] === 'label' ? ' (from the label).' : ' (estimate, not from this product\'s label).');
        } else {
            $nut = ' Nutrition information is not available for this product, so it is not counted in the nutrient totals.';
        }

        return trim($part . ' Supplies ' . $fmt($suppliedAmount) . ' of the ' . $fmt($a['amount']) . ' planned for this product.' . $nut);
    }
}
