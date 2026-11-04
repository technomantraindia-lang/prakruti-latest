<?php

namespace Tests\Unit\FamilyPack;

use App\Services\SmartFamilyGroceryEngine;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SmartFamilyGroceryEngineTest extends TestCase
{
    private SmartFamilyGroceryEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new SmartFamilyGroceryEngine();
    }

    private function family(): array
    {
        return [
            ['id' => 'f', 'name' => 'Father', 'age' => 45, 'sex' => 'male', 'activity' => 'high'],
            ['id' => 'm', 'name' => 'Mother', 'age' => 40, 'sex' => 'female', 'activity' => 'sedentary'],
            ['id' => 's', 'name' => 'Son', 'age' => 12, 'sex' => 'male', 'activity' => 'moderate'],
            ['id' => 'g', 'name' => 'Grandmother', 'age' => 70, 'sex' => 'female', 'activity' => 'sedentary', 'bp' => true],
        ];
    }

    public static function profileCases(): array
    {
        return [
            'toddler'            => [['age' => 2], 'C13'],
            'child 5'            => [['age' => 5], 'C46'],
            'child 8'            => [['age' => 8], 'C79'],
            'boy 12'             => [['age' => 12, 'sex' => 'male'], 'M1012'],
            'girl 12'            => [['age' => 12, 'sex' => 'female'], 'F1012'],
            'girl 15'            => [['age' => 15, 'sex' => 'female'], 'F1315'],
            'boy 17'             => [['age' => 17, 'sex' => 'male'], 'M1618'],
            'man sedentary'      => [['age' => 30, 'sex' => 'male', 'activity' => 'sedentary'], 'M_S'],
            'man moderate'       => [['age' => 30, 'sex' => 'male', 'activity' => 'moderate'], 'M_M'],
            'woman heavy'        => [['age' => 30, 'sex' => 'female', 'activity' => 'high'], 'F_H'],
            'athlete maps heavy' => [['age' => 25, 'sex' => 'male', 'activity' => 'athlete'], 'M_H'],
            'man 54 uses adult'  => [['age' => 54, 'sex' => 'male', 'activity' => 'moderate'], 'M_M'],
            'woman 70'           => [['age' => 70, 'sex' => 'female', 'activity' => 'sedentary'], 'F60'],
            'pregnant'           => [['age' => 28, 'sex' => 'female', 'lifeStage' => 'pregnant'], 'PREG'],
            'lactating'          => [['age' => 28, 'sex' => 'female', 'lifeStage' => 'lactating'], 'LACT'],
        ];
    }

    #[DataProvider('profileCases')]
    public function test_profile_selection(array $member, string $expectedKey): void
    {
        $this->assertSame($expectedKey, $this->engine->matchProfile($member)['key']);
    }

    public function test_missing_sex_is_a_clear_error_for_age_10_and_over(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->engine->assessMember(['age' => 30]);
    }

    public function test_young_child_without_sex_is_fine(): void
    {
        $a = $this->engine->assessMember(['age' => 8]);
        $this->assertSame('C79', $a['profile_key']);
    }

    public function test_age_out_of_range_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->engine->assessMember(['age' => 0, 'sex' => 'male']);
    }

    public function test_official_values_are_used_as_published(): void
    {
        $toddler = $this->engine->assessMember(['age' => 2]);
        $this->assertSame(1070, $toddler['daily_nutrients']['energy_kcal']);   // RDA 2020 Table 1a
        $this->assertSame(12.5, $toddler['daily_nutrients']['protein_g']);     // RDA 2020 Table 2a
        $this->assertSame(20.0, $toddler['daily_grams']['fat']);               // DGI 2024 Table 1.6

        $man = $this->engine->assessMember(['age' => 30, 'sex' => 'male', 'activity' => 'moderate']);
        $this->assertSame(2710, $man['daily_nutrients']['energy_kcal']);
        $this->assertSame(370.0, $man['daily_grams']['cereals']);              // DGI 2024 Table 1.6 (old code had 320)
        $this->assertSame('official', $man['profile_basis']);

        $woman = $this->engine->assessMember(['age' => 30, 'sex' => 'female', 'activity' => 'sedentary']);
        $this->assertSame(1660, $woman['daily_nutrients']['energy_kcal']);
        $this->assertSame(190.0, $woman['daily_grams']['cereals']);
    }

    public function test_teenager_protein_is_not_inflated_on_top_of_the_official_value(): void
    {
        $boy = $this->engine->assessMember(['age' => 14, 'sex' => 'male', 'activity' => 'high']);
        $this->assertSame(44.9, $boy['daily_nutrients']['protein_g']);
        $this->assertSame(2860, $boy['daily_nutrients']['energy_kcal']);       // activity does not stack on child tables
    }

    public function test_activity_is_not_double_counted_for_adults(): void
    {
        $heavy = $this->engine->assessMember(['age' => 30, 'sex' => 'male', 'activity' => 'high']);
        $this->assertSame(3470, $heavy['daily_nutrients']['energy_kcal']);
        $this->assertSame(54.0, $heavy['daily_nutrients']['protein_g']);
    }

    public function test_macros_always_add_up_to_stated_energy(): void
    {
        foreach (config('family_pack.profiles') as $key => $p) {
            $member = [
                'age' => $p['ages'][0],
                'sex' => $p['sex'] === 'any' ? null : $p['sex'],
                'activity' => $p['activity'] === 'any' ? 'sedentary' : $p['activity'],
            ];
            if ($key === 'PREG') { $member['lifeStage'] = 'pregnant'; $member['age'] = 28; }
            if ($key === 'LACT') { $member['lifeStage'] = 'lactating'; $member['age'] = 28; }

            $n = $this->engine->assessMember($member)['daily_nutrients'];
            $kcal = 4 * $n['protein_g'] + 4 * $n['carbs_g'] + 9 * $n['fat_g'];
            $this->assertEqualsWithDelta($n['energy_kcal'], $kcal, 3.0, "Macros do not add up for profile {$key}");
        }
    }

    public function test_fibre_follows_the_guideline_of_25g_per_1000_kcal(): void
    {
        $a = $this->engine->assessMember(['age' => 30, 'sex' => 'male', 'activity' => 'moderate']);
        $this->assertEqualsWithDelta(67.8, $a['daily_nutrients']['fiber_g'], 0.1);
    }

    public function test_no_cliff_between_49_and_50_and_gentle_slope_to_60(): void
    {
        $energy = fn (int $age) => $this->engine->assessMember(['age' => $age, 'sex' => 'male', 'activity' => 'high'])['daily_nutrients']['energy_kcal'];

        $this->assertSame($energy(49), $energy(50));
        $this->assertSame($energy(49), $energy(54));

        $prev = $energy(54);
        for ($age = 55; $age <= 60; $age++) {
            $now = $energy($age);
            $this->assertLessThan($prev, $now, "Energy should fall gently at {$age}");
            $this->assertLessThan(0.30 * 3470, $prev - $now, "Step at {$age} is too abrupt");
            $prev = $now;
        }
        $this->assertSame('estimate', $this->engine->assessMember(['age' => 57, 'sex' => 'male', 'activity' => 'high'])['profile_basis']);
    }

    public function test_older_adult_activity_uplift_is_flagged_as_estimate(): void
    {
        $sed = $this->engine->assessMember(['age' => 70, 'sex' => 'male', 'activity' => 'sedentary']);
        $act = $this->engine->assessMember(['age' => 70, 'sex' => 'male', 'activity' => 'high']);
        $this->assertSame(1740, $sed['daily_nutrients']['energy_kcal']);
        $this->assertSame('official', $sed['profile_basis']);
        $this->assertSame(2088, $act['daily_nutrients']['energy_kcal']);
        $this->assertSame('estimate', $act['profile_basis']);
    }

    public function test_adult_bmi_is_calculated_and_reference_weight_is_disclosed(): void
    {
        $a = $this->engine->assessMember(['age' => 35, 'sex' => 'male', 'heightCm' => 175, 'weightKg' => 70]);
        $this->assertSame(22.9, $a['bmi']);
        $this->assertStringContainsString('reference body weight (65 kg)', $a['bmi_note']);
    }

    public function test_feet_and_inches_height_works(): void
    {
        $a = $this->engine->assessMember(['age' => 35, 'sex' => 'female', 'heightFt' => 5, 'heightIn' => 4, 'weightKg' => 60]);
        $this->assertSame(22.7, $a['bmi']);
    }

    public function test_child_never_gets_an_adult_bmi_even_with_measurements(): void
    {
        $a = $this->engine->assessMember(['age' => 8, 'heightCm' => 127, 'weightKg' => 27]);
        $this->assertNull($a['bmi']);
        $this->assertStringContainsString('not interpreted for children', $a['bmi_note']);
    }

    public function test_missing_measurements_are_not_invented(): void
    {
        $a = $this->engine->assessMember(['age' => 35, 'sex' => 'female']);
        $this->assertNull($a['bmi']);
        $this->assertStringContainsString('Height/weight not entered', $a['bmi_note']);
        $this->assertStringContainsString('55 kg', $a['bmi_note']);
    }

    public function test_four_member_family_totals_match_hand_calculation(): void
    {
        $r = $this->engine->calculateBasket($this->family());

        $this->assertSame(8880, $r['daily_household_nutrients']['energy_kcal']);   // 3470 + 1660 + 2220 + 1530
        $this->assertSame(177.2, $r['daily_household_nutrients']['protein_g']);    // 54 + 45.7 + 31.8 + 45.7
        $this->assertEqualsWithDelta(222.0, $r['daily_household_nutrients']['fiber_g'], 0.2);

        $req = $r['monthly_requirements'];
        $this->assertSame(32.52, $req['cereals_kg']);   // (474 + 190 + 280 + 140) g x 30
        $this->assertSame(11.22, $req['pulses_kg']);    // (154 + 60 + 90 + 70) g x 30
        $this->assertSame(4.2, $req['nuts_kg']);        // (50 + 30 + 30 + 30) g x 30
        $this->assertSame(3.75, $req['fat_kg']);        // (50 + 25 + 35 + 15) g x 30
        $this->assertSame(0.94, $req['ghee_kg']);       // 25% share
        $this->assertEqualsWithDelta(0.585, $req['salt_kg'], 0.006);
        $this->assertSame(4, $r['members_count']);
    }

    public function test_bp_flag_reduces_only_that_persons_salt(): void
    {
        $with = $this->engine->calculateBasket($this->family());
        $fam = $this->family();
        unset($fam[3]['bp']);
        $without = $this->engine->calculateBasket($fam);

        $this->assertEqualsWithDelta(0.60, $without['monthly_requirements']['salt_kg'], 0.006);
        $this->assertEqualsWithDelta(0.585, $with['monthly_requirements']['salt_kg'], 0.006);
        $this->assertSame(4.5, $with['members_assessed'][3]['daily_grams']['salt']);
        $this->assertSame(5.0, $with['members_assessed'][0]['daily_grams']['salt']);
    }

    public function test_diabetes_reduces_only_that_persons_sugar(): void
    {
        $fam = [
            ['name' => 'A', 'age' => 40, 'sex' => 'male', 'activity' => 'moderate', 'diabetes' => true],
            ['name' => 'B', 'age' => 38, 'sex' => 'female', 'activity' => 'moderate'],
        ];
        $r = $this->engine->calculateBasket($fam);
        $this->assertEqualsWithDelta(0.75 * (2710 * 0.05 / 4), $r['members_assessed'][0]['daily_grams']['sugar'], 0.1);
        $this->assertEqualsWithDelta(2130 * 0.05 / 4, $r['members_assessed'][1]['daily_grams']['sugar'], 0.1);
    }

    public function test_heart_flag_lowers_fat_slightly_and_ghee_share_for_that_person(): void
    {
        $plain = $this->engine->assessMember(['age' => 50, 'sex' => 'male', 'activity' => 'moderate']);
        $heart = $this->engine->assessMember(['age' => 50, 'sex' => 'male', 'activity' => 'moderate', 'heart' => true]);
        $this->assertSame(0.25, $plain['ghee_share']);
        $this->assertSame(0.12, $heart['ghee_share']);
        $this->assertEqualsWithDelta($plain['daily_grams']['fat'] * 0.95, $heart['daily_grams']['fat'], 0.1);
    }

    public function test_kidney_flag_changes_no_quantities_but_shows_a_critical_review_alert(): void
    {
        $plain = $this->engine->calculateBasket([['age' => 60, 'sex' => 'male']]);
        $kidney = $this->engine->calculateBasket([['age' => 60, 'sex' => 'male', 'kidney' => true]]);

        $this->assertSame($plain['monthly_requirements'], $kidney['monthly_requirements']);
        $this->assertSame($plain['daily_household_nutrients'], $kidney['daily_household_nutrients']);
        $this->assertSame('critical', $kidney['safety_alerts'][0]['type']);
        $this->assertStringContainsString('nephrologist', $kidney['safety_alerts'][0]['message']);
    }

    public function test_combined_conditions_are_applied_per_food_and_never_stacked(): void
    {
        $base = ['age' => 55, 'sex' => 'male', 'activity' => 'sedentary'];
        $a = $this->engine->assessMember($base + ['diabetes' => true, 'bp' => true, 'heart' => true]);
        $plain = $this->engine->assessMember($base);

        $this->assertEqualsWithDelta($plain['daily_grams']['sugar'] * 0.75, $a['daily_grams']['sugar'], 0.1);
        $this->assertEqualsWithDelta($plain['daily_grams']['salt'] * 0.90, $a['daily_grams']['salt'], 0.1);
        $this->assertEqualsWithDelta($plain['daily_grams']['fat'] * 0.95, $a['daily_grams']['fat'], 0.1);
        $this->assertSame($plain['daily_grams']['cereals'], $a['daily_grams']['cereals']);
        $this->assertSame($plain['daily_nutrients']['energy_kcal'], $a['daily_nutrients']['energy_kcal']);

        $r = $this->engine->calculateBasket([$base + ['diabetes' => true, 'bp' => true]]);
        $this->assertContains('More than one health condition', array_column($r['safety_alerts'], 'title'));
    }

    public function test_rare_pulse_habit_lowers_not_raises_pulses(): void
    {
        $daily = $this->engine->calculateBasket($this->family(), ['pulse_frequency' => 'daily']);
        $rare = $this->engine->calculateBasket($this->family(), ['pulse_frequency' => 'rare']);
        $this->assertLessThan($daily['monthly_requirements']['pulses_kg'], $rare['monthly_requirements']['pulses_kg']);
    }

    public function test_few_home_cooked_meals_scale_the_basket_down(): void
    {
        $full = $this->engine->calculateBasket($this->family(), ['mealsCookedAtHome' => 3]);
        $few = $this->engine->calculateBasket($this->family(), ['mealsCookedAtHome' => 1]);
        $this->assertEqualsWithDelta($full['monthly_requirements']['cereals_kg'] * 0.85, $few['monthly_requirements']['cereals_kg'], 0.02);
    }

    public function test_millet_share_follows_the_guideline_and_staple_preference(): void
    {
        $r = $this->engine->calculateBasket($this->family());
        $share = $r['monthly_requirements']['millet_share'];
        $this->assertGreaterThanOrEqual(0.20, $share);
        $this->assertLessThanOrEqual(0.30, $share);

        $m = $this->engine->calculateBasket($this->family(), ['staplePreference' => 'millets']);
        $this->assertSame(0.4, $m['monthly_requirements']['millet_share']);
    }

    public function test_no_added_sugar_planned_for_very_young_children(): void
    {
        $this->assertSame(0.0, $this->engine->assessMember(['age' => 2])['daily_grams']['sugar']);
    }

    public function test_empty_family_returns_a_safe_empty_result(): void
    {
        $r = $this->engine->calculateBasket([]);
        $this->assertSame(0, $r['members_count']);
        $this->assertSame([], $r['members_assessed']);
    }
}
