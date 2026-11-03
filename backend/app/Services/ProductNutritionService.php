<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductNutritionService
{
    /**
     * Standard food groups supported by the Prakruti Nutrition Engine
     */
    public const FOOD_GROUPS = [
        'pulses_legumes' => 'Pulses & Legumes',
        'grains_cereals' => 'Grains, Flours & Millets',
        'nuts_seeds'     => 'Healthy Seeds & Nuts',
        'cooking_oils'   => 'Cold-Pressed Cooking Oils',
        'ghee'           => 'Traditional Vedic Ghee',
        'sweeteners'     => 'Natural Sweeteners',
        'spices'         => 'Pure Spices',
        'salt'           => 'Mineral Salt',
        'other'          => 'Other Food Products',
    ];

    /**
     * Verification source constants
     */
    public const SOURCE_PACKAGING_VERIFIED = 'packaging_verified';
    public const SOURCE_IFCT_REFERENCE     = 'ifct_reference_estimate';
    public const SOURCE_NEEDS_REVIEW       = 'needs_review';
    public const SOURCE_UNVERIFIED         = 'unverified';

    /**
     * Parse plaintext nutrition labels (e.g. pasted by admin or extracted from packaging OCR)
     */
    public function parseNutritionText(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // OCR text sanitation for common character confusions:
        // 'o.5g' or 'O.5' -> '0.5', 'IOOg' -> '100g'
        $text = preg_replace('/\b[oO]\.([0-9]+)/', '0.$1', $text);
        $text = preg_replace('/\b[Il]00\s*(g|gm|gram|ml)/i', '100$1', $text);

        $extracted = [];

        // Match Energy / Calories: e.g. "Energy: 345 kcal", "Calories: 350", "Energy 360kcal"
        if (preg_match('/(?:energy|calories|caloric value)\s*[:=\-]?\s*([0-9]+(?:\.[0-9]+)?)\s*(?:kcal|cal|kj)?/i', $text, $m)) {
            $extracted['energy_kcal'] = (float) $m[1];
        }

        // Match Protein: e.g. "Protein: 22g", "Protein 22.5 g"
        if (preg_match('/protein\s*[:=\-]?\s*([0-9]+(?:\.[0-9]+)?)\s*g?/i', $text, $m)) {
            $extracted['protein_g'] = (float) $m[1];
        }

        // Match Carbohydrates: e.g. "Total Carbohydrates: 57g", "Carbohydrate: 63g", "Total Carbs: 60 g", "Carbs 65g"
        if (preg_match('/(?:total\s+carbohydrates?|carbohydrates?|total\s+carbs?|carbs)\s*[:=\-]?\s*([0-9]+(?:\.[0-9]+)?)\s*g?/i', $text, $m)) {
            $extracted['carbs_g'] = (float) $m[1];
        }

        // Match Total Fat: e.g. "Total Fat: 1.5g", "Total Fat: 0.5g", "Fat 1.0 g"
        if (preg_match('/(?:total\s+fat|fat|lipids)\s*[:=\-]?\s*([0-9]+(?:\.[0-9]+)?)\s*g?/i', $text, $m)) {
            $extracted['fat_g'] = (float) $m[1];
        }

        // Match Dietary Fibre: e.g. "Dietary Fiber: 18g", "Dietary Fibre: 15g", "Fiber: 12 g"
        if (preg_match('/(?:dietary\s+)?(?:fiber|fibre)\s*[:=\-]?\s*([0-9]+(?:\.[0-9]+)?)\s*g?/i', $text, $m)) {
            $extracted['fiber_g'] = (float) $m[1];
        }

        // Match Sodium: e.g. "Sodium: 17mg", "Sodium 0.05 g"
        if (preg_match('/sodium\s*[:=\-]?\s*([0-9]+(?:\.[0-9]+)?)\s*(mg|g)?/i', $text, $m)) {
            $val = (float) $m[1];
            $unit = strtolower($m[2] ?? 'mg');
            $extracted['sodium_mg'] = ($unit === 'g') ? $val * 1000.0 : $val;
        }

        // Match Serving Size: e.g. "Serving Size: 100g", "per 100g", "per 30 g"
        if (preg_match('/(?:serving\s*size|per)\s*[:=\-]?\s*([0-9]+(?:\.[0-9]+)?)\s*(g|gm|gram|ml)/i', $text, $m)) {
            $extracted['serving_size_val'] = (float) $m[1];
            $extracted['serving_size_unit'] = strtolower($m[2]);
        }

        return $extracted;
    }

    /**
     * Normalize nutrition values to a strict per 100g basis
     */
    public function normalizeTo100g(array $raw, float $servingSizeGrams = 100.0): array
    {
        if ($servingSizeGrams <= 0) {
            $servingSizeGrams = 100.0;
        }

        $scale = 100.0 / $servingSizeGrams;

        $energy = isset($raw['energy_kcal']) ? round((float) $raw['energy_kcal'] * $scale, 1) : null;
        $protein = isset($raw['protein_g']) ? round((float) $raw['protein_g'] * $scale, 1) : null;
        $carbs = isset($raw['carbs_g']) ? round((float) $raw['carbs_g'] * $scale, 1) : null;
        $fat = isset($raw['fat_g']) ? round((float) $raw['fat_g'] * $scale, 1) : null;
        $fiber = isset($raw['fiber_g']) ? round((float) $raw['fiber_g'] * $scale, 1) : null;
        $sodium = isset($raw['sodium_mg']) ? round((float) $raw['sodium_mg'] * $scale, 1) : 0.0;

        // Bounds validation
        if ($energy !== null && ($energy < 0 || $energy > 950)) {
            $energy = null;
        }
        if ($protein !== null && ($protein < 0 || $protein > 100)) {
            $protein = null;
        }
        if ($carbs !== null && ($carbs < 0 || $carbs > 100)) {
            $carbs = null;
        }
        if ($fat !== null && ($fat < 0 || $fat > 100)) {
            $fat = null;
        }
        if ($fiber !== null && ($fiber < 0 || $fiber > 100)) {
            $fiber = null;
        }
        if ($sodium < 0 || $sodium > 50000) {
            $sodium = 0.0;
        }

        return [
            'energy_kcal' => $energy,
            'protein_g'   => $protein,
            'carbs_g'     => $carbs,
            'fat_g'       => $fat,
            'fiber_g'     => $fiber,
            'sodium_mg'   => $sodium,
        ];
    }

    /**
     * Dynamically classify any product into canonical food group and subgroup
     */
    public function classifyFoodGroup(string $productName, ?string $categoryName = null, ?string $ingredients = null, array $per100g = []): array
    {
        $haystack = strtolower("{$productName} " . ($categoryName ?? '') . " " . ($ingredients ?? ''));

        // 1. Ghee (Pure animal fat / Vedic A2 bilona)
        if (preg_match('/(?:ghee|bilona|a2 gir cow ghee)/i', $haystack)) {
            return [
                'food_group'    => 'ghee',
                'food_subgroup' => 'a2_cow_ghee',
                'group_label'   => 'Traditional Vedic Ghee',
            ];
        }

        // 2. Cooking Oils
        if (preg_match('/(?:oil|tel|kachi ghani|wood pressed oil|cold pressed.*oil)/i', $haystack)) {
            $sub = 'vegetable_oils';
            if (str_contains($haystack, 'groundnut') || str_contains($haystack, 'peanut')) {
                $sub = 'groundnut_oil';
            } elseif (str_contains($haystack, 'mustard') || str_contains($haystack, 'sarson')) {
                $sub = 'mustard_oil';
            } elseif (str_contains($haystack, 'sesame') || str_contains($haystack, 'til')) {
                $sub = 'sesame_oil';
            } elseif (str_contains($haystack, 'sunflower')) {
                $sub = 'sunflower_oil';
            } elseif (str_contains($haystack, 'olive')) {
                $sub = 'olive_oil';
            } elseif (str_contains($haystack, 'coconut')) {
                $sub = 'coconut_oil';
            }
            return [
                'food_group'    => 'cooking_oils',
                'food_subgroup' => $sub,
                'group_label'   => 'Cold-Pressed Cooking Oils',
            ];
        }

        // 3. Healthy Seeds & Nuts
        if (preg_match('/(?:chia|flax|pumpkin|sunflower seed|sesame seed|watermelon seed|muskmelon seed|hemp seed|almond|walnut|cashew|pista|seed)/i', $haystack)) {
            $isNut = preg_match('/(?:almond|walnut|cashew|pista|tree nut)/i', $haystack);
            return [
                'food_group'    => 'nuts_seeds',
                'food_subgroup' => $isNut ? 'tree_nuts' : 'seeds',
                'group_label'   => 'Healthy Seeds & Nuts',
            ];
        }

        // 4. Pulses & Legumes (Dals, Grams, Lentils, Beans)
        if (preg_match('/(?:dal|tuar|toor|arhar|pigeon pea|moong|green gram|chana|bengal gram|chickpea|kabuli|masoor|pink lentil|rajma|kidney bean|urad|black gram|horsegram|kulthi|lobia|cowpea|moth|matki|panchratna|mix dal|besan)/i', $haystack)) {
            $sub = 'other_pulses';
            if (preg_match('/(?:tuar|toor|arhar|pigeon pea)/i', $haystack)) $sub = 'toor_arhar';
            elseif (preg_match('/(?:moong|green gram)/i', $haystack)) $sub = 'moong';
            elseif (preg_match('/(?:chana|bengal gram|chickpea|kabuli)/i', $haystack)) $sub = 'chana_bengal';
            elseif (preg_match('/(?:masoor|pink lentil|panchratna)/i', $haystack)) $sub = 'lentils_masoor';
            elseif (preg_match('/(?:rajma|kidney bean)/i', $haystack)) $sub = 'beans_rajma';

            return [
                'food_group'    => 'pulses_legumes',
                'food_subgroup' => $sub,
                'group_label'   => 'Pulses & Legumes',
            ];
        }

        // 5. Millets & Ancient Grains
        if (preg_match('/(?:ragi|finger millet|jowar|jawar|sorghum|bajra|pearl millet|kodo|foxtail|barnyard|little millet|proso)/i', $haystack)) {
            return [
                'food_group'    => 'grains_cereals',
                'food_subgroup' => 'millets',
                'group_label'   => 'Ancient Prebiotic Millets',
            ];
        }

        // 6. Rice varieties
        if (preg_match('/(?:rice|basmati|sona masoori|poha|chawal)/i', $haystack)) {
            return [
                'food_group'    => 'grains_cereals',
                'food_subgroup' => 'rice',
                'group_label'   => 'Traditional Rice',
            ];
        }

        // 7. Flours & Wheats
        if (preg_match('/(?:flour|atta|wheat|khapli|emmer|spelt|multigrain|suji|rava|dalia)/i', $haystack)) {
            return [
                'food_group'    => 'grains_cereals',
                'food_subgroup' => 'wheat_flours',
                'group_label'   => 'Whole Flours & Wheats',
            ];
        }

        // 8. Natural Sweeteners
        if (preg_match('/(?:jaggery|gud|sugar|khandasari|bilona khand|sweetener|honey)/i', $haystack)) {
            return [
                'food_group'    => 'sweeteners',
                'food_subgroup' => str_contains($haystack, 'jaggery') ? 'jaggery' : 'raw_sugar',
                'group_label'   => 'Natural Sweeteners',
            ];
        }

        // 9. Spices
        if (preg_match('/(?:turmeric|haldi|curcumin|cumin|jeera|coriander|dhaniya|chilli|mirch|pepper|cardamom|elaichi|cinnamon|clove|masala|spice)/i', $haystack)) {
            return [
                'food_group'    => 'spices',
                'food_subgroup' => str_contains($haystack, 'turmeric') ? 'turmeric' : 'spice_powders',
                'group_label'   => 'Pure Spices',
            ];
        }

        // 10. Salt
        if (preg_match('/(?:salt|namak|sendha|rock salt|himalayan)/i', $haystack)) {
            return [
                'food_group'    => 'salt',
                'food_subgroup' => 'rock_salt',
                'group_label'   => 'Mineral Salt',
            ];
        }

        // Fallback by Category Name if available
        $cat = strtolower($categoryName ?? '');
        if (str_contains($cat, 'pulse') || str_contains($cat, 'cereal & pulse')) {
            return ['food_group' => 'pulses_legumes', 'food_subgroup' => 'other_pulses', 'group_label' => 'Pulses & Legumes'];
        }
        if (str_contains($cat, 'millet')) {
            return ['food_group' => 'grains_cereals', 'food_subgroup' => 'millets', 'group_label' => 'Ancient Millets'];
        }
        if (str_contains($cat, 'seed')) {
            return ['food_group' => 'nuts_seeds', 'food_subgroup' => 'seeds', 'group_label' => 'Healthy Seeds'];
        }
        if (str_contains($cat, 'oil') || str_contains($cat, 'ghee')) {
            return ['food_group' => 'cooking_oils', 'food_subgroup' => 'vegetable_oils', 'group_label' => 'Cooking Oils & Ghee'];
        }
        if (str_contains($cat, 'rice') || str_contains($cat, 'flour')) {
            return ['food_group' => 'grains_cereals', 'food_subgroup' => 'wheat_flours', 'group_label' => 'Grains & Flours'];
        }
        if (str_contains($cat, 'spice')) {
            return ['food_group' => 'spices', 'food_subgroup' => 'spice_powders', 'group_label' => 'Indian Spices'];
        }
        if (str_contains($cat, 'sweetener')) {
            return ['food_group' => 'sweeteners', 'food_subgroup' => 'raw_sugar', 'group_label' => 'Natural Sweeteners'];
        }

        return [
            'food_group'    => 'other',
            'food_subgroup' => 'general',
            'group_label'   => 'Other Food Products',
        ];
    }

    /**
     * Extract plaintext from nutrition packaging image using cross-platform multi-tier engine
     * Tier 1: System Tesseract OCR (Linux & Windows)
     * Tier 2: Python OCR Runner (Linux & Windows)
     * Tier 3: Windows Media OCR (Windows only)
     * Tier 4: Graceful fallback (Marks for manual review, never fabricates values)
     */
    public function extractTextFromImage(string $imagePath): ?string
    {
        $realPath = $imagePath;
        if (!file_exists($realPath)) {
            $storagePath = storage_path('app/public/' . ltrim($imagePath, '/'));
            if (file_exists($storagePath)) {
                $realPath = $storagePath;
            } elseif (file_exists(public_path('storage/' . ltrim($imagePath, '/')))) {
                $realPath = public_path('storage/' . ltrim($imagePath, '/'));
            } else {
                return null;
            }
        }

        // Tier 1: Check for system tesseract binary
        $tesseractCheck = (PHP_OS_FAMILY === 'Windows') ? 'where tesseract 2>NUL' : 'which tesseract 2>/dev/null';
        $tesseractPath = trim((string) @shell_exec($tesseractCheck));
        if (!empty($tesseractPath) && !str_contains($tesseractPath, 'not found') && !str_contains($tesseractPath, 'INFO:')) {
            $cmd = "tesseract " . escapeshellarg($realPath) . " stdout --oem 1 -l eng 2>" . (PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null');
            $output = @shell_exec($cmd);
            if (!empty($output) && strlen(trim($output)) > 10) {
                return trim($output);
            }
        }

        // Tier 2: Cross-platform Python OCR runner (Python is available on both Windows and Linux production)
        $pyScript = __DIR__ . '/ocr_extract.py';
        if (file_exists($pyScript)) {
            $pyBin = (PHP_OS_FAMILY === 'Windows') ? 'python' : 'python3';
            $pyCmd = "{$pyBin} " . escapeshellarg($pyScript) . " " . escapeshellarg($realPath);
            $pyOutput = @shell_exec($pyCmd);
            if (!empty($pyOutput) && str_contains($pyOutput, 'EXTRACTED_TEXT_START')) {
                if (preg_match('/EXTRACTED_TEXT_START\s*(.*?)\s*EXTRACTED_TEXT_END/s', $pyOutput, $m)) {
                    $res = trim($m[1]);
                    if (!empty($res)) {
                        return $res;
                    }
                }
            }
        }

        // Tier 3: Windows Media OCR (Windows only)
        if (PHP_OS_FAMILY === 'Windows') {
            $scriptPath = __DIR__ . '/ocr_extract.ps1';
            if (file_exists($scriptPath)) {
                $cmd = "powershell -ExecutionPolicy Bypass -File " . escapeshellarg($scriptPath) . " -ImagePath " . escapeshellarg($realPath);
                $output = @shell_exec($cmd);
                if (!empty($output)) {
                    if (preg_match('/EXTRACTED_TEXT_START\s*(.*?)\s*EXTRACTED_TEXT_END/s', $output, $m)) {
                        return trim($m[1]);
                    }
                    return trim($output);
                }
            }
        }

        return null;
    }

    /**
     * Build structured nutritional_info payload from input data
     */
    public function buildStructuredPayload(
        string|array|null $nutritionInput,
        string $productName,
        ?string $categoryName = null,
        ?string $ingredients = null,
        ?string $forcedSource = null,
        bool $isFromPackagingOcr = false,
        ?string $packagingProofUrl = null
    ): ?array {
        $rawValues = [];
        $servingSize = '100g';
        $servingGrams = 100.0;
        $rawText = '';

        // Auto-extract from packaging label image if image proof is available and text is missing or OCR requested
        if ((empty($nutritionInput) || $isFromPackagingOcr) && !empty($packagingProofUrl)) {
            $extracted = $this->extractTextFromImage($packagingProofUrl);
            if (!empty($extracted)) {
                $parsedOcr = $this->parseNutritionText($extracted);
                if (!empty($parsedOcr)) {
                    $rawValues = $parsedOcr;
                    $rawText = "OCR Extracted:\n" . $extracted;
                    $isFromPackagingOcr = true;
                    if (!empty($parsedOcr['serving_size_val'])) {
                        $servingGrams = (float) $parsedOcr['serving_size_val'];
                        $servingSize = "{$servingGrams}" . ($parsedOcr['serving_size_unit'] ?? 'g');
                    }
                }
            }
        }

        if (empty($nutritionInput) && empty($rawValues)) {
            // Unverified product: Do NOT fabricate fallback nutrition values!
            $classification = $this->classifyFoodGroup($productName, $categoryName, $ingredients);
            return [
                'source'           => self::SOURCE_UNVERIFIED,
                'verified_label'   => 'Pending Nutritional Lab Verification',
                'food_group'       => $classification['food_group'],
                'food_subgroup'    => $classification['food_subgroup'],
                'group_label'      => $classification['group_label'],
                'review_required'  => true,
                'serving_size'     => '100g',
                'per_100g'         => [
                    'energy_kcal' => null,
                    'protein_g'   => null,
                    'carbs_g'     => null,
                    'fat_g'       => null,
                    'fiber_g'     => null,
                    'sodium_mg'   => 0,
                ],
                'raw_input'        => null,
                'packaging_proof'  => $packagingProofUrl,
            ];
        }

        if (empty($rawValues)) {
            if (is_array($nutritionInput)) {
                if (isset($nutritionInput['per_100g']) && is_array($nutritionInput['per_100g'])) {
                    $rawValues = $nutritionInput['per_100g'];
                } else {
                    $rawValues = $nutritionInput;
                }
                $servingSize = $nutritionInput['serving_size'] ?? '100g';
                $rawText = json_encode($nutritionInput);
            } elseif (is_string($nutritionInput)) {
                $rawText = trim($nutritionInput);
                // Check if JSON
                $decoded = json_decode($rawText, true);
                if (is_array($decoded)) {
                    $rawValues = $decoded['per_100g'] ?? $decoded;
                    $servingSize = $decoded['serving_size'] ?? '100g';
                } else {
                    // Key-value text
                    $parsed = $this->parseNutritionText($rawText);
                    $rawValues = $parsed;
                    if (!empty($parsed['serving_size_val'])) {
                        $servingGrams = (float) $parsed['serving_size_val'];
                        $servingSize = "{$servingGrams}" . ($parsed['serving_size_unit'] ?? 'g');
                    }
                }
            }
        }

        $normalized = $this->normalizeTo100g($rawValues, $servingGrams);
        $classification = $this->classifyFoodGroup($productName, $categoryName, $ingredients, $normalized);

        // Determine verification status
        $hasKeyNutrients = ($normalized['protein_g'] !== null && $normalized['carbs_g'] !== null && $normalized['fat_g'] !== null);

        $source = self::SOURCE_UNVERIFIED;
        $reviewRequired = false;

        if ($forcedSource) {
            $source = $forcedSource;
        } elseif ($isFromPackagingOcr || !empty($packagingProofUrl)) {
            if ($hasKeyNutrients) {
                $source = self::SOURCE_PACKAGING_VERIFIED;
            } else {
                $source = self::SOURCE_NEEDS_REVIEW;
                $reviewRequired = true;
            }
        } elseif ($hasKeyNutrients) {
            $source = self::SOURCE_PACKAGING_VERIFIED;
        } else {
            $source = self::SOURCE_NEEDS_REVIEW;
            $reviewRequired = true;
        }

        // Safety rule: Do not mark packaging_verified unless key nutrients are verified
        if ($source === self::SOURCE_PACKAGING_VERIFIED && !$hasKeyNutrients) {
            $source = self::SOURCE_NEEDS_REVIEW;
            $reviewRequired = true;
        }

        $verifiedLabel = match ($source) {
            self::SOURCE_PACKAGING_VERIFIED => 'Prakruti Certified Packaging Fact',
            self::SOURCE_IFCT_REFERENCE     => 'ICMR-NIN / IFCT 2024 Reference Standard',
            self::SOURCE_NEEDS_REVIEW       => 'Extracted Packaging Fact (Pending Review)',
            default                         => 'Pending Nutritional Lab Verification',
        };

        return [
            'source'           => $source,
            'verified_label'   => $verifiedLabel,
            'food_group'       => $classification['food_group'],
            'food_subgroup'    => $classification['food_subgroup'],
            'group_label'      => $classification['group_label'],
            'review_required'  => $reviewRequired,
            'serving_size'     => $servingSize,
            'per_100g'         => $normalized,
            'raw_input'        => Str::limit($rawText, 500),
            'packaging_proof'  => $packagingProofUrl,
            'verified_at'      => ($source === self::SOURCE_PACKAGING_VERIFIED) ? now()->toIso8601String() : null,
        ];
    }
}

