<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Reads nutrition labels, normalises them to "per 100 g", and classifies products into food groups.
 *
 * Honesty rules
 *  - Values typed or OCR'd by an admin are stored as `label_unreviewed`: "taken from the label, not independently checked".
 *  - Only an admin who deliberately selects "packaging_verified" produces a verified record.
 *  - A product with no (or incomplete) label data is never given invented numbers. The basket may show a clearly
 *    labelled food-group ESTIMATE instead (config/family_pack.php), and the product is marked as such.
 */
class ProductNutritionService
{
    public const FOOD_GROUPS = [
        'pulses_legumes' => 'Pulses & Legumes',
        'grains_cereals' => 'Grains, Flours & Millets',
        'nuts_seeds'     => 'Nuts & Seeds',
        'cooking_oils'   => 'Cooking Oils',
        'ghee'           => 'Ghee',
        'sweeteners'     => 'Sweeteners',
        'spices'         => 'Spices',
        'salt'           => 'Salt',
        'other'          => 'Other Products',
    ];

    public const SOURCE_PACKAGING_VERIFIED = 'packaging_verified';   // an admin explicitly confirmed it against the pack
    public const SOURCE_LABEL_UNREVIEWED   = 'label_unreviewed';     // complete values entered / OCR'd, not independently checked
    public const SOURCE_IFCT_REFERENCE     = 'ifct_reference_estimate';
    public const SOURCE_NEEDS_REVIEW       = 'needs_review';         // incomplete or inconsistent label values
    public const SOURCE_UNVERIFIED         = 'unverified';           // no values

    private const SOURCE_LABELS = [
        self::SOURCE_PACKAGING_VERIFIED => 'Verified against packaging',
        self::SOURCE_LABEL_UNREVIEWED   => 'From product label (not independently reviewed)',
        self::SOURCE_IFCT_REFERENCE     => 'Reference estimate (food composition table)',
        self::SOURCE_NEEDS_REVIEW       => 'Incomplete label values (needs review)',
        self::SOURCE_UNVERIFIED         => 'Nutrition information not available',
    ];

    // ───────────────────────────── Label parsing ─────────────────────────────

    /**
     * Parse plain label text. Returns the values as printed (not yet per 100 g) plus the basis they refer to.
     *
     * @return array{values: array<string,float>, basis_amount: ?float, basis_unit: ?string, basis_assumed: bool, warnings: string[]}
     */
    public function parseNutritionText(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[\x{2013}\x{2014}]/u', '-', $text);
        // common OCR confusions inside numbers: "o.5g", "IOO g"
        $text = preg_replace('/\b[oO]\.([0-9]+)/', '0.$1', $text);
        $text = preg_replace('/\b[Il1]{1}[oO0]{2}\s*(g|gm|gms|gram|grams|ml)\b/i', '100 $1', $text);
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text)), fn ($l) => $l !== ''));
        $lower = array_map('strtolower', $lines);

        $values = [];
        $warnings = [];
        $num = '([0-9]+(?:[.,][0-9]+)?)';

        $first = function (string $labelRegex, array $exclude = [], ?string $numRegex = null) use ($lower, $num): ?float {
            foreach ($lower as $line) {
                if (! preg_match('/' . $labelRegex . '/', $line, $lm, PREG_OFFSET_CAPTURE)) {
                    continue;
                }
                foreach ($exclude as $ex) {
                    if (preg_match('/' . $ex . '/', $line)) {
                        continue 2;
                    }
                }
                $rest = substr($line, $lm[0][1] + strlen($lm[0][0]));
                $rest = preg_replace('/\([^)]*\)/', ' ', $rest); // drop "(g)", "(kcal)" etc.
                if (preg_match('/' . ($numRegex ?? $num) . '/', $rest, $m)) {
                    return (float) str_replace(',', '.', $m[1]);
                }
            }

            return null;
        };

        // Energy: prefer the number printed next to "kcal"; convert from kJ only if kcal is absent.
        foreach ($lower as $line) {
            if (! preg_match('/\b(energy|calories?|calorific|caloric)\b/', $line)) {
                continue;
            }
            if (preg_match('/' . $num . '\s*kcal/', $line, $m)) {
                $values['energy_kcal'] = (float) str_replace(',', '.', $m[1]);
            } elseif (preg_match('/' . $num . '\s*kj/', $line, $m) && ! str_contains($line, 'kcal')) {
                $values['energy_kcal'] = round(((float) str_replace(',', '.', $m[1])) / 4.184, 1);
            } else {
                $rest = preg_replace('/\([^)]*\)/', ' ', preg_replace('/^.*?(energy|calories?|calorific value|caloric value)/', '', $line, 1));
                if (preg_match('/' . $num . '/', $rest, $m)) {
                    $values['energy_kcal'] = (float) str_replace(',', '.', $m[1]);
                }
            }
            if (isset($values['energy_kcal'])) {
                break;
            }
        }

        if (($v = $first('\bprotein')) !== null) {
            $values['protein_g'] = $v;
        }
        if (($v = $first('\b(total\s+)?carbohydrates?\b|\bcarbs\b|\bcarbohydrate', ['of which', 'sugars?\b.*sugars?\b'])) !== null) {
            $values['carbs_g'] = $v;
        }
        // Total fat only: skip saturated / trans / mono / poly lines.
        if (($v = $first('\b(total\s+fat|fat|lipids?)\b', ['saturat', 'trans', 'mono', 'poly', 'unsaturat', 'of which'])) !== null) {
            $values['fat_g'] = $v;
        }
        if (($v = $first('\b(dietary\s+)?fib(?:re|er)\b')) !== null) {
            $values['fiber_g'] = $v;
        }
        // Sodium: mg unless g is stated
        foreach ($lower as $line) {
            if (preg_match('/\bsodium\b[^0-9]*' . $num . '\s*(mg|g)?/', $line, $m)) {
                $val = (float) str_replace(',', '.', $m[1]);
                $values['sodium_mg'] = (($m[2] ?? 'mg') === 'g') ? $val * 1000.0 : $val;
                break;
            }
        }

        // Basis: "per 100 g" wins; otherwise a stated serving size; otherwise assume 100 g and say so.
        $basisAmount = null;
        $basisUnit = null;
        $assumed = false;
        $joined = strtolower(implode("\n", $lines));
        if (preg_match('/per\s*100\s*(g|gm|gms|gram|grams|ml)\b/', $joined, $m)) {
            $basisAmount = 100.0;
            $basisUnit = str_starts_with($m[1], 'm') ? 'ml' : 'g';
        } elseif (preg_match('/(?:serving\s*size|per\s*serving|per)\s*[:=\-]?\s*(?:\(?\s*)?' . $num . '\s*(g|gm|gms|gram|grams|ml)\b/', $joined, $m)) {
            $basisAmount = (float) str_replace(',', '.', $m[1]);
            $basisUnit = str_starts_with($m[2], 'm') ? 'ml' : 'g';
        } else {
            $basisAmount = 100.0;
            $basisUnit = 'g';
            $assumed = ! empty($values);
            if ($assumed) {
                $warnings[] = 'No "per 100 g" or serving size found on the label text; values were assumed to be per 100 g.';
            }
        }

        return [
            'values'        => $values,
            'basis_amount'  => $basisAmount,
            'basis_unit'    => $basisUnit,
            'basis_assumed' => $assumed,
            'warnings'      => $warnings,
        ];
    }

    /**
     * Scale printed values to 100 g (or 100 ml) and drop values that cannot be right.
     * Missing nutrients stay null (they are never turned into zero).
     */
    public function normalizeTo100g(array $raw, float $basisAmount = 100.0): array
    {
        $scale = 100.0 / ($basisAmount > 0 ? $basisAmount : 100.0);
        $limits = ['energy_kcal' => 950, 'protein_g' => 100, 'carbs_g' => 100, 'fat_g' => 100, 'fiber_g' => 100, 'sodium_mg' => 40000];
        $out = [];
        foreach ($limits as $key => $max) {
            if (! isset($raw[$key]) || $raw[$key] === '' || $raw[$key] === null) {
                $out[$key] = null;
                continue;
            }
            $v = round((float) $raw[$key] * $scale, 1);
            $out[$key] = ($v < 0 || $v > $max) ? null : $v;
        }

        return $out;
    }

    /** Internal-consistency checks. Returns human-readable problems; empty means plausible. */
    public function plausibilityProblems(array $per100): array
    {
        $p = [];
        $prot = $per100['protein_g'] ?? null;
        $carb = $per100['carbs_g'] ?? null;
        $fat = $per100['fat_g'] ?? null;
        $fib = $per100['fiber_g'] ?? null;
        $kcal = $per100['energy_kcal'] ?? null;

        if ($prot !== null && $carb !== null && $fat !== null) {
            if ($prot + $carb + $fat > 102) {
                $p[] = 'Protein + carbohydrate + fat is more than 100 g per 100 g.';
            }
            if ($kcal !== null && $kcal > 0) {
                $calc = 4 * $prot + 4 * $carb + 9 * $fat;
                if ($calc > 0 && abs($calc - $kcal) / $kcal > 0.30) {
                    $p[] = "Energy ({$kcal} kcal) does not match the macronutrients (about " . round($calc) . ' kcal).';
                }
            }
        }
        if ($fib !== null && $carb !== null && $fib > $carb + 1) {
            $p[] = 'Dietary fibre is higher than carbohydrate.';
        }

        return $p;
    }

    // ───────────────────────────── Classification ─────────────────────────────

    private const NON_FOOD_OR_PROCESSED = '/\b(soap|shampoo|conditioner|lotion|cream|cosmetic|incense|agarbatti|candle|tablets?|capsules?|supplement|ashwagandha|tea|coffee|pickle|achar|chutney|jam|sauce|ketchup|biscuits?|cookies?|chips|namkeen|papad|noodles?|pasta|snack|chikki|laddu|candy|chocolate|juice|drink|malt|makhana|toothpaste|powder\s+mix)\b/';

    /**
     * Put any product into a food group. Matching is on whole words, so "Parboiled Rice" is not an oil
     * and "Dalia" is not a dal. Unrecognised products become "other" and are never put in a basket.
     */
    public function classifyFoodGroup(string $productName, ?string $categoryName = null, ?string $ingredients = null, array $per100g = []): array
    {
        $n = ' ' . trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower($productName))) . ' ';
        $n = trim($n);

        $result = fn (string $group, string $sub) => [
            'food_group'    => $group,
            'food_subgroup' => $sub,
            'group_label'   => self::FOOD_GROUPS[$group] ?? 'Other Products',
        ];
        $has = fn (string $regex) => (bool) preg_match('/\b(?:' . $regex . ')\b/', $n);

        if (preg_match(self::NON_FOOD_OR_PROCESSED, $n)) {
            return $result('other', 'processed_or_non_food');
        }
        if ($has('ghee|bilona')) {
            return $result('ghee', 'ghee');
        }
        if ($has('oil|tel|kachi ghani')) {
            $sub = 'vegetable_oil';
            foreach (['groundnut|peanut' => 'groundnut_oil', 'mustard|sarson' => 'mustard_oil', 'sesame|til|gingelly' => 'sesame_oil', 'sunflower' => 'sunflower_oil', 'olive' => 'olive_oil', 'coconut' => 'coconut_oil', 'safflower|kardi' => 'safflower_oil'] as $rx => $s) {
                if ($has($rx)) {
                    $sub = $s;
                    break;
                }
            }

            return $result('cooking_oils', $sub);
        }
        if ($has('salt|namak|sendha')) {
            return $result('salt', 'salt');
        }
        if ($has('jaggery|gud|sugar|khandsari|khandasari|honey|sweetener|mishri')) {
            return $result('sweeteners', $has('jaggery|gud') ? 'jaggery' : ($has('honey') ? 'honey' : 'sugar'));
        }
        if ($has('besan|gram flour')) {
            return $result('pulses_legumes', 'pulse_flour');
        }
        if ($has('turmeric|haldi|cumin|jeera|coriander|dhania|dhaniya|chilli|chili|mirch|pepper|cardamom|elaichi|cinnamon|dalchini|clove|cloves|loung|ajwain|carom|fennel|saunf|fenugreek|methi|asafoetida|hing|mustard|masala|spices?|nutmeg|jaiphal|bay leaf|tej patta|star anise|kalonji|nigella|poppy')) {
            return $result('spices', $has('turmeric|haldi') ? 'turmeric' : 'spice');
        }
        if ($has('quinoa')) {
            return $result('grains_cereals', 'other_grains');
        }
        if ($has('chia|flax|flaxseed|linseed|alsi|pumpkin|sunflower|sesame|til|watermelon|muskmelon|hemp|almonds?|badam|walnuts?|akhrot|cashews?|kaju|pista|pistachios?|peanuts?|groundnuts?|seeds?')) {
            return $result('nuts_seeds', $has('almonds?|badam|walnuts?|akhrot|cashews?|kaju|pista|pistachios?|peanuts?|groundnuts?') ? 'nuts' : 'seeds');
        }
        if ($has('dal|dals|daal|tuar|toor|tur|arhar|pigeon pea|moong|mung|green gram|chana|channa|bengal gram|chickpeas?|chick peas?|kabuli|masoor|lentils?|rajma|kidney beans?|urad|black gram|horse ?gram|kulthi|lobia|cowpea|moth|matki|panchratna|beans?|peas')) {
            $sub = 'other_pulses';
            foreach (['tuar|toor|tur|arhar|pigeon pea' => 'toor_arhar', 'moong|mung|green gram' => 'moong', 'chana|channa|bengal gram|chickpeas?|chick peas?|kabuli' => 'chana_bengal', 'masoor|lentils?|panchratna' => 'lentils', 'rajma|kidney beans?' => 'beans_rajma', 'urad|black gram' => 'urad', 'horse ?gram|kulthi' => 'horsegram'] as $rx => $s) {
                if ($has($rx)) {
                    $sub = $s;
                    break;
                }
            }

            return $result('pulses_legumes', $sub);
        }
        if ($has('ragi|finger|nachni|jowar|jawar|sorghum|bajra|pearl millet|kodo|kodro|kodra|foxtail|barnyard|little millet|proso|millets?|browntop|amaranth|rajgira|buckwheat|kuttu')) {
            return $result('grains_cereals', 'millets');
        }
        if ($has('rice|basmati|sona masoori|chawal|poha')) {
            return $result('grains_cereals', 'rice');
        }
        if ($has('atta|flour|wheat|khapli|emmer|spelt|multigrain|multi grain|suji|semolina|rava|sooji|dalia|daliya|maida|oats')) {
            return $result('grains_cereals', 'wheat_flours');
        }

        return $result('other', 'unclassified');
    }

    // ───────────────────────────── OCR ─────────────────────────────

    /**
     * Read a label image with Tesseract. Returns null (never throws) when Tesseract is missing, times out or finds nothing,
     * so the admin can type the values by hand.
     */
    public function extractTextFromImage(string $imagePath): ?string
    {
        $realPath = $imagePath;
        if (! is_file($realPath)) {
            $candidates = [storage_path('app/public/' . ltrim($imagePath, '/')), public_path('storage/' . ltrim($imagePath, '/'))];
            $realPath = null;
            foreach ($candidates as $c) {
                if (is_file($c)) {
                    $realPath = $c;
                    break;
                }
            }
            if ($realPath === null) {
                return null;
            }
        }

        $binary = config('family_pack.ocr.tesseract_path') ?: (new ExecutableFinder())->find('tesseract');
        if (! $binary) {
            Log::info('Nutrition OCR skipped: Tesseract is not installed.');

            return null;
        }

        try {
            $process = new Process([$binary, $realPath, 'stdout', '--psm', '6', '-l', 'eng']);
            $process->setTimeout((float) config('family_pack.ocr.timeout_seconds', 30));
            $process->run();
            if (! $process->isSuccessful()) {
                Log::warning('Nutrition OCR failed', ['exit' => $process->getExitCode()]);

                return null;
            }
            $text = trim($process->getOutput());

            return strlen($text) >= 10 ? $text : null;
        } catch (\Throwable $e) {
            Log::warning('Nutrition OCR error', ['error' => $e->getMessage()]);

            return null;
        }
    }

    // ───────────────────────────── Payload ─────────────────────────────

    /**
     * Build the structured record stored in products.nutritional_info.
     */
    public function buildStructuredPayload(
        string|array|null $nutritionInput,
        string $productName,
        ?string $categoryName = null,
        ?string $ingredients = null,
        ?string $forcedSource = null,
        bool $isFromPackagingOcr = false,
        ?string $packagingProofUrl = null,
        ?string $forcedFoodGroup = null
    ): array {
        $rawValues = [];
        $basisAmount = 100.0;
        $basisUnit = 'g';
        $warnings = [];
        $rawText = '';
        $ocrText = null;

        if ((empty($nutritionInput) || $isFromPackagingOcr) && ! empty($packagingProofUrl)) {
            $ocrText = $this->extractTextFromImage($packagingProofUrl);
            if (! empty($ocrText)) {
                $parsed = $this->parseNutritionText($ocrText);
                if (! empty($parsed['values'])) {
                    [$rawValues, $basisAmount, $basisUnit] = [$parsed['values'], $parsed['basis_amount'], $parsed['basis_unit']];
                    $warnings = array_merge($warnings, $parsed['warnings']);
                    $rawText = "OCR text:\n" . $ocrText;
                }
            } else {
                $warnings[] = 'The label image could not be read automatically. Please type the values.';
            }
        }

        if (empty($rawValues) && ! empty($nutritionInput)) {
            if (is_array($nutritionInput)) {
                $rawValues = isset($nutritionInput['per_100g']) && is_array($nutritionInput['per_100g']) ? $nutritionInput['per_100g'] : $nutritionInput;
                $rawText = json_encode($nutritionInput);
            } else {
                $rawText = trim($nutritionInput);
                $decoded = json_decode($rawText, true);
                if (is_array($decoded)) {
                    $rawValues = $decoded['per_100g'] ?? $decoded;
                } else {
                    $parsed = $this->parseNutritionText($rawText);
                    [$rawValues, $basisAmount, $basisUnit] = [$parsed['values'], $parsed['basis_amount'], $parsed['basis_unit']];
                    $warnings = array_merge($warnings, $parsed['warnings']);
                }
            }
        }

        $classification = $this->classifyFoodGroup($productName, $categoryName, $ingredients);
        if ($forcedFoodGroup && isset(self::FOOD_GROUPS[$forcedFoodGroup])) {
            $classification = ['food_group' => $forcedFoodGroup, 'food_subgroup' => $classification['food_subgroup'], 'group_label' => self::FOOD_GROUPS[$forcedFoodGroup]];
            $classification['manual_group'] = true;
        }

        $per100 = $this->normalizeTo100g(is_array($rawValues) ? $rawValues : [], $basisAmount);

        // Liquids: a label per 100 ml is converted with the density of oil/ghee; any other liquid is flagged for review.
        $unitNote = null;
        if ($basisUnit === 'ml' && array_filter($per100, fn ($v) => $v !== null)) {
            $density = config('family_pack.density_kg_per_l')[$classification['food_group']] ?? null;
            if ($density) {
                foreach ($per100 as $k => $v) {
                    $per100[$k] = $v === null ? null : round($v / $density, 1);
                }
                $unitNote = "Label was per 100 ml; converted to per 100 g using density {$density} kg/L.";
            } else {
                $warnings[] = 'Label values are per 100 ml and this product has no density rule; please check.';
            }
        }

        $hasValues = (bool) array_filter($per100, fn ($v) => $v !== null);
        $complete = $per100['protein_g'] !== null && $per100['carbs_g'] !== null && $per100['fat_g'] !== null;
        $problems = $hasValues ? $this->plausibilityProblems($per100) : [];
        $warnings = array_merge($warnings, $problems);
        if ($unitNote) {
            $warnings[] = $unitNote;
        }

        if (! $hasValues) {
            $source = self::SOURCE_UNVERIFIED;
        } elseif (! $complete || ! empty($problems)) {
            $source = self::SOURCE_NEEDS_REVIEW;
        } else {
            $source = self::SOURCE_LABEL_UNREVIEWED;
        }

        // A human may state that the values are verified (or a reference estimate). We never infer it.
        if (in_array($forcedSource, [self::SOURCE_PACKAGING_VERIFIED, self::SOURCE_IFCT_REFERENCE], true) && $hasValues) {
            $source = ($forcedSource === self::SOURCE_PACKAGING_VERIFIED && ! $complete) ? self::SOURCE_NEEDS_REVIEW : $forcedSource;
        }

        return [
            'source'          => $source,
            'verified_label'  => self::SOURCE_LABELS[$source],
            'verified'        => $source === self::SOURCE_PACKAGING_VERIFIED,
            'food_group'      => $classification['food_group'],
            'food_subgroup'   => $classification['food_subgroup'],
            'group_label'     => $classification['group_label'],
            'manual_group'    => $classification['manual_group'] ?? false,
            'review_required' => in_array($source, [self::SOURCE_NEEDS_REVIEW, self::SOURCE_UNVERIFIED], true),
            'serving_size'    => $basisAmount . $basisUnit,
            'per_100g'        => $per100,
            'warnings'        => array_values(array_unique($warnings)),
            'raw_input'       => Str::limit($rawText, 500),
            'packaging_proof' => $packagingProofUrl,
            'verified_at'     => $source === self::SOURCE_PACKAGING_VERIFIED ? now()->toIso8601String() : null,
        ];
    }

    // ───────────────────────────── Basket helpers ─────────────────────────────

    /**
     * Nutrition to use for a product in the basket.
     *
     * @return array{basis: 'label'|'estimate'|'none', per_100g: ?array, label: string, source: ?string}
     */
    public function nutritionForBasket(?array $nutritionalInfo, string $foodGroup, string $subGroup): array
    {
        $per = is_array($nutritionalInfo['per_100g'] ?? null) ? $nutritionalInfo['per_100g'] : null;
        $source = $nutritionalInfo['source'] ?? null;

        if ($per && ($per['protein_g'] ?? null) !== null && ($per['carbs_g'] ?? null) !== null && ($per['fat_g'] ?? null) !== null
            && in_array($source, [self::SOURCE_PACKAGING_VERIFIED, self::SOURCE_LABEL_UNREVIEWED, self::SOURCE_IFCT_REFERENCE], true)) {
            $kcal = $per['energy_kcal'] ?? round(4 * $per['protein_g'] + 4 * $per['carbs_g'] + 9 * $per['fat_g'], 1);

            return [
                'basis' => $source === self::SOURCE_IFCT_REFERENCE ? 'estimate' : 'label',
                'per_100g' => [
                    'energy_kcal' => (float) $kcal,
                    'protein_g'   => (float) $per['protein_g'],
                    'carbs_g'     => (float) $per['carbs_g'],
                    'fat_g'       => (float) $per['fat_g'],
                    'fiber_g'     => (float) ($per['fiber_g'] ?? 0),
                ],
                'label' => self::SOURCE_LABELS[$source],
                'source' => $source,
            ];
        }

        $refKey = match (true) {
            $foodGroup === 'pulses_legumes' && $subGroup !== 'pulse_flour' => 'pulses',
            $foodGroup === 'grains_cereals' && $subGroup === 'millets'     => 'millets',
            $foodGroup === 'grains_cereals' && in_array($subGroup, ['rice', 'wheat_flours'], true) => 'cereals',
            $foodGroup === 'nuts_seeds'                                    => 'nuts',
            in_array($foodGroup, ['cooking_oils', 'ghee'], true)           => 'fats',
            default                                                        => null,
        };
        $ref = $refKey ? config("family_pack.reference_per_100g.{$refKey}") : null;
        if ($ref) {
            return [
                'basis' => 'estimate',
                'per_100g' => array_intersect_key($ref, array_flip(['energy_kcal', 'protein_g', 'carbs_g', 'fat_g', 'fiber_g'])),
                'label' => 'Estimate: ' . $ref['source'],
                'source' => 'group_reference',
            ];
        }

        return ['basis' => 'none', 'per_100g' => null, 'label' => 'Nutrition information not available', 'source' => null];
    }
}
