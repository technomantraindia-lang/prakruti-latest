<?php

/**
 * Family Pack — reference data and tunable planning rules.
 *
 * Every number carries a `basis`:
 *   official  = copied from the ICMR-NIN document named in `source`
 *   derived   = computed from official numbers by the rule written next to it
 *   estimate  = planning assumption of this software (NOT an ICMR figure)
 *
 * Sources
 *   RDA2020 : ICMR-NIN "Nutrient Requirements for Indians (RDA 2020)", short note Tables 1a, 2a
 *             https://www.nin.res.in/rdabook/brief_note.pdf
 *   DGI2024 : ICMR-NIN "Dietary Guidelines for Indians 2024", Tables 1.3 and 1.6
 *             https://nin.res.in/dietaryguidelines/pdfjs/locale/DGI_2024.pdf
 *
 * Nothing here is a medical prescription. It is grocery-planning reference data.
 */

return [

    'days' => 30,

    /*
     * Label OCR. Tesseract must be installed on the server (apt/yum/dnf install tesseract). If it is not,
     * OCR is skipped and the admin types the values.
     */
    'ocr' => [
        'tesseract_path'  => env('TESSERACT_PATH'),
        'timeout_seconds' => 30,
    ],

    /*
     * One row per reference profile.
     * energy kcal/day, protein g/day, others are raw food g/day (DGI2024 Table 1.6).
     * `fat` is VISIBLE fat/cooking oil g/day (not total dietary fat).
     */
    'profiles' => [
        'C13'    => ['label' => 'Child 1–3 yrs',  'sex' => 'any',    'ages' => [1, 3],   'activity' => 'any', 'energy' => 1070, 'protein' => 12.5, 'cereals' => 100, 'pulses' => 50,  'nuts' => 10, 'fat' => 20, 'basis' => 'official', 'source' => 'RDA2020 T1a/T2a; DGI2024 T1.6'],
        'C46'    => ['label' => 'Child 4–6 yrs',  'sex' => 'any',    'ages' => [4, 6],   'activity' => 'any', 'energy' => 1360, 'protein' => 15.9, 'cereals' => 160, 'pulses' => 60,  'nuts' => 15, 'fat' => 20, 'basis' => 'official', 'source' => 'RDA2020 T1a/T2a; DGI2024 T1.6'],
        'C79'    => ['label' => 'Child 7–9 yrs',  'sex' => 'any',    'ages' => [7, 9],   'activity' => 'any', 'energy' => 1700, 'protein' => 23.3, 'cereals' => 200, 'pulses' => 65,  'nuts' => 20, 'fat' => 25, 'basis' => 'official', 'source' => 'RDA2020 T1a/T2a; DGI2024 T1.6'],
        'M1012'  => ['label' => 'Boy 10–12 yrs',  'sex' => 'male',   'ages' => [10, 12], 'activity' => 'any', 'energy' => 2220, 'protein' => 31.8, 'cereals' => 280, 'pulses' => 90,  'nuts' => 30, 'fat' => 35, 'basis' => 'official', 'source' => 'RDA2020 T1a/T2a; DGI2024 T1.6'],
        'F1012'  => ['label' => 'Girl 10–12 yrs', 'sex' => 'female', 'ages' => [10, 12], 'activity' => 'any', 'energy' => 2060, 'protein' => 32.8, 'cereals' => 250, 'pulses' => 85,  'nuts' => 30, 'fat' => 30, 'basis' => 'official', 'source' => 'RDA2020 T1a/T2a; DGI2024 T1.6'],
        'M1315'  => ['label' => 'Boy 13–15 yrs',  'sex' => 'male',   'ages' => [13, 15], 'activity' => 'any', 'energy' => 2860, 'protein' => 44.9, 'cereals' => 390, 'pulses' => 130, 'nuts' => 40, 'fat' => 45, 'basis' => 'official', 'source' => 'RDA2020 T1a/T2a; DGI2024 T1.6'],
        'F1315'  => ['label' => 'Girl 13–15 yrs', 'sex' => 'female', 'ages' => [13, 15], 'activity' => 'any', 'energy' => 2400, 'protein' => 43.2, 'cereals' => 300, 'pulses' => 100, 'nuts' => 35, 'fat' => 40, 'basis' => 'official', 'source' => 'RDA2020 T1a/T2a; DGI2024 T1.6'],
        'M1618'  => ['label' => 'Boy 16–18 yrs',  'sex' => 'male',   'ages' => [16, 18], 'activity' => 'any', 'energy' => 3320, 'protein' => 55.4, 'cereals' => 450, 'pulses' => 150, 'nuts' => 50, 'fat' => 55, 'basis' => 'official', 'source' => 'RDA2020 T1a/T2a; DGI2024 T1.6'],
        'F1618'  => ['label' => 'Girl 16–18 yrs', 'sex' => 'female', 'ages' => [16, 18], 'activity' => 'any', 'energy' => 2500, 'protein' => 46.2, 'cereals' => 315, 'pulses' => 105, 'nuts' => 40, 'fat' => 40, 'basis' => 'official', 'source' => 'RDA2020 T1a/T2a; DGI2024 T1.6'],

        // Adults. RDA2020 gives sedentary / moderate / heavy work and no upper age limit.
        'M_S'    => ['label' => 'Adult man – sedentary',   'sex' => 'male',   'ages' => [19, 59], 'activity' => 'sedentary', 'energy' => 2110, 'protein' => 54.0, 'cereals' => 260, 'pulses' => 85,  'nuts' => 40, 'fat' => 30, 'basis' => 'official', 'source' => 'RDA2020 T1a/T2a; DGI2024 T1.6'],
        'M_M'    => ['label' => 'Adult man – moderate',    'sex' => 'male',   'ages' => [19, 59], 'activity' => 'moderate',  'energy' => 2710, 'protein' => 54.0, 'cereals' => 370, 'pulses' => 120, 'nuts' => 45, 'fat' => 40, 'basis' => 'official', 'source' => 'RDA2020 T1a/T2a; DGI2024 T1.6'],
        'M_H'    => ['label' => 'Adult man – heavy work',  'sex' => 'male',   'ages' => [19, 59], 'activity' => 'high',      'energy' => 3470, 'protein' => 54.0, 'cereals' => 474, 'pulses' => 154, 'nuts' => 50, 'fat' => 50, 'basis' => 'derived', 'source' => 'Energy: RDA2020 T1a. Food groups: DGI2024 T1.6 moderate row × (3470/2710), nuts and fat capped at DGI maximum of 50'],
        'F_S'    => ['label' => 'Adult woman – sedentary', 'sex' => 'female', 'ages' => [19, 59], 'activity' => 'sedentary', 'energy' => 1660, 'protein' => 45.7, 'cereals' => 190, 'pulses' => 60,  'nuts' => 30, 'fat' => 25, 'basis' => 'official', 'source' => 'RDA2020 T1a/T2a; DGI2024 T1.6'],
        'F_M'    => ['label' => 'Adult woman – moderate',  'sex' => 'female', 'ages' => [19, 59], 'activity' => 'moderate',  'energy' => 2130, 'protein' => 45.7, 'cereals' => 270, 'pulses' => 90,  'nuts' => 40, 'fat' => 30, 'basis' => 'official', 'source' => 'RDA2020 T1a/T2a; DGI2024 T1.6'],
        'F_H'    => ['label' => 'Adult woman – heavy work', 'sex' => 'female', 'ages' => [19, 59], 'activity' => 'high',     'energy' => 2720, 'protein' => 45.7, 'cereals' => 345, 'pulses' => 115, 'nuts' => 50, 'fat' => 38, 'basis' => 'derived', 'source' => 'Energy: RDA2020 T1a. Food groups: DGI2024 T1.6 moderate row × (2720/2130), nuts capped at DGI maximum of 50'],

        // Older adults. RDA2020 has no separate row; DGI2024 Table 1.6 does (menu energy, not an RDA).
        'M60'    => ['label' => 'Older man 60+',   'sex' => 'male',   'ages' => [60, 120], 'activity' => 'any', 'energy' => 1740, 'protein' => 54.0, 'cereals' => 170, 'pulses' => 75, 'nuts' => 30, 'fat' => 25, 'basis' => 'official', 'source' => 'DGI2024 T1.6 (energy is menu energy); protein RDA2020 T2a'],
        'F60'    => ['label' => 'Older woman 60+', 'sex' => 'female', 'ages' => [60, 120], 'activity' => 'any', 'energy' => 1530, 'protein' => 45.7, 'cereals' => 140, 'pulses' => 70, 'nuts' => 30, 'fat' => 15, 'basis' => 'official', 'source' => 'DGI2024 T1.6 (energy is menu energy); protein RDA2020 T2a'],

        // Women only. Energy = sedentary woman + extra (RDA2020 T1a), protein = RDA + extra (T2a), foods = DGI2024 T1.6.
        'PREG'   => ['label' => 'Pregnant woman',          'sex' => 'female', 'ages' => [15, 49], 'activity' => 'any', 'energy' => 2010, 'protein' => 55.2, 'cereals' => 220, 'pulses' => 75, 'nuts' => 40, 'fat' => 30, 'basis' => 'derived', 'source' => '1660 + 350 kcal; 45.7 + 9.5 g (2nd trimester); DGI2024 T1.6'],
        'LACT'   => ['label' => 'Breast-feeding woman (0–6 months)', 'sex' => 'female', 'ages' => [15, 49], 'activity' => 'any', 'energy' => 2260, 'protein' => 62.6, 'cereals' => 260, 'pulses' => 85, 'nuts' => 40, 'fat' => 35, 'basis' => 'derived', 'source' => '1660 + 600 kcal; 45.7 + 16.9 g; DGI2024 T1.6'],
    ],

    /*
     * Smooth the step between "adult" and "older adult": ages 55–59 move gradually toward the 60+ row.
     * The ICMR documents give no 50–59 category, so ages 19–54 use the adult rows unchanged.
     * basis = estimate.
     */
    'blend_to_older' => ['from_age' => 55, 'to_age' => 60],

    /*
     * ICMR has no activity split at 60+. A modest uplift for active older adults. basis = estimate.
     */
    'older_activity_factor' => ['sedentary' => 1.0, 'moderate' => 1.10, 'high' => 1.20],

    /*
     * Rules derived from the nutrient targets.
     */
    'derived' => [
        'fat_energy_share'        => 0.25,   // total fat = 25% of energy (RDA2020 range 20–30%). basis = estimate
        'fibre_g_per_1000_kcal'   => 25,     // DGI2024 text: "around 25 g/1000 Kcal"
        'added_sugar_energy_share' => 0.05,  // DGI2024: restrict added sugar to <5% of energy
        'no_added_sugar_below_age' => 4,     // DGI2024 advises no added sugar for infants; planning is conservative for under-4s
        'salt_g_per_day'          => 5,      // DGI2024: maximum 5 g/day
        'salt_g_per_day_young'    => 3,      // under 4 years. basis = estimate
        'spices_g_per_day'        => 6,      // basis = estimate
    ],

    /*
     * Health-condition planning adjustments. MODEST, configurable, and applied only to the person who has the flag.
     * These are software planning rules, not medical advice. A dietitian should approve them before launch.
     * When one food is touched by two conditions the single most protective factor applies (no stacking).
     */
    'health' => [
        'diabetes' => ['sugar_factor' => 0.75, 'fibre_boost' => true],
        'bp'       => ['salt_factor' => 0.90],
        'heart'    => ['fat_factor' => 0.95, 'ghee_share' => 0.12],
        'kidney'   => [], // warning only: no automatic changes
    ],

    'ghee_share_default' => 0.25, // share of visible fat taken as ghee. basis = estimate

    /*
     * Household habit factors (basis = estimate).
     */
    'habits' => [
        'low_home_cooking'  => ['max_meals' => 2, 'factor' => 0.85], // fewer than 2 home-cooked meals/day
        'pulses_rare'       => 0.85,
        'nuts_rare'         => 0.85,
        'sugar_low'         => 0.80,
    ],

    /*
     * Cereals: DGI2024 Table 1.6 footnote — 20–30% of cereals should be millets for adults, 20% for children up to 10.
     */
    'millets' => ['adult_share' => 0.25, 'child_share' => 0.20, 'child_max_age' => 10, 'max_share' => 0.45],

    'staple_preference' => [
        // share of the NON-millet cereal that goes to wheat flours vs rice
        'balanced' => ['wheat' => 0.55, 'rice' => 0.45],
        'wheat'    => ['wheat' => 0.75, 'rice' => 0.25],
        'rice'     => ['wheat' => 0.25, 'rice' => 0.75],
        'millets'  => ['wheat' => 0.55, 'rice' => 0.45, 'millet_share' => 0.40],
    ],

    /*
     * Densities used ONLY to convert between litres and kilograms for oils and ghee.
     */
    'density_kg_per_l' => ['cooking_oils' => 0.915, 'ghee' => 0.91],

    /*
     * Basket builder limits (basis = estimate).
     */
    'basket' => [
        'max_products' => ['pulses_legumes' => 6, 'cooking_oils' => 2, 'millets' => 3, 'wheat_flours' => 2, 'rice' => 2, 'nuts_seeds' => 5, 'spices' => 5],
        'undershoot_tolerance' => 0.08,  // a pack plan may fall up to 8% below the need
        'min_line_share'       => 0.04,  // drop products that would receive <4% of a group (redistribute)
        // grams per person per month a single product may supply (concentrated seeds etc.), by keyword
        'per_person_month_cap_g' => ['chia' => 150, 'flax' => 300, 'default' => 600],
        'core_spices' => ['turmeric', 'haldi', 'chilli', 'mirch', 'coriander', 'dhania', 'cumin', 'jeera'],
    ],

    /*
     * Food-group average nutrition per 100 g RAW (DGI2024 Table 1.3). Used only when a product has no usable label
     * data, and always shown to customers as an ESTIMATE, never as the product's own label.
     */
    'reference_per_100g' => [
        'cereals' => ['energy_kcal' => 343, 'protein_g' => 9.3,  'fat_g' => 1.2,  'carbs_g' => 72, 'fiber_g' => 6,  'source' => 'ICMR-NIN DGI 2024 Table 1.3 (cereals)'],
        'millets' => ['energy_kcal' => 330, 'protein_g' => 9.9,  'fat_g' => 2.7,  'carbs_g' => 65, 'fiber_g' => 7,  'source' => 'ICMR-NIN DGI 2024 Table 1.3 (millets)'],
        'pulses'  => ['energy_kcal' => 323, 'protein_g' => 22.8, 'fat_g' => 3.0,  'carbs_g' => 49, 'fiber_g' => 12, 'source' => 'ICMR-NIN DGI 2024 Table 1.3 (pulses)'],
        'nuts'    => ['energy_kcal' => 516, 'protein_g' => 17.5, 'fat_g' => 41.3, 'carbs_g' => 18, 'fiber_g' => 9,  'source' => 'ICMR-NIN DGI 2024 Table 1.3 (nuts and oilseeds)'],
        'fats'    => ['energy_kcal' => 900, 'protein_g' => 0.0,  'fat_g' => 100.0, 'carbs_g' => 0, 'fiber_g' => 0,  'source' => 'Pure fat: 9 kcal per gram (Atwater factor)'],
    ],
];
