<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Turns a list of family members into daily nutrition targets and 30-day grocery quantities.
 *
 * All reference numbers live in config/family_pack.php together with their source.
 * This class contains only the rules for combining them. It never touches the database.
 *
 * This is a grocery planning tool. It is not a diagnosis, treatment or medical diet.
 */
class SmartFamilyGroceryEngine
{
    public const ACTIVITY_LEVELS = ['sedentary', 'moderate', 'high'];

    /** Accept the words customers and older code used. */
    public static function normalizeActivity(?string $activity): string
    {
        $a = strtolower(trim((string) $activity));

        return match (true) {
            in_array($a, ['high', 'heavy', 'athlete', 'very_active', 'active'], true) => 'high',
            in_array($a, ['sedentary', 'low', 'light'], true)                         => 'sedentary',
            default                                                                    => 'moderate',
        };
    }

    private function cfg(string $key, mixed $default = null): mixed
    {
        return config('family_pack.' . $key, $default);
    }

    private function flag(array $member, string $key): bool
    {
        return filter_var($member[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Pick the reference profile key for a member.
     *
     * @throws InvalidArgumentException when age is unusable or sex is missing for someone aged 10+
     */
    public function matchProfile(array $member): array
    {
        $age = (int) ($member['age'] ?? 0);
        if ($age < 1 || $age > 120) {
            throw new InvalidArgumentException('Age must be between 1 and 120 years.');
        }

        $sex = strtolower((string) ($member['sex'] ?? ''));
        if ($age >= 10 && ! in_array($sex, ['male', 'female'], true)) {
            throw new InvalidArgumentException('Sex (male or female) is required for members aged 10 and above.');
        }

        $profiles = $this->cfg('profiles');
        $activity = self::normalizeActivity($member['activity'] ?? null);
        $lifeStage = strtolower((string) ($member['life_stage'] ?? $member['lifeStage'] ?? 'none'));

        if ($sex === 'female' && $age >= 15 && $age <= 49) {
            if ($lifeStage === 'pregnant' || $this->flag($member, 'is_pregnant')) {
                return ['key' => 'PREG', 'blend' => null];
            }
            if ($lifeStage === 'lactating' || $this->flag($member, 'is_lactating')) {
                return ['key' => 'LACT', 'blend' => null];
            }
        }

        if ($age <= 18) {
            foreach ($profiles as $key => $p) {
                if ($p['activity'] !== 'any' || $p['ages'][1] > 18) {
                    continue;
                }
                if ($age < $p['ages'][0] || $age > $p['ages'][1]) {
                    continue;
                }
                if ($p['sex'] === 'any' || $p['sex'] === $sex) {
                    return ['key' => $key, 'blend' => null];
                }
            }
        }

        $prefix = $sex === 'female' ? 'F' : 'M';
        $older = $prefix . '60';

        if ($age >= 60) {
            return ['key' => $older, 'blend' => null];
        }

        $adult = $prefix . '_' . match ($activity) {
            'sedentary' => 'S',
            'high'      => 'H',
            default     => 'M',
        };

        $b = $this->cfg('blend_to_older');
        if ($age >= $b['from_age'] && $age < $b['to_age']) {
            $weight = ($age - ($b['from_age'] - 1)) / ($b['to_age'] - ($b['from_age'] - 1));

            return ['key' => $adult, 'blend' => ['to' => $older, 'weight' => round($weight, 4)]];
        }

        return ['key' => $adult, 'blend' => null];
    }

    /**
     * Height in metres from cm or feet+inches. Null when missing or implausible.
     */
    private function heightMetres(array $member): ?float
    {
        $cm = (float) ($member['heightCm'] ?? $member['height_cm'] ?? 0);
        if ($cm > 0) {
            $m = $cm / 100.0;
        } else {
            $ft = (float) ($member['heightFt'] ?? 0);
            $in = (float) ($member['heightIn'] ?? 0);
            $m = (($ft * 12) + $in) * 2.54 / 100.0;
        }

        return ($m >= 0.5 && $m <= 2.5) ? $m : null;
    }

    /** Adult BMI only. Children and teenagers never get an adult BMI. */
    public function computeBmi(array $member): ?float
    {
        if ((int) ($member['age'] ?? 0) < 19) {
            return null;
        }
        $weight = (float) ($member['weightKg'] ?? $member['weight_kg'] ?? 0);
        $height = $this->heightMetres($member);
        if ($height === null || $weight < 10 || $weight > 250) {
            return null;
        }

        return round($weight / ($height * $height), 1);
    }

    /**
     * Raw reference values (before health adjustments) for one member.
     */
    private function referenceValues(array $member, array $match): array
    {
        $profiles = $this->cfg('profiles');
        $age = (int) $member['age'];
        $activity = self::normalizeActivity($member['activity'] ?? null);
        $fields = ['energy', 'protein', 'cereals', 'pulses', 'nuts', 'fat'];

        $row = $profiles[$match['key']];
        $values = array_intersect_key($row, array_flip($fields));
        $basis = $row['basis'];
        $notes = [];

        $olderRow = function (string $key) use ($profiles, $activity, $fields): array {
            $r = array_intersect_key($profiles[$key], array_flip($fields));
            $factor = (float) ($this->cfg('older_activity_factor')[$activity] ?? 1.0);
            foreach (['energy', 'cereals', 'pulses', 'nuts', 'fat'] as $f) {
                $r[$f] = $r[$f] * $factor;
            }

            return $r;
        };

        if ($age >= 60) {
            $values = $olderRow($match['key']);
            if ($activity !== 'sedentary') {
                $basis = 'estimate';
                $notes[] = 'ICMR gives no activity split after 60; a modest planning uplift was applied for activity level.';
            }
        }

        if ($match['blend']) {
            $to = $olderRow($match['blend']['to']);
            $w = $match['blend']['weight'];
            foreach ($fields as $f) {
                $values[$f] = $values[$f] * (1 - $w) + $to[$f] * $w;
            }
            $basis = 'estimate';
            $notes[] = 'ICMR has no separate 55–59 year category; values move gradually from the adult to the older-adult reference.';
        }

        if ($basis === 'derived') {
            $notes[] = 'Derived from official ICMR-NIN figures (see source).';
        }

        return [$values, $basis, $row['source'], $row['label'], $notes];
    }

    /**
     * Per-person assessment: targets, derived nutrients and any health-based planning adjustments.
     */
    public function assessMember(array $member): array
    {
        $match = $this->matchProfile($member);
        [$ref, $basis, $source, $label, $notes] = $this->referenceValues($member, $match);

        $age = (int) $member['age'];
        $d = $this->cfg('derived');
        $energy = (float) $ref['energy'];
        $protein = (float) $ref['protein'];

        $fatG = $energy * $d['fat_energy_share'] / 9.0;
        $carbsG = max(0.0, ($energy - 4 * $protein - 9 * $fatG) / 4.0);
        $fibreG = $energy / 1000.0 * $d['fibre_g_per_1000_kcal'];

        $sugar = $age < $d['no_added_sugar_below_age'] ? 0.0 : $energy * $d['added_sugar_energy_share'] / 4.0;
        $salt = (float) ($age < 4 ? $d['salt_g_per_day_young'] : $d['salt_g_per_day']);
        $spices = (float) $d['spices_g_per_day'];
        $fat = (float) $ref['fat'];
        $gheeShare = (float) $this->cfg('ghee_share_default');

        // Health flags: modest, per-person, never stacked on the same food (most protective single factor wins).
        $health = $this->cfg('health');
        $flags = [
            'diabetes' => $this->flag($member, 'diabetes'),
            'bp'       => $this->flag($member, 'bp'),
            'heart'    => $this->flag($member, 'heart'),
            'kidney'   => $this->flag($member, 'kidney'),
        ];
        $factors = ['sugar' => [1.0], 'salt' => [1.0], 'fat' => [1.0]];
        $adjustments = [];
        if ($flags['diabetes']) {
            $factors['sugar'][] = $health['diabetes']['sugar_factor'];
            $adjustments[] = 'Added sugar planning allowance reduced by ' . round((1 - $health['diabetes']['sugar_factor']) * 100) . '% for this person. Total carbohydrates and portions matter more than sugar alone.';
        }
        if ($flags['bp']) {
            $factors['salt'][] = $health['bp']['salt_factor'];
            $adjustments[] = 'Salt planning allowance reduced by ' . round((1 - $health['bp']['salt_factor']) * 100) . '% for this person. Total sodium (including packaged foods) matters.';
        }
        if ($flags['heart']) {
            $factors['fat'][] = $health['heart']['fat_factor'];
            $gheeShare = min($gheeShare, (float) $health['heart']['ghee_share']);
            $adjustments[] = 'Visible fat reduced slightly and the ghee share lowered to ' . round($gheeShare * 100) . '% for this person.';
        }
        if ($flags['kidney']) {
            $adjustments[] = 'Kidney condition: no automatic changes were made. Please follow your nephrologist or dietitian.';
        }
        $sugar *= min($factors['sugar']);
        $salt *= min($factors['salt']);
        $fat *= min($factors['fat']);

        // BMI and reference-weight disclosure
        $bmi = $this->computeBmi($member);
        $hasMeasures = (float) ($member['weightKg'] ?? $member['weight_kg'] ?? 0) > 0 && $this->heightMetres($member) !== null;
        $refWeight = ($member['sex'] ?? '') === 'female' ? 55 : 65;
        if ($age < 19) {
            $bmiNote = 'BMI is not interpreted for children and teenagers (adult cut-offs do not apply). Growth should be reviewed with a paediatrician.';
        } elseif ($bmi !== null) {
            $bmiNote = "BMI {$bmi}. Targets use the ICMR reference body weight ({$refWeight} kg), not the measured weight.";
        } elseif ($hasMeasures) {
            $bmiNote = 'Measurements look implausible, so BMI is not shown.';
        } else {
            $bmiNote = "Height/weight not entered. Targets use the ICMR reference body weight ({$refWeight} kg); this is a reference value, not a measurement.";
        }

        return [
            'member_id'       => $member['id'] ?? null,
            'name'            => $member['name'] ?? 'Member',
            'age'             => $age,
            'sex'             => $member['sex'] ?? null,
            'profile_key'     => $match['key'],
            'profile_label'   => $label,
            'profile_basis'   => $basis,
            'profile_source'  => $source,
            'profile_notes'   => $notes,
            'bmi'             => $bmi,
            'bmi_note'        => $bmiNote,
            'uses_reference_weight' => true,
            'activity'        => self::normalizeActivity($member['activity'] ?? null),
            'goal'            => strtolower((string) ($member['goal'] ?? 'general')),
            'health_flags'    => $flags,
            'adjustments'     => $adjustments,
            'ghee_share'      => $gheeShare,
            'daily_grams'     => [
                'cereals' => round($ref['cereals'], 1),
                'pulses'  => round($ref['pulses'], 1),
                'nuts'    => round($ref['nuts'], 1),
                'fat'     => round($fat, 1),
                'sugar'   => round($sugar, 1),
                'salt'    => round($salt, 1),
                'spices'  => round($spices, 1),
            ],
            'daily_nutrients' => [
                'energy_kcal' => (int) round($energy),
                'protein_g'   => round($protein, 1),
                'carbs_g'     => round($carbsG, 1),
                'fat_g'       => round($fatG, 1),
                'fiber_g'     => round($fibreG, 1),
            ],
        ];
    }

    /**
     * Household totals and 30-day grocery needs.
     */
    public function calculateBasket(array $members, array $household = []): array
    {
        if (empty($members)) {
            return [
                'members_count' => 0,
                'members_assessed' => [],
                'daily_household_grams' => [],
                'daily_household_nutrients' => [],
                'monthly_requirements' => [],
                'monthly_nutrients' => [],
                'interpretation' => 'No family members entered.',
                'safety_alerts' => [],
            ];
        }

        $days = (int) $this->cfg('days', 30);
        $assessed = array_map(fn ($m) => $this->assessMember($m), array_values($members));

        $g = ['cereals' => 0.0, 'pulses' => 0.0, 'nuts' => 0.0, 'fat' => 0.0, 'sugar' => 0.0, 'salt' => 0.0, 'spices' => 0.0];
        $n = ['energy_kcal' => 0.0, 'protein_g' => 0.0, 'carbs_g' => 0.0, 'fat_g' => 0.0, 'fiber_g' => 0.0];
        $gheeG = 0.0;
        $milletG = 0.0;
        $any = ['diabetes' => false, 'bp' => false, 'heart' => false, 'kidney' => false];
        $conditionCount = 0;
        $millet = $this->cfg('millets');

        foreach ($assessed as $a) {
            foreach ($g as $k => $_) {
                $g[$k] += $a['daily_grams'][$k];
            }
            foreach ($n as $k => $_) {
                $n[$k] += $a['daily_nutrients'][$k];
            }
            $gheeG += $a['daily_grams']['fat'] * $a['ghee_share'];
            $share = $a['age'] <= $millet['child_max_age'] ? $millet['child_share'] : $millet['adult_share'];
            $milletG += $a['daily_grams']['cereals'] * $share;
            foreach ($a['health_flags'] as $k => $v) {
                $any[$k] = $any[$k] || $v;
            }
            $conditionCount += count(array_filter($a['health_flags']));
        }

        // Monthly kg
        $m = [];
        foreach ($g as $k => $v) {
            $m[$k] = $v * $days / 1000.0;
        }
        $m['ghee'] = $gheeG * $days / 1000.0;
        $m['cooking_oil'] = $m['fat'] - $m['ghee'];

        // Household habits (planning assumptions)
        $habits = $this->cfg('habits');
        $meals = (float) ($household['meals_cooked_at_home'] ?? $household['mealsCookedAtHome'] ?? 3);
        if ($meals < $habits['low_home_cooking']['max_meals']) {
            foreach (['cereals', 'pulses', 'nuts', 'fat', 'sugar', 'salt', 'spices', 'ghee', 'cooking_oil'] as $k) {
                $m[$k] *= $habits['low_home_cooking']['factor'];
            }
        }
        $pulseFreq = strtolower((string) ($household['pulse_frequency'] ?? $household['pulseFrequency'] ?? 'daily'));
        if (in_array($pulseFreq, ['rare', 'low', 'infrequent'], true)) {
            $m['pulses'] *= $habits['pulses_rare'];
        }
        $nutsFreq = strtolower((string) ($household['nuts_frequency'] ?? $household['nutsFrequency'] ?? 'daily'));
        if (in_array($nutsFreq, ['rare', 'infrequent'], true)) {
            $m['nuts'] *= $habits['nuts_rare'];
        }
        $sugarUse = strtolower((string) ($household['sugar_use'] ?? $household['sugarUse'] ?? 'moderate'));
        if ($sugarUse === 'low') {
            $m['sugar'] *= $habits['sugar_low'];
        }

        // Millets share of cereals (DGI 2024) with preference / diabetes lift
        $milletShare = $g['cereals'] > 0 ? $milletG / $g['cereals'] : $millet['adult_share'];
        $pref = strtolower((string) ($household['staple_preference'] ?? $household['staplePreference'] ?? 'balanced'));
        $prefCfg = $this->cfg('staple_preference');
        if ($pref === 'millets') {
            $milletShare = max($milletShare, $prefCfg['millets']['millet_share']);
        }
        if ($any['diabetes']) {
            $milletShare += 0.05;
        }
        $milletShare = min($milletShare, $millet['max_share']);
        $split = $prefCfg[$pref] ?? $prefCfg['balanced'];

        $dens = $this->cfg('density_kg_per_l');
        $requirements = [
            'cereals_kg'      => round($m['cereals'], 2),
            'pulses_kg'       => round($m['pulses'], 2),
            'nuts_kg'         => round($m['nuts'], 2),
            'fat_kg'          => round($m['fat'], 2),
            'cooking_oil_kg'  => round($m['cooking_oil'], 2),
            'ghee_kg'         => round($m['ghee'], 2),
            'cooking_oil_l'   => round($m['cooking_oil'] / $dens['cooking_oils'], 2),
            'ghee_l'          => round($m['ghee'] / $dens['ghee'], 2),
            'sugar_kg'        => round($m['sugar'], 2),
            'salt_kg'         => round($m['salt'], 2),
            'spices_kg'       => round($m['spices'], 2),
            'millet_share'    => round($milletShare, 3),
            'wheat_share_of_non_millet' => $split['wheat'],
            'rice_share_of_non_millet'  => $split['rice'],
        ];
        $requirements['oil_liters'] = round($requirements['cooking_oil_l'] + $requirements['ghee_l'], 2);

        $alerts = $this->alerts($any, $conditionCount);

        $count = count($assessed);
        $interpretation = "Planning basis for {$count} member(s): about "
            . number_format($requirements['cereals_kg'], 1) . ' kg grains & flours, '
            . number_format($requirements['pulses_kg'], 1) . ' kg pulses, '
            . number_format($requirements['oil_liters'], 1) . ' L cooking oil & ghee and '
            . number_format($requirements['nuts_kg'], 1) . ' kg nuts/seeds for 30 days. '
            . 'Fresh vegetables, fruit and dairy are not part of this basket.';

        return [
            'members_count' => $count,
            'members_assessed' => $assessed,
            'daily_household_grams' => array_map(fn ($v) => round($v, 1), $g),
            'daily_household_nutrients' => [
                'energy_kcal' => (int) round($n['energy_kcal']),
                'protein_g'   => round($n['protein_g'], 1),
                'carbs_g'     => round($n['carbs_g'], 1),
                'fat_g'       => round($n['fat_g'], 1),
                'fiber_g'     => round($n['fiber_g'], 1),
            ],
            'monthly_requirements' => $requirements,
            'monthly_nutrients' => [
                'energy_kcal' => (int) round($n['energy_kcal'] * $days),
                'protein_g'   => round($n['protein_g'] * $days, 1),
                'carbs_g'     => round($n['carbs_g'] * $days, 1),
                'fat_g'       => round($n['fat_g'] * $days, 1),
                'fiber_g'     => round($n['fiber_g'] * $days, 1),
            ],
            'interpretation' => $interpretation,
            'safety_alerts' => $alerts,
            'positioning_disclaimer' => 'A grocery planning assistant. Not medical advice, a diagnosis, a treatment or a complete meal plan.',
        ];
    }

    private function alerts(array $any, int $conditionCount): array
    {
        $alerts = [];
        if ($any['kidney']) {
            $alerts[] = ['type' => 'critical', 'title' => 'Kidney condition: professional review needed',
                'message' => 'Kidney conditions need individual limits on protein, potassium, phosphorus, sodium and fluids. This planner makes no automatic changes for kidney conditions. Please have the basket reviewed by your nephrologist or dietitian before buying.'];
        }
        if ($any['diabetes']) {
            $alerts[] = ['type' => 'info', 'title' => 'Diabetes: lower added-sugar planning',
                'message' => 'Added sugar is planned lower for the person with diabetes and fibre-rich grains are given slightly more room. Managing diabetes depends on total carbohydrates, portions and your treatment plan, so please follow your doctor or dietitian.'];
        }
        if ($any['bp']) {
            $alerts[] = ['type' => 'info', 'title' => 'High blood pressure: sodium notice',
                'message' => 'Salt is planned slightly lower for the person with high blood pressure. Rock salt (sendha namak) is also mostly sodium chloride and is not a safer substitute. Packaged foods add sodium too. This does not replace medical advice.'];
        }
        if ($any['heart']) {
            $alerts[] = ['type' => 'info', 'title' => 'Heart condition: cooking-fat balance',
                'message' => 'Visible fat is planned slightly lower and more of it comes from cooking oil than ghee for the person with a heart condition. Please follow your cardiologist or dietitian for specific advice.'];
        }
        if ($conditionCount > 1) {
            $alerts[] = ['type' => 'info', 'title' => 'More than one health condition',
                'message' => 'Adjustments are applied separately for each person and never stacked on the same food. With several conditions please have the basket reviewed by a qualified professional.'];
        }

        return $alerts;
    }
}
