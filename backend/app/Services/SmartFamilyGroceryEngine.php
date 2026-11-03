<?php

namespace App\Services;

class SmartFamilyGroceryEngine
{
    /**
     * ICMR-NIN 2024 Reference Baseline Table (g/day and daily nutrient targets per profile)
     */
    public const REFERENCE_TABLE = [
        'C13'    => ['label' => 'Toddler 1–3',       'sex' => 'any',    'min_age' => 1,  'max_age' => 3,   'activity' => 'any',       'cereals' => 120, 'pulses' => 40,  'nuts' => 10, 'oil' => 15, 'sugar' => 15, 'salt' => 3, 'energy' => 1010, 'protein' => 11.3, 'carbs' => 130, 'fat' => 27, 'fiber' => 15],
        'C46'    => ['label' => 'Child 4–6',         'sex' => 'any',    'min_age' => 4,  'max_age' => 6,   'activity' => 'any',       'cereals' => 160, 'pulses' => 60,  'nuts' => 15, 'oil' => 20, 'sugar' => 20, 'salt' => 5, 'energy' => 1360, 'protein' => 16.0, 'carbs' => 175, 'fat' => 30, 'fiber' => 18],
        'C79'    => ['label' => 'Child 7–9',         'sex' => 'any',    'min_age' => 7,  'max_age' => 9,   'activity' => 'any',       'cereals' => 200, 'pulses' => 65,  'nuts' => 20, 'oil' => 25, 'sugar' => 20, 'salt' => 5, 'energy' => 1700, 'protein' => 23.0, 'carbs' => 220, 'fat' => 35, 'fiber' => 22],
        'M1012'  => ['label' => 'Boy 10–12',         'sex' => 'male',   'min_age' => 10, 'max_age' => 12,  'activity' => 'any',       'cereals' => 280, 'pulses' => 90,  'nuts' => 30, 'oil' => 35, 'sugar' => 20, 'salt' => 5, 'energy' => 2220, 'protein' => 32.0, 'carbs' => 290, 'fat' => 40, 'fiber' => 28],
        'F1012'  => ['label' => 'Girl 10–12',        'sex' => 'female', 'min_age' => 10, 'max_age' => 12,  'activity' => 'any',       'cereals' => 250, 'pulses' => 85,  'nuts' => 30, 'oil' => 30, 'sugar' => 20, 'salt' => 5, 'energy' => 2060, 'protein' => 33.0, 'carbs' => 270, 'fat' => 38, 'fiber' => 28],
        'M1315'  => ['label' => 'Boy 13–15',         'sex' => 'male',   'min_age' => 13, 'max_age' => 15,  'activity' => 'any',       'cereals' => 390, 'pulses' => 130, 'nuts' => 40, 'oil' => 45, 'sugar' => 20, 'salt' => 5, 'energy' => 2860, 'protein' => 45.0, 'carbs' => 370, 'fat' => 45, 'fiber' => 35],
        'F1315'  => ['label' => 'Girl 13–15',        'sex' => 'female', 'min_age' => 13, 'max_age' => 15,  'activity' => 'any',       'cereals' => 300, 'pulses' => 100, 'nuts' => 35, 'oil' => 40, 'sugar' => 20, 'salt' => 5, 'energy' => 2400, 'protein' => 43.0, 'carbs' => 310, 'fat' => 40, 'fiber' => 30],
        'M1618'  => ['label' => 'Boy 16–18',         'sex' => 'male',   'min_age' => 16, 'max_age' => 18,  'activity' => 'any',       'cereals' => 450, 'pulses' => 150, 'nuts' => 50, 'oil' => 55, 'sugar' => 20, 'salt' => 5, 'energy' => 3320, 'protein' => 55.0, 'carbs' => 430, 'fat' => 50, 'fiber' => 40],
        'F1618'  => ['label' => 'Girl 16–18',        'sex' => 'female', 'min_age' => 16, 'max_age' => 18,  'activity' => 'any',       'cereals' => 315, 'pulses' => 105, 'nuts' => 40, 'oil' => 40, 'sugar' => 20, 'salt' => 5, 'energy' => 2500, 'protein' => 46.0, 'carbs' => 320, 'fat' => 40, 'fiber' => 32],
        'M1949S' => ['label' => 'Adult 19–49 Male (Sedentary)', 'sex' => 'male',   'min_age' => 19, 'max_age' => 49,  'activity' => 'sedentary', 'cereals' => 230, 'pulses' => 75,  'nuts' => 30, 'oil' => 30, 'sugar' => 20, 'salt' => 5, 'energy' => 2110, 'protein' => 54.0, 'carbs' => 275, 'fat' => 30, 'fiber' => 30],
        'M1949M' => ['label' => 'Adult 19–49 Male (Moderate)',  'sex' => 'male',   'min_age' => 19, 'max_age' => 49,  'activity' => 'moderate',  'cereals' => 320, 'pulses' => 105, 'nuts' => 40, 'oil' => 35, 'sugar' => 20, 'salt' => 5, 'energy' => 2710, 'protein' => 65.0, 'carbs' => 350, 'fat' => 35, 'fiber' => 40],
        'M1949H' => ['label' => 'Adult 19–49 Male (High Physical / Manual)', 'sex' => 'male', 'min_age' => 19, 'max_age' => 49, 'activity' => 'high', 'cereals' => 400, 'pulses' => 130, 'nuts' => 45, 'oil' => 45, 'sugar' => 20, 'salt' => 5, 'energy' => 3470, 'protein' => 78.0, 'carbs' => 450, 'fat' => 45, 'fiber' => 50],
        'F1949S' => ['label' => 'Adult 19–49 Female (Sedentary)','sex' => 'female', 'min_age' => 19, 'max_age' => 49,  'activity' => 'sedentary', 'cereals' => 180, 'pulses' => 60,  'nuts' => 25, 'oil' => 25, 'sugar' => 20, 'salt' => 5, 'energy' => 1660, 'protein' => 46.0, 'carbs' => 215, 'fat' => 25, 'fiber' => 25],
        'F1949M' => ['label' => 'Adult 19–49 Female (Moderate)', 'sex' => 'female', 'min_age' => 19, 'max_age' => 49,  'activity' => 'moderate',  'cereals' => 250, 'pulses' => 85,  'nuts' => 30, 'oil' => 30, 'sugar' => 20, 'salt' => 5, 'energy' => 2130, 'protein' => 55.0, 'carbs' => 275, 'fat' => 30, 'fiber' => 30],
        'F1949H' => ['label' => 'Adult 19–49 Female (High Physical / Manual)', 'sex' => 'female', 'min_age' => 19, 'max_age' => 49, 'activity' => 'high', 'cereals' => 320, 'pulses' => 105, 'nuts' => 35, 'oil' => 35, 'sugar' => 20, 'salt' => 5, 'energy' => 2720, 'protein' => 65.0, 'carbs' => 350, 'fat' => 38, 'fiber' => 38],
        'M5060'  => ['label' => 'Adult 50–59 Male', 'sex' => 'male',   'min_age' => 50, 'max_age' => 59,  'activity' => 'any',       'cereals' => 200, 'pulses' => 75,  'nuts' => 30, 'oil' => 25, 'sugar' => 20, 'salt' => 5, 'energy' => 2000, 'protein' => 54.0, 'carbs' => 260, 'fat' => 28, 'fiber' => 30],
        'F5060'  => ['label' => 'Adult 50–59 Female', 'sex' => 'female', 'min_age' => 50, 'max_age' => 59, 'activity' => 'any',       'cereals' => 165, 'pulses' => 70,  'nuts' => 25, 'oil' => 20, 'sugar' => 20, 'salt' => 5, 'energy' => 1600, 'protein' => 46.0, 'carbs' => 210, 'fat' => 25, 'fiber' => 25],
        'M60'    => ['label' => 'Older adult 60+ Male', 'sex' => 'male', 'min_age' => 60, 'max_age' => 120, 'activity' => 'any',       'cereals' => 170, 'pulses' => 75,  'nuts' => 30, 'oil' => 25, 'sugar' => 20, 'salt' => 5, 'energy' => 1750, 'protein' => 54.0, 'carbs' => 230, 'fat' => 25, 'fiber' => 28],
        'F60'    => ['label' => 'Older adult 60+ Female', 'sex' => 'female', 'min_age' => 60, 'max_age' => 120, 'activity' => 'any',   'cereals' => 140, 'pulses' => 70,  'nuts' => 30, 'oil' => 15, 'sugar' => 20, 'salt' => 5, 'energy' => 1450, 'protein' => 46.0, 'carbs' => 190, 'fat' => 22, 'fiber' => 25],
        'PREG'   => ['label' => 'Pregnant Female',  'sex' => 'female', 'min_age' => 19, 'max_age' => 49,  'activity' => 'any',       'cereals' => 220, 'pulses' => 85,  'nuts' => 40, 'oil' => 30, 'sugar' => 20, 'salt' => 5, 'energy' => 2010, 'protein' => 61.0, 'carbs' => 245, 'fat' => 35, 'fiber' => 28],
        'LACT'   => ['label' => 'Lactating Female', 'sex' => 'female', 'min_age' => 19, 'max_age' => 49,  'activity' => 'any',       'cereals' => 260, 'pulses' => 95,  'nuts' => 40, 'oil' => 35, 'sugar' => 20, 'salt' => 5, 'energy' => 2180, 'protein' => 65.0, 'carbs' => 255, 'fat' => 37, 'fiber' => 30],
    ];

    /**
     * Engine Multiplier Parameters
     */
    public const MONTH_DAYS = 30;
    public const FACTOR_HIGH_ACTIVITY_CEREAL = 1.15;
    public const FACTOR_ATHLETE_CEREAL = 1.25;
    public const FACTOR_HIGH_ACTIVITY_PROTEIN = 1.15;
    public const FACTOR_ATHLETE_PROTEIN = 1.25;
    public const FACTOR_ADOLESCENT_PROTEIN = 1.10;
    public const FACTOR_WEIGHT_GOAL_CEREAL = 0.90; // Only adult + weight-management goal + BMI >= 25
    public const FACTOR_SEDENTARY_CEREAL = 0.95;
    public const FACTOR_DIABETES_SUGAR = 0.50;
    public const FACTOR_HIGH_SUGAR_USE = 1.15;
    public const FACTOR_BP_SALT = 0.80;
    public const FACTOR_HEART_OIL = 0.90;
    public const FACTOR_RARE_PULSE = 1.15;
    public const FACTOR_RARE_NUTS = 1.15;
    public const FACTOR_LOW_HOME_COOKING = 0.85;
    public const PACK_BUFFER = 1.0;

    /**
     * Match a family member to the NIN 2024 Reference Profile
     */
    public function matchProfile(array $member): array
    {
        $age = max(1, (int) ($member['age'] ?? 25));
        $sex = strtolower($member['sex'] ?? 'male');
        $activity = strtolower($member['activity'] ?? 'moderate');
        $lifeStage = strtolower($member['life_stage'] ?? $member['lifeStage'] ?? 'none');

        // Pregnancy and Lactation overrides (for adult females 19-49)
        if ($sex === 'female' && $age >= 19 && $age <= 49) {
            if ($lifeStage === 'pregnant' || !empty($member['is_pregnant'])) {
                return ['key' => 'PREG', 'baseline' => self::REFERENCE_TABLE['PREG']];
            }
            if ($lifeStage === 'lactating' || !empty($member['is_lactating'])) {
                return ['key' => 'LACT', 'baseline' => self::REFERENCE_TABLE['LACT']];
            }
        }

        // Early childhood (1–3 years)
        if ($age < 4) {
            return ['key' => 'C13', 'baseline' => self::REFERENCE_TABLE['C13']];
        }

        // Children and Adolescents
        if ($age < 7) {
            return ['key' => 'C46', 'baseline' => self::REFERENCE_TABLE['C46']];
        }
        if ($age < 10) {
            return ['key' => 'C79', 'baseline' => self::REFERENCE_TABLE['C79']];
        }
        if ($age <= 12) {
            $key = $sex === 'female' ? 'F1012' : 'M1012';
            return ['key' => $key, 'baseline' => self::REFERENCE_TABLE[$key]];
        }
        if ($age <= 15) {
            $key = $sex === 'female' ? 'F1315' : 'M1315';
            return ['key' => $key, 'baseline' => self::REFERENCE_TABLE[$key]];
        }
        if ($age <= 18) {
            $key = $sex === 'female' ? 'F1618' : 'M1618';
            return ['key' => $key, 'baseline' => self::REFERENCE_TABLE[$key]];
        }

        // Adults 19-49
        if ($age <= 49) {
            $isSedentary = ($activity === 'sedentary');
            $isHigh = ($activity === 'high' || $activity === 'athlete' || $activity === 'heavy');
            if ($sex === 'female') {
                $key = $isSedentary ? 'F1949S' : ($isHigh ? 'F1949H' : 'F1949M');
            } else {
                $key = $isSedentary ? 'M1949S' : ($isHigh ? 'M1949H' : 'M1949M');
            }
            return ['key' => $key, 'baseline' => self::REFERENCE_TABLE[$key]];
        }

        // Adults 50-59
        if ($age <= 59) {
            $key = $sex === 'female' ? 'F5060' : 'M5060';
            return ['key' => $key, 'baseline' => self::REFERENCE_TABLE[$key]];
        }

        // Older Adults 60+
        $key = $sex === 'female' ? 'F60' : 'M60';
        return ['key' => $key, 'baseline' => self::REFERENCE_TABLE[$key]];
    }

    /**
     * Compute BMI for adult members
     */
    public function computeBmi(array $member): ?float
    {
        $age = (int) ($member['age'] ?? 0);
        $weightKg = (float) ($member['weightKg'] ?? $member['weight_kg'] ?? 0);

        // Height resolution in meters
        $heightM = 0;
        if (!empty($member['heightCm']) || !empty($member['height_cm'])) {
            $heightM = ((float) ($member['heightCm'] ?? $member['height_cm'])) / 100.0;
        } elseif (isset($member['heightFt']) || isset($member['heightIn'])) {
            $ft = (float) ($member['heightFt'] ?? 0);
            $in = (float) ($member['heightIn'] ?? 0);
            $totalInches = ($ft * 12) + $in;
            $heightM = ($totalInches * 2.54) / 100.0;
        }

        if ($heightM < 0.5 || $weightKg < 1 || $weightKg > 180) {
            return null;
        }

        return round($weightKg / ($heightM * $heightM), 1);
    }

    /**
     * Member-level quantity and nutrient assessment
     */
    public function assessMember(array $member): array
    {
        $profileMatch = $this->matchProfile($member);
        $profileKey = $profileMatch['key'];
        $base = $profileMatch['baseline'];

        $age = (int) ($member['age'] ?? 25);
        $activity = strtolower($member['activity'] ?? 'moderate');
        $goal = strtolower($member['goal'] ?? 'general');
        $bmi = $this->computeBmi($member);

        $cerealMult = 1.0;
        $proteinMult = 1.0;
        $energyMult = 1.0;

        $isAdult1949 = ($age >= 19 && $age <= 49);

        // Activity Adjustments
        // Note: For adults 19-49, ICMR-NIN 2024 provides distinct baseline profiles (Sedentary, Moderate, Heavy).
        // Therefore, we must NOT double-multiply for Sedentary, Moderate, or High adults 19-49.
        if ($activity === 'athlete') {
            $energyMult = 1.10;
            $cerealMult *= 1.10;
            $proteinMult *= 1.15;
        } elseif (!$isAdult1949) {
            // Apply activity scaling only to age groups without activity-stratified NIN tables (e.g. 50+, children)
            if ($activity === 'high' || $activity === 'heavy') {
                $energyMult = 1.10;
                $cerealMult *= 1.10;
                $proteinMult *= 1.10;
            } elseif ($activity === 'sedentary') {
                $energyMult = 0.95;
                $cerealMult *= 0.95;
            }
        }

        // Adolescent Growth Factor
        if ($age >= 10 && $age <= 18) {
            $proteinMult *= self::FACTOR_ADOLESCENT_PROTEIN;
        }

        // Weight Management Cereal Factor (Only for Adult with BMI >= 25 and weight-management goal)
        if ($age >= 19 && $bmi !== null && $bmi >= 25.0 && ($goal === 'weight_loss' || $goal === 'weight_management')) {
            $cerealMult *= self::FACTOR_WEIGHT_GOAL_CEREAL;
            $energyMult *= 0.92;
        }

        $dailyCereals = round($base['cereals'] * $cerealMult, 1);
        $dailyPulses  = round($base['pulses'] * $proteinMult, 1);
        $dailyNuts    = round($base['nuts'] * $proteinMult, 1);
        $dailyOil     = round($base['oil'], 1);
        $dailySugar   = round($base['sugar'], 1);
        $dailySalt    = round($base['salt'], 1);

        $dailyEnergy  = round($base['energy'] * $energyMult);
        $dailyProtein = round($base['protein'] * $proteinMult, 1);
        $dailyCarbs   = round($base['carbs'] * $cerealMult, 1);
        $dailyFat     = round($base['fat'], 1);
        $dailyFiber   = round($base['fiber'], 1);

        $bmiNote = $bmi !== null 
            ? "BMI: {$bmi}" 
            : ($age < 19 
                ? "Pediatric Growth Reference Applied" 
                : "ICMR-NIN 2024 Reference Standard Indian Adult (" . ($member['sex'] === 'female' ? '55 kg' : '65 kg') . ") applied.");

        return [
            'member_id'    => $member['id'] ?? null,
            'name'         => $member['name'] ?? 'Member',
            'age'          => $age,
            'sex'          => $member['sex'] ?? 'male',
            'profile_key'  => $profileKey,
            'profile_label'=> $base['label'],
            'bmi'          => $bmi,
            'bmi_note'     => $bmiNote,
            'activity'     => $activity,
            'goal'         => $goal,
            'health_flags' => [
                'diabetes' => !empty($member['diabetes']),
                'bp'       => !empty($member['bp']),
                'heart'    => !empty($member['heart']),
                'kidney'   => !empty($member['kidney']),
            ],
            'daily_grams' => [
                'cereals' => $dailyCereals,
                'pulses'  => $dailyPulses,
                'nuts'    => $dailyNuts,
                'oil'     => $dailyOil,
                'sugar'   => $dailySugar,
                'salt'    => $dailySalt,
            ],
            'daily_nutrients' => [
                'energy_kcal' => $dailyEnergy,
                'protein_g'   => $dailyProtein,
                'carbs_g'     => $dailyCarbs,
                'fat_g'       => $dailyFat,
                'fiber_g'     => $dailyFiber,
            ],
        ];
    }

    /**
     * Household aggregation second (NIN basis: 30 days + household adjustments)
     */
    public function calculateBasket(array $members, array $household = []): array
    {
        if (empty($members)) {
            return [
                'members_assessed' => [],
                'daily_household_grams' => [],
                'daily_household_nutrients' => [],
                'monthly_kg' => [],
                'monthly_nutrients' => [],
                'interpretation' => 'No family members entered.',
                'safety_alerts' => [],
            ];
        }

        $assessedMembers = [];
        $totDailyCereals = 0.0;
        $totDailyPulses  = 0.0;
        $totDailyNuts    = 0.0;
        $totDailyOil     = 0.0;
        $totDailySugar   = 0.0;
        $totDailySalt    = 0.0;

        $totDailyEnergy  = 0.0;
        $totDailyProtein = 0.0;
        $totDailyCarbs   = 0.0;
        $totDailyFat     = 0.0;
        $totDailyFiber   = 0.0;

        $hasDiabetes = false;
        $hasBp       = false;
        $hasHeart    = false;
        $hasKidney   = false;
        $hasAthlete  = false;
        $hasTeen     = false;
        $hasSenior   = false;

        foreach ($members as $m) {
            $assessed = $this->assessMember($m);
            $assessedMembers[] = $assessed;

            $totDailyCereals += $assessed['daily_grams']['cereals'];
            $totDailyPulses  += $assessed['daily_grams']['pulses'];
            $totDailyNuts    += $assessed['daily_grams']['nuts'];
            $totDailyOil     += $assessed['daily_grams']['oil'];
            $totDailySugar   += $assessed['daily_grams']['sugar'];
            $totDailySalt    += $assessed['daily_grams']['salt'];

            $totDailyEnergy  += $assessed['daily_nutrients']['energy_kcal'];
            $totDailyProtein += $assessed['daily_nutrients']['protein_g'];
            $totDailyCarbs   += $assessed['daily_nutrients']['carbs_g'];
            $totDailyFat     += $assessed['daily_nutrients']['fat_g'];
            $totDailyFiber   += $assessed['daily_nutrients']['fiber_g'];

            if (!empty($assessed['health_flags']['diabetes'])) $hasDiabetes = true;
            if (!empty($assessed['health_flags']['bp']))       $hasBp = true;
            if (!empty($assessed['health_flags']['heart']))    $hasHeart = true;
            if (!empty($assessed['health_flags']['kidney']))   $hasKidney = true;

            if ($assessed['activity'] === 'athlete' || $assessed['activity'] === 'high') $hasAthlete = true;
            if ($assessed['age'] >= 10 && $assessed['age'] <= 18) $hasTeen = true;
            if ($assessed['age'] >= 60) $hasSenior = true;
        }

        // Convert 30 days to monthly kg
        $monthlyCerealsKg = ($totDailyCereals * self::MONTH_DAYS) / 1000.0;
        $monthlyPulsesKg  = ($totDailyPulses  * self::MONTH_DAYS) / 1000.0;
        $monthlyNutsKg    = ($totDailyNuts    * self::MONTH_DAYS) / 1000.0;
        $monthlyOilLiters = ($totDailyOil     * self::MONTH_DAYS) / 1000.0;
        $monthlySugarKg   = ($totDailySugar   * self::MONTH_DAYS) / 1000.0;
        $monthlySaltKg    = ($totDailySalt    * self::MONTH_DAYS) / 1000.0;

        // Household Factor Adjustments
        $mealsCooked = (float) ($household['meals_cooked_at_home'] ?? $household['mealsCookedAtHome'] ?? 3);
        if ($mealsCooked < 2) {
            $factor = self::FACTOR_LOW_HOME_COOKING;
            $monthlyCerealsKg *= $factor;
            $monthlyPulsesKg  *= $factor;
            $monthlyNutsKg    *= $factor;
            $monthlyOilLiters *= $factor;
            $monthlySugarKg   *= $factor;
            $monthlySaltKg    *= $factor;
        }

        // Pulse frequency factor
        $pulseFreq = strtolower($household['pulse_frequency'] ?? $household['pulseFrequency'] ?? 'daily');
        if ($pulseFreq === 'rare' || $pulseFreq === 'low') {
            $monthlyPulsesKg *= self::FACTOR_RARE_PULSE;
        }

        // Nuts & Seeds frequency factor
        $nutsFreq = strtolower($household['nuts_frequency'] ?? $household['nutsFrequency'] ?? 'daily');
        if ($nutsFreq === 'rare' || $nutsFreq === 'infrequent') {
            $monthlyNutsKg *= self::FACTOR_RARE_NUTS;
        }

        // Added sugar use & Diabetes adjustments
        if ($hasDiabetes) {
            $monthlySugarKg *= self::FACTOR_DIABETES_SUGAR;
        } else {
            $sugarUse = strtolower($household['sugar_use'] ?? $household['sugarUse'] ?? 'moderate');
            if ($sugarUse === 'high') {
                $monthlySugarKg *= self::FACTOR_HIGH_SUGAR_USE;
            }
        }

        // BP Salt factor
        if ($hasBp) {
            $monthlySaltKg *= self::FACTOR_BP_SALT;
        }

        // Heart / Lipid oil factor
        if ($hasHeart) {
            $monthlyOilLiters *= self::FACTOR_HEART_OIL;
        }

        // Apply pantry pack buffer
        $buffer = (float) ($household['pack_buffer'] ?? self::PACK_BUFFER);
        $monthlyCerealsKg *= $buffer;
        $monthlyPulsesKg  *= $buffer;
        $monthlyNutsKg    *= $buffer;
        $monthlyOilLiters *= $buffer;
        $monthlySugarKg   *= $buffer;
        $monthlySaltKg    *= $buffer;

        // Construct Clinical Safety & Positioning Alerts
        $safetyAlerts = [];
        if ($hasKidney) {
            $safetyAlerts[] = [
                'type'    => 'critical',
                'title'   => 'Kidney Condition Detected – Professional Review Required',
                'message' => 'Kidney condition detected: grocery engine requires professional review. No automated disease-specific restriction. Please consult a qualified clinical nephrologist or dietitian before purchasing therapeutic meal plans.',
            ];
        }
        if ($hasDiabetes) {
            $safetyAlerts[] = [
                'type'    => 'info',
                'title'   => 'Sugar-Conscious Basket Adjustment',
                'message' => 'Diabetes flag detected: household added sugar purchase quantity has been automatically reduced by 50% while unpolished, fibre-rich whole grains and legumes are prioritized.',
            ];
        }
        if ($hasBp) {
            $safetyAlerts[] = [
                'type'    => 'info',
                'title'   => 'Salt-Mindful Planning (Sodium Notice)',
                'message' => 'Blood pressure flag detected: salt purchase recommendation reduced to support mindful intake (< 5g/day per adult). Note: Sendha Namak (Pink Rock Salt) is naturally unrefined but consists of ~98% sodium chloride and must still be used in moderation.',
            ];
        }
        if ($hasHeart) {
            $safetyAlerts[] = [
                'type'    => 'info',
                'title'   => 'Cardiovascular Fat Balance Guidance',
                'message' => 'Heart / lipid profile flag detected: cold-pressed unsaturated vegetable oils (Groundnut, Sesame, Sunflower, Olive) prioritized (85–90%); saturated fats (A2 Desi Cow Ghee) moderated to 10–15% of total added fats.',
            ];
        }

        // Customer-Facing Multi-Parameter Interpretation Report
        $priorities = [];
        if ($hasAthlete) $priorities[] = 'high-performance athletic activity';
        if ($hasTeen)    $priorities[] = 'adolescent growth stage';
        if ($hasDiabetes)$priorities[] = 'sugar-conscious routine';
        if ($hasHeart)   $priorities[] = 'cold-pressed healthy fat moderation';
        if ($hasSenior)  $priorities[] = 'nutrient-dense senior vitality';

        $priorityText = empty($priorities) 
            ? 'balanced whole-food nourishment'
            : implode(', ', $priorities);

        $memberCount = count($members);
        $interpretation = "Your Smart Family Grocery Basket is configured for {$memberCount} member(s) with focus on {$priorityText}. "
            . "Calculated using ICMR-NIN 2024 member-level guidelines: "
            . number_format($monthlyCerealsKg, 1) . " kg unpolished grains & flours, "
            . number_format($monthlyPulsesKg, 1) . " kg diverse pulses & legumes, "
            . number_format($monthlyOilLiters, 1) . " L cold-pressed oils & A2 ghee, and "
            . number_format($monthlyNutsKg, 1) . " kg raw seeds/nuts for the month.";

        return [
            'members_count' => $memberCount,
            'members_assessed' => $assessedMembers,
            'daily_household_grams' => [
                'cereals' => round($totDailyCereals, 1),
                'pulses'  => round($totDailyPulses, 1),
                'nuts'    => round($totDailyNuts, 1),
                'oil'     => round($totDailyOil, 1),
                'sugar'   => round($totDailySugar, 1),
                'salt'    => round($totDailySalt, 1),
            ],
            'daily_household_nutrients' => [
                'energy_kcal' => round($totDailyEnergy),
                'protein_g'   => round($totDailyProtein, 1),
                'carbs_g'     => round($totDailyCarbs, 1),
                'fat_g'       => round($totDailyFat, 1),
                'fiber_g'     => round($totDailyFiber, 1),
            ],
            'monthly_requirements' => [
                'cereals_kg' => round($monthlyCerealsKg, 2),
                'pulses_kg'  => round($monthlyPulsesKg, 2),
                'nuts_kg'    => round($monthlyNutsKg, 2),
                'oil_liters' => round($monthlyOilLiters, 2),
                'sugar_kg'   => round($monthlySugarKg, 2),
                'salt_kg'    => round($monthlySaltKg, 2),
            ],
            'monthly_nutrients' => [
                'energy_kcal' => round($totDailyEnergy * self::MONTH_DAYS),
                'protein_g'   => round($totDailyProtein * self::MONTH_DAYS, 1),
                'carbs_g'     => round($totDailyCarbs * self::MONTH_DAYS, 1),
                'fat_g'       => round($totDailyFat * self::MONTH_DAYS, 1),
                'fiber_g'     => round($totDailyFiber * self::MONTH_DAYS, 1),
            ],
            'interpretation' => $interpretation,
            'safety_alerts'  => $safetyAlerts,
            'positioning_disclaimer' => 'Smart Family Grocery Planner / Household Nutrition Basket. Not a medical treatment, clinical prescription, or disease diet.',
        ];
    }
}
