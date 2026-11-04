<?php

namespace App\Services\FamilyPack;

use App\Services\SmartFamilyGroceryEngine;

/**
 * One call that does the whole job: member targets → household totals → 30-day needs → real products → coverage.
 */
class FamilyPackPlanner
{
    /** Food groups whose nutrients count toward the coverage totals. */
    private const COUNTED_GROUPS = ['cereals', 'pulses', 'nuts', 'oil', 'ghee'];

    public function __construct(
        private SmartFamilyGroceryEngine $engine,
        private FamilyPackCatalog $catalog,
        private FamilyPackBasketBuilder $builder,
    ) {
    }

    public function plan(array $members, array $household = []): array
    {
        $calc = $this->engine->calculateBasket($members, $household);
        $basket = $this->builder->build($this->catalog->load(), $calc['monthly_requirements'], [
            'members' => count($members),
            'has_diabetes' => collect($calc['members_assessed'])->contains(fn ($m) => $m['health_flags']['diabetes']),
            'household' => $household,
        ]);
        $basket['coverage'] = $this->coverage($basket['lines'], $calc['monthly_nutrients']);

        return ['plan' => $calc, 'basket' => $basket];
    }

    /**
     * Nutrition delivered by a list of lines against the household's 30-day targets.
     * Lines need: group_key, qty, pack_grams, nutrition{basis, per_100g}, name.
     */
    public function coverage(array $lines, array $monthlyTargets): array
    {
        $keys = ['energy_kcal' => 'energy_kcal', 'protein_g' => 'protein_g', 'carbs_g' => 'carbs_g', 'fat_g' => 'fat_g', 'fiber_g' => 'fiber_g'];
        $total = array_fill_keys(array_keys($keys), ['label' => 0.0, 'estimate' => 0.0]);
        $notCounted = [];
        $estimated = [];

        foreach ($lines as $l) {
            if (! in_array($l['group_key'] ?? '', self::COUNTED_GROUPS, true)) {
                continue;
            }
            $per = $l['nutrition']['per_100g'] ?? null;
            $grams = ($l['qty'] ?? 0) * ($l['pack_grams'] ?? 0);
            if (! $per || $grams <= 0) {
                $notCounted[] = $l['name'];
                continue;
            }
            $bucket = ($l['nutrition']['basis'] ?? 'estimate') === 'label' ? 'label' : 'estimate';
            if ($bucket === 'estimate') {
                $estimated[] = $l['name'];
            }
            foreach ($keys as $k => $_) {
                $total[$k][$bucket] += $grams / 100.0 * ($per[$k] ?? 0);
            }
        }

        $out = [];
        foreach ($keys as $k => $_) {
            $target = (float) ($monthlyTargets[$k] ?? 0);
            $got = $total[$k]['label'] + $total[$k]['estimate'];
            $pct = $target > 0 ? (int) round(100 * $got / $target) : 0;
            $out[$k] = [
                'target_monthly' => round($target, 1),
                'basket_monthly' => round($got, 1),
                'from_label' => round($total[$k]['label'], 1),
                'from_estimate' => round($total[$k]['estimate'], 1),
                'pct' => $pct,
                'gap_monthly' => round(max(0.0, $target - $got), 1),
                'target_daily' => round($target / 30.0, 1),
                'basket_daily' => round($got / 30.0, 1),
            ];
        }

        return [
            'nutrients' => $out,
            'estimated_products' => array_values(array_unique($estimated)),
            'not_counted_products' => array_values(array_unique($notCounted)),
            'explanation' => 'This basket covers dry groceries only. Fresh vegetables, fruit, milk or curd, eggs or meat and other foods are expected to provide the rest, so a figure below 100% is normal and is not a shortage in your plan. Percentages are not a medical assessment.',
        ];
    }
}
