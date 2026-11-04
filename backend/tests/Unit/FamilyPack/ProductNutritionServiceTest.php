<?php

namespace Tests\Unit\FamilyPack;

use App\Services\ProductNutritionService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductNutritionServiceTest extends TestCase
{
    private ProductNutritionService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new ProductNutritionService();
    }

    // ── Classification ────────────────────────────────────────────────

    public static function classificationCases(): array
    {
        return [
            // The cases that used to be wrong
            'Parboiled Rice is rice, not oil'      => ['Parboiled Rice', 'grains_cereals', 'rice'],
            'Dalia is a grain, not a dal'          => ['Dalia Broken Wheat', 'grains_cereals', 'wheat_flours'],
            'Soap is not food'                     => ['Sandalwood Soap', 'other', null],
            'Pickle in oil is processed'           => ['Mango Pickle in Mustard Oil', 'other', null],
            'Tablets are not food'                 => ['Ashwagandha Gold Tablets Test', 'other', null],
            'Mustard seeds are a spice'            => ['Mustard Seeds', 'spices', null],
            'Ajwain seed is a spice'               => ['Ajwain / Carom Seed', 'spices', null],
            'Masala salt is salt'                  => ['Masala Rock Salt', 'salt', null],
            // Live-catalogue names
            'Toor dal'                             => ['Toor Dal/Arhar Dal/Split Pigeon Pea (Unpolished)', 'pulses_legumes', 'toor_arhar'],
            'Moong'                                => ['Green Gram Whole (Moong)', 'pulses_legumes', 'moong'],
            'Kabuli'                               => ['Kabuli Chana/Chickpea', 'pulses_legumes', 'chana_bengal'],
            'Rajma'                                => ['Rajma', 'pulses_legumes', 'beans_rajma'],
            'Urad is discovered'                   => ['Urad Dal Chilka / Black Gram Split', 'pulses_legumes', 'urad'],
            'Horsegram new product'                => ['Horse Gram', 'pulses_legumes', 'horsegram'],
            'Besan is a pulse flour'               => ['Besan/Gram Flour', 'pulses_legumes', 'pulse_flour'],
            'Ragi'                                 => ['Ragi Whole/Finger Millet', 'grains_cereals', 'millets'],
            'Ragi flour'                           => ['Finger (Ragi) Millet Flour', 'grains_cereals', 'millets'],
            'Bajra atta'                           => ['Bajra Atta / Pearl Millet Flour', 'grains_cereals', 'millets'],
            'Foxtail'                              => ['Foxtail Millet', 'grains_cereals', 'millets'],
            'Wheat flour'                          => ['Wheat Flour (Lokwan)', 'grains_cereals', 'wheat_flours'],
            'Multi grain flour'                    => ['Multi Grain Flour', 'grains_cereals', 'wheat_flours'],
            'Basmati'                              => ['Basmati Rice Brown', 'grains_cereals', 'rice'],
            'Quinoa'                               => ['White Quinoa Seeds (Processed)', 'grains_cereals', 'other_grains'],
            'Chia'                                 => ['Chia Seed', 'nuts_seeds', 'seeds'],
            'Black sesame'                         => ['Black Sesame (Til)', 'nuts_seeds', 'seeds'],
            'Almonds'                              => ['California Almonds', 'nuts_seeds', 'nuts'],
            'Ghee'                                 => ['A2 Gir Cow Ghee Bilona', 'ghee', null],
            'Groundnut oil'                        => ['Groundnut Oil ( Cold Pressed)', 'cooking_oils', 'groundnut_oil'],
            'Olive oil'                            => ['Olive Oil Extra Virgin', 'cooking_oils', 'olive_oil'],
            'Jaggery'                              => ['Jaggery Powder', 'sweeteners', 'jaggery'],
            'Sugar'                                => ['Off White Sugar Light', 'sweeteners', 'sugar'],
            'Sendha'                               => ['Pink Rock Salt / Sendha Namak (Light Pink)', 'salt', null],
            'Turmeric'                             => ['Turmeric Powder', 'spices', 'turmeric'],
            'Cumin'                                => ['Cumin (Jeera) / Cumin Whole', 'spices', null],
            'Unknown stays out of baskets'         => ['Mystery Item 7', 'other', null],
        ];
    }

    #[DataProvider('classificationCases')]
    public function test_classification(string $name, string $group, ?string $sub): void
    {
        $c = $this->svc->classifyFoodGroup($name);
        $this->assertSame($group, $c['food_group'], "{$name} -> {$c['food_group']}/{$c['food_subgroup']}");
        if ($sub !== null) {
            $this->assertSame($sub, $c['food_subgroup'], "{$name} subgroup");
        }
    }

    // ── Label parsing ─────────────────────────────────────────────────

    public function test_serving_size_line_does_not_distort_a_per_100g_table(): void
    {
        $p = $this->svc->parseNutritionText("Serving Size: 30g\nNutrition per 100g\nEnergy 345 kcal\nProtein 22 g\nCarbohydrate 60 g\nTotal Fat 1 g\nDietary Fibre 15 g");
        $this->assertSame(100.0, $p['basis_amount']);
        $this->assertSame(22.0, $p['values']['protein_g']);
        $this->assertSame(345.0, $p['values']['energy_kcal']);
        $this->assertSame(15.0, $p['values']['fiber_g']);
    }

    public function test_per_serving_label_is_scaled_to_per_100g(): void
    {
        $p = $this->svc->parseNutritionText("Serving size: 30 g\nEnergy 104 kcal\nProtein 6.6 g\nCarbohydrate 18 g\nFat 0.3 g");
        $this->assertSame(30.0, $p['basis_amount']);
        $n = $this->svc->normalizeTo100g($p['values'], $p['basis_amount']);
        $this->assertSame(22.0, $n['protein_g']);
        $this->assertSame(60.0, $n['carbs_g']);
        $this->assertSame(1.0, $n['fat_g']);
    }

    public function test_kj_and_kcal_on_one_line_uses_kcal(): void
    {
        $p = $this->svc->parseNutritionText("Per 100g\nEnergy 1450 kJ / 345 kcal\nProtein 22g\nCarbohydrate 60g\nFat 1g");
        $this->assertSame(345.0, $p['values']['energy_kcal']);
    }

    public function test_kj_only_is_converted(): void
    {
        $p = $this->svc->parseNutritionText("Per 100g\nEnergy 1450 kJ\nProtein 22g");
        $this->assertEqualsWithDelta(346.6, $p['values']['energy_kcal'], 0.1);
    }

    public function test_units_in_brackets_are_understood(): void
    {
        $p = $this->svc->parseNutritionText("Per 100g\nEnergy (kcal) 345\nProtein (g) 22\nCarbohydrate (g) 60\nTotal Fat (g) 1\nDietary Fibre (g) 15");
        $this->assertSame(['energy_kcal' => 345.0, 'protein_g' => 22.0, 'carbs_g' => 60.0, 'fat_g' => 1.0, 'fiber_g' => 15.0], $p['values']);
    }

    public function test_saturated_fat_is_never_read_as_total_fat(): void
    {
        $p = $this->svc->parseNutritionText("Per 100g\nSaturated Fat 8g\nTotal Fat 14g\nProtein 10g\nCarbohydrates 60g");
        $this->assertSame(14.0, $p['values']['fat_g']);
        $only = $this->svc->parseNutritionText("Per 100g\nSaturated Fat 8g\nProtein 10g");
        $this->assertArrayNotHasKey('fat_g', $only['values']);
    }

    public function test_sugars_sub_line_is_not_taken_as_carbohydrate(): void
    {
        $p = $this->svc->parseNutritionText("per 100g\nCarbohydrates of which sugars 3g\nProtein 10g\nFat 2g");
        $this->assertArrayNotHasKey('carbs_g', $p['values']);
    }

    public function test_sodium_in_g_is_converted_to_mg(): void
    {
        $this->assertSame(50.0, $this->svc->parseNutritionText("Per 100g\nSodium 0.05 g")['values']['sodium_mg']);
        $this->assertSame(17.0, $this->svc->parseNutritionText("Per 100g\nSodium 17 mg")['values']['sodium_mg']);
    }

    public function test_ocr_letter_confusions_are_tolerated(): void
    {
        $p = $this->svc->parseNutritionText("Per IOO g\nProtein o.5 g\nFat 1 g");
        $this->assertSame(100.0, $p['basis_amount']);
        $this->assertSame(0.5, $p['values']['protein_g']);
    }

    public function test_missing_basis_is_assumed_and_reported(): void
    {
        $p = $this->svc->parseNutritionText("Protein 22g\nCarbohydrate 60g\nFat 1g");
        $this->assertTrue($p['basis_assumed']);
        $this->assertNotEmpty($p['warnings']);
    }

    public function test_garbage_text_yields_nothing(): void
    {
        $this->assertSame([], $this->svc->parseNutritionText("hello world\nno numbers here")['values']);
    }

    // ── Normalisation and plausibility ────────────────────────────────

    public function test_missing_nutrients_stay_null_not_zero(): void
    {
        $n = $this->svc->normalizeTo100g(['protein_g' => 22]);
        $this->assertSame(22.0, $n['protein_g']);
        $this->assertNull($n['fat_g']);
        $this->assertNull($n['sodium_mg']);
    }

    public function test_impossible_values_are_rejected(): void
    {
        $n = $this->svc->normalizeTo100g(['protein_g' => 180, 'energy_kcal' => 2000, 'fat_g' => -3]);
        $this->assertNull($n['protein_g']);
        $this->assertNull($n['energy_kcal']);
        $this->assertNull($n['fat_g']);
    }

    public function test_inconsistent_label_is_flagged(): void
    {
        $this->assertNotEmpty($this->svc->plausibilityProblems(['protein_g' => 60, 'carbs_g' => 60, 'fat_g' => 10]));
        $this->assertNotEmpty($this->svc->plausibilityProblems(['energy_kcal' => 900, 'protein_g' => 10, 'carbs_g' => 60, 'fat_g' => 1]));
        $this->assertNotEmpty($this->svc->plausibilityProblems(['protein_g' => 10, 'carbs_g' => 20, 'fat_g' => 1, 'fiber_g' => 40]));
        $this->assertSame([], $this->svc->plausibilityProblems(['energy_kcal' => 345, 'protein_g' => 22, 'carbs_g' => 60, 'fat_g' => 1, 'fiber_g' => 15]));
    }

    // ── Honest status labels ──────────────────────────────────────────

    public function test_typed_label_is_marked_unreviewed_not_verified(): void
    {
        $p = $this->svc->buildStructuredPayload("Per 100g\nProtein 22g\nCarbohydrate 60g\nFat 1g", 'Toor Dal');
        $this->assertSame('label_unreviewed', $p['source']);
        $this->assertFalse($p['verified']);
        $this->assertStringNotContainsString('Certified', $p['verified_label']);
        $this->assertNull($p['verified_at']);
    }

    public function test_default_dropdown_values_do_not_force_a_verified_status(): void
    {
        foreach (['unverified', 'needs_review', 'label_unreviewed'] as $forced) {
            $p = $this->svc->buildStructuredPayload("Per 100g\nProtein 22g\nCarbohydrate 60g\nFat 1g", 'Toor Dal', null, null, $forced);
            $this->assertSame('label_unreviewed', $p['source'], "forced={$forced}");
        }
    }

    public function test_only_an_explicit_admin_choice_produces_a_verified_record(): void
    {
        $p = $this->svc->buildStructuredPayload("Per 100g\nProtein 22g\nCarbohydrate 60g\nFat 1g", 'Toor Dal', null, null, 'packaging_verified');
        $this->assertSame('packaging_verified', $p['source']);
        $this->assertTrue($p['verified']);
        $this->assertNotNull($p['verified_at']);
    }

    public function test_cannot_be_verified_without_complete_values(): void
    {
        $p = $this->svc->buildStructuredPayload("Per 100g\nProtein 22g", 'Toor Dal', null, null, 'packaging_verified');
        $this->assertSame('needs_review', $p['source']);
        $this->assertTrue($p['review_required']);
    }

    public function test_incomplete_label_needs_review(): void
    {
        $p = $this->svc->buildStructuredPayload("Per 100g\nProtein 22g\nFat 1g", 'Toor Dal');
        $this->assertSame('needs_review', $p['source']);
    }

    public function test_no_input_means_unverified_with_no_invented_numbers(): void
    {
        $p = $this->svc->buildStructuredPayload(null, 'Mystery Product');
        $this->assertSame('unverified', $p['source']);
        foreach ($p['per_100g'] as $k => $v) {
            $this->assertNull($v, "{$k} must stay null");
        }
    }

    public function test_label_per_serving_is_normalised_in_the_payload(): void
    {
        $p = $this->svc->buildStructuredPayload("Serving size: 30 g\nProtein 6.6g\nCarbohydrate 18g\nFat 0.3g", 'Toor Dal');
        $this->assertSame(22.0, $p['per_100g']['protein_g']);
        $this->assertSame('label_unreviewed', $p['source']);
    }

    public function test_oil_label_per_100ml_is_converted_with_density(): void
    {
        $p = $this->svc->buildStructuredPayload("Per 100 ml\nEnergy 824 kcal\nProtein 0 g\nCarbohydrate 0 g\nFat 91.5 g", 'Groundnut Oil');
        $this->assertSame(100.0, $p['per_100g']['fat_g']);
        $this->assertNotEmpty(array_filter($p['warnings'], fn ($w) => str_contains($w, 'density')));
    }

    public function test_manual_food_group_override_wins(): void
    {
        $p = $this->svc->buildStructuredPayload("Per 100g\nProtein 22g\nCarbohydrate 60g\nFat 1g", 'Mystery Item 7', null, null, null, false, null, 'pulses_legumes');
        $this->assertSame('pulses_legumes', $p['food_group']);
        $this->assertTrue($p['manual_group']);
    }

    // ── Nutrition used by the basket ──────────────────────────────────

    public function test_basket_uses_label_values_when_complete(): void
    {
        $info = $this->svc->buildStructuredPayload("Per 100g\nEnergy 345 kcal\nProtein 22g\nCarbohydrate 60g\nFat 1g\nFibre 15g", 'Toor Dal');
        $n = $this->svc->nutritionForBasket($info, 'pulses_legumes', 'toor_arhar');
        $this->assertSame('label', $n['basis']);
        $this->assertSame(22.0, $n['per_100g']['protein_g']);
    }

    public function test_basket_falls_back_to_a_labelled_group_estimate_never_a_silent_value(): void
    {
        $n = $this->svc->nutritionForBasket(null, 'pulses_legumes', 'urad');
        $this->assertSame('estimate', $n['basis']);
        $this->assertStringContainsString('Estimate', $n['label']);
        $this->assertSame(22.8, $n['per_100g']['protein_g']);   // ICMR-NIN DGI 2024 Table 1.3
    }

    public function test_basket_has_no_nutrition_for_groups_without_a_reference(): void
    {
        $this->assertSame('none', $this->svc->nutritionForBasket(null, 'spices', 'turmeric')['basis']);
        $this->assertSame('none', $this->svc->nutritionForBasket(null, 'sweeteners', 'sugar')['basis']);
        $this->assertNull($this->svc->nutritionForBasket(null, 'other', 'unclassified')['per_100g']);
    }

    public function test_incomplete_label_does_not_count_as_label_values(): void
    {
        $info = $this->svc->buildStructuredPayload("Per 100g\nProtein 22g", 'Toor Dal');
        $n = $this->svc->nutritionForBasket($info, 'pulses_legumes', 'toor_arhar');
        $this->assertSame('estimate', $n['basis']);
    }
}
