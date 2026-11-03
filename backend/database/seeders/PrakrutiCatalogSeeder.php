<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PrakrutiCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $brand = Brand::firstOrCreate(
            ['slug' => 'prakruti-organic'],
            ['name' => 'Prakruti Organic', 'status' => 'active']
        );

        $categoriesList = [
            ['name' => 'Cereal & Pulses', 'slug' => 'cereal-pulses', 'description' => 'Daily essentials that nourish your body with pure plant-based protein and fiber.'],
            ['name' => 'Healthy Seeds', 'slug' => 'healthy-seeds', 'description' => 'Tiny powerhouses packed with nutrients for a healthier you.'],
            ['name' => 'Indian Spices', 'slug' => 'indian-spices', 'description' => 'Aromatic spices that add flavor, warmth and wellness to every meal.'],
            ['name' => 'Millets', 'slug' => 'millets', 'description' => 'Ancient grains for modern lifestyles - wholesome, hearty and naturally gluten-free.'],
            ['name' => 'Oils & Ghee', 'slug' => 'oils-ghee', 'description' => 'Pure, cold-pressed oils and traditional ghee for a healthy you.'],
            ['name' => 'Rice & Flours', 'slug' => 'rice-flours', 'description' => 'Wholesome grains and flours for everyday cooking and baking.'],
            ['name' => 'Sweeteners', 'slug' => 'sweeteners', 'description' => 'Natural sweeteners for mindful indulgence.'],
        ];

        $categoryMap = [];
        foreach ($categoriesList as $catData) {
            $categoryMap[$catData['name']] = Category::firstOrCreate(
                ['slug' => $catData['slug']],
                ['name' => $catData['name'], 'description' => $catData['description'], 'status' => 'active']
            );
        }

        $featured = [
            'Masoor Dal / Pink Lentil Split',
            'Panchratna Dal/Mix Dal ( 5 Pulses )',
            'Chia Seed',
            'Turmeric Powder',
            'A2 Gir Cow Ghee Bilona',
        ];

        $products = [
            ['Masoor Dal / Pink Lentil Split', 'Cereal & Pulses', 120],
            ['Panchratna Dal/Mix Dal ( 5 Pulses )', 'Cereal & Pulses', 140],
            ['Rajma Jammu', 'Cereal & Pulses', 150],
            ['Split Bengal Gram (Chana Dal)', 'Cereal & Pulses', 110],
            ['Tuar Dal/Arhar/Pigeon Pea', 'Cereal & Pulses', 120],
            ['Urad Dal Chilka / Black Gram Split', 'Cereal & Pulses', 130],
            ['White Chickpeas (Kabuli) Dollar Big Size', 'Cereal & Pulses', 160],
            ['Green Gram Whole (Moong)', 'Cereal & Pulses', 120],
            ['Brown Chick Peas Small Size [Desi Chana]', 'Cereal & Pulses', 110],
            ['Chia Seed', 'Healthy Seeds', 180],
            ['Flax Seed', 'Healthy Seeds', 110],
            ['Pumpkin Seeds', 'Healthy Seeds', 170],
            ['Sunflower Seed', 'Healthy Seeds', 120],
            ['Ajwain / Carom Seed', 'Indian Spices', 110],
            ['Black Pepper Whole / Kali Mirch', 'Indian Spices', 130],
            ['Black Sesame (Til)', 'Indian Spices', 110],
            ['Cinnamon Stick / Dal Chini', 'Indian Spices', 110],
            ['Clove Whole / Loung', 'Indian Spices', 120],
            ['Coriander Powder', 'Indian Spices', 110],
            ['Cumin (Jeera) / Cumin Whole', 'Indian Spices', 110],
            ['Cumin Powder / Jeera Powder', 'Indian Spices', 110],
            ['Pink Rock Salt / Sendha Namak (Light Pink)', 'Indian Spices', 55],
            ['Red Chilli Powder', 'Indian Spices', 110],
            ['Turmeric Powder', 'Indian Spices', 110],
            ['White Sesame (Til) / Natural', 'Indian Spices', 110],
            ['Bajra Whole / Pearl Millet', 'Millets', 75],
            ['Finger (Ragi) Millet', 'Millets', 75],
            ['Jawar/Sorghum Whole', 'Millets', 85],
            ['Kodo (Kodro) Millet', 'Millets', 80],
            ['Little Millet', 'Millets', 80],
            ['White Quinoa Seeds (Processed)', 'Millets', 120],
            ['A2 Gir Cow Ghee Bilona', 'Oils & Ghee', 750],
            ['Black Sesame Oil (Cold Pressed)', 'Oils & Ghee', 290],
            ['Groundnut Oil ( Cold Pressed)', 'Oils & Ghee', 220],
            ['Sunflower Oil [Cold Pressed]', 'Oils & Ghee', 180],
            ['Olive Oil Extra Virgin', 'Oils & Ghee', 650],
            ['Bajra Atta / Pearl Millet Flour', 'Rice & Flours', 75],
            ['Basmati Rice Brown', 'Rice & Flours', 150],
            ['Basmati Rice White', 'Rice & Flours', 130],
            ['Sona Masoori - White Rice', 'Rice & Flours', 120],
            ['Chana Besan / Bengal Gram Flour', 'Rice & Flours', 90],
            ['Finger (Ragi) Millet Flour', 'Rice & Flours', 80],
            ['Jowar Atta/Sorghum Flour', 'Rice & Flours', 75],
            ['Wheat Flour', 'Rice & Flours', 60],
            ['Multi Grain Flour', 'Rice & Flours', 100],
            ['Jaggery Powder', 'Sweeteners', 110],
            ['Off White Sugar Light', 'Sweeteners', 90],
            ['Raw Sugar / Khandasari Sugar (Brown)', 'Sweeteners', 110],
        ];

        // Authoritative Nutritional Data per 100g (Packaging lab verification + IFCT 2024 Reference Standard)
        $nutritionProfiles = [
            'Masoor Dal / Pink Lentil Split' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 350.0, 'protein_g' => 26.0, 'carbs_g' => 60.0, 'fat_g' => 1.0, 'fiber_g' => 20.0, 'sodium_mg' => 0.0],
            ],
            'Panchratna Dal/Mix Dal ( 5 Pulses )' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 344.0, 'protein_g' => 23.0, 'carbs_g' => 58.0, 'fat_g' => 2.0, 'fiber_g' => 2.0, 'sodium_mg' => 40.0],
            ],
            'Rajma Jammu' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 346.0, 'protein_g' => 23.0, 'carbs_g' => 60.0, 'fat_g' => 1.3, 'fiber_g' => 4.8, 'sodium_mg' => 0.0],
            ],
            'Split Bengal Gram (Chana Dal)' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 340.0, 'protein_g' => 11.0, 'carbs_g' => 73.0, 'fat_g' => 0.4, 'fiber_g' => 8.0, 'sodium_mg' => 5.0],
            ],
            'Tuar Dal/Arhar/Pigeon Pea' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 345.0, 'protein_g' => 22.0, 'carbs_g' => 63.0, 'fat_g' => 1.0, 'fiber_g' => 15.0, 'sodium_mg' => 17.0],
            ],
            'Urad Dal Chilka / Black Gram Split' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 347.0, 'protein_g' => 24.0, 'carbs_g' => 60.0, 'fat_g' => 1.4, 'fiber_g' => 0.9, 'sodium_mg' => 40.0],
            ],
            'White Chickpeas (Kabuli) Dollar Big Size' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 347.0, 'protein_g' => 19.0, 'carbs_g' => 60.0, 'fat_g' => 6.0, 'fiber_g' => 17.0, 'sodium_mg' => 14.0],
            ],
            'Green Gram Whole (Moong)' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 375.0, 'protein_g' => 14.0, 'carbs_g' => 64.0, 'fat_g' => 7.1, 'fiber_g' => 7.1, 'sodium_mg' => 14.0],
            ],
            'Brown Chick Peas Small Size [Desi Chana]' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 340.0, 'protein_g' => 11.0, 'carbs_g' => 73.0, 'fat_g' => 0.4, 'fiber_g' => 8.0, 'sodium_mg' => 5.0],
            ],
            'Chia Seed' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 486.0, 'protein_g' => 16.5, 'carbs_g' => 42.1, 'fat_g' => 30.7, 'fiber_g' => 34.4, 'sodium_mg' => 16.0],
            ],
            'Flax Seed' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 534.0, 'protein_g' => 18.3, 'carbs_g' => 28.9, 'fat_g' => 42.2, 'fiber_g' => 27.3, 'sodium_mg' => 30.0],
            ],
            'Pumpkin Seeds' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 559.0, 'protein_g' => 30.2, 'carbs_g' => 10.7, 'fat_g' => 49.1, 'fiber_g' => 6.0, 'sodium_mg' => 7.0],
            ],
            'Sunflower Seed' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 584.0, 'protein_g' => 20.8, 'carbs_g' => 20.0, 'fat_g' => 51.5, 'fiber_g' => 8.6, 'sodium_mg' => 9.0],
            ],
            'Ajwain / Carom Seed' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 305.0, 'protein_g' => 16.0, 'carbs_g' => 43.0, 'fat_g' => 25.0, 'fiber_g' => 21.0, 'sodium_mg' => 30.0],
            ],
            'Black Pepper Whole / Kali Mirch' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 251.0, 'protein_g' => 10.4, 'carbs_g' => 64.0, 'fat_g' => 3.3, 'fiber_g' => 25.3, 'sodium_mg' => 20.0],
            ],
            'Black Sesame (Til)' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 563.0, 'protein_g' => 18.3, 'carbs_g' => 23.4, 'fat_g' => 49.7, 'fiber_g' => 11.8, 'sodium_mg' => 11.0],
            ],
            'Cinnamon Stick / Dal Chini' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 247.0, 'protein_g' => 4.0, 'carbs_g' => 80.6, 'fat_g' => 1.2, 'fiber_g' => 53.1, 'sodium_mg' => 10.0],
            ],
            'Clove Whole / Loung' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 274.0, 'protein_g' => 6.0, 'carbs_g' => 65.5, 'fat_g' => 13.0, 'fiber_g' => 34.2, 'sodium_mg' => 277.0],
            ],
            'Coriander Powder' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 340.0, 'protein_g' => 20.0, 'carbs_g' => 20.0, 'fat_g' => 20.0, 'fiber_g' => 40.0, 'sodium_mg' => 40.0],
            ],
            'Cumin (Jeera) / Cumin Whole' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 420.0, 'protein_g' => 20.0, 'carbs_g' => 40.0, 'fat_g' => 20.0, 'fiber_g' => 20.0, 'sodium_mg' => 160.0],
            ],
            'Cumin Powder / Jeera Powder' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 420.0, 'protein_g' => 20.0, 'carbs_g' => 40.0, 'fat_g' => 20.0, 'fiber_g' => 20.0, 'sodium_mg' => 160.0],
            ],
            'Pink Rock Salt / Sendha Namak (Light Pink)' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 0.0, 'protein_g' => 0.0, 'carbs_g' => 0.0, 'fat_g' => 0.0, 'fiber_g' => 0.0, 'sodium_mg' => 38000.0],
            ],
            'Red Chilli Powder' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 316.6, 'protein_g' => 12.2, 'carbs_g' => 55.0, 'fat_g' => 17.2, 'fiber_g' => 27.2, 'sodium_mg' => 30.0],
            ],
            'Turmeric Powder' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 354.0, 'protein_g' => 8.0, 'carbs_g' => 65.0, 'fat_g' => 10.0, 'fiber_g' => 21.0, 'sodium_mg' => 38.0],
            ],
            'White Sesame (Til) / Natural' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 563.0, 'protein_g' => 18.3, 'carbs_g' => 23.4, 'fat_g' => 49.7, 'fiber_g' => 11.8, 'sodium_mg' => 11.0],
            ],
            'Bajra Whole / Pearl Millet' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 320.0, 'protein_g' => 7.0, 'carbs_g' => 67.0, 'fat_g' => 2.0, 'fiber_g' => 11.0, 'sodium_mg' => 0.0],
            ],
            'Finger (Ragi) Millet' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 386.0, 'protein_g' => 11.0, 'carbs_g' => 75.0, 'fat_g' => 4.3, 'fiber_g' => 3.6, 'sodium_mg' => 3.9],
            ],
            'Jawar/Sorghum Whole' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 329.0, 'protein_g' => 11.0, 'carbs_g' => 72.0, 'fat_g' => 3.5, 'fiber_g' => 6.7, 'sodium_mg' => 2.0],
            ],
            'Kodo (Kodro) Millet' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 149.0, 'protein_g' => 5.0, 'carbs_g' => 33.0, 'fat_g' => 1.6, 'fiber_g' => 3.1, 'sodium_mg' => 0.0],
            ],
            'Little Millet' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 378.0, 'protein_g' => 13.3, 'carbs_g' => 71.0, 'fat_g' => 4.4, 'fiber_g' => 8.9, 'sodium_mg' => 222.0],
            ],
            'White Quinoa Seeds (Processed)' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 368.0, 'protein_g' => 14.1, 'carbs_g' => 64.2, 'fat_g' => 6.1, 'fiber_g' => 7.0, 'sodium_mg' => 5.0],
            ],
            'A2 Gir Cow Ghee Bilona' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 900.0, 'protein_g' => 0.0, 'carbs_g' => 0.0, 'fat_g' => 100.0, 'fiber_g' => 0.0, 'sodium_mg' => 0.0],
            ],
            'Black Sesame Oil (Cold Pressed)' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 884.0, 'protein_g' => 0.0, 'carbs_g' => 0.0, 'fat_g' => 100.0, 'fiber_g' => 0.0, 'sodium_mg' => 0.0],
            ],
            'Groundnut Oil ( Cold Pressed)' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 884.0, 'protein_g' => 0.0, 'carbs_g' => 0.0, 'fat_g' => 100.0, 'fiber_g' => 0.0, 'sodium_mg' => 0.0],
            ],
            'Sunflower Oil [Cold Pressed]' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 884.0, 'protein_g' => 0.0, 'carbs_g' => 0.0, 'fat_g' => 100.0, 'fiber_g' => 0.0, 'sodium_mg' => 0.0],
            ],
            'Olive Oil Extra Virgin' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 884.0, 'protein_g' => 0.0, 'carbs_g' => 0.0, 'fat_g' => 100.0, 'fiber_g' => 0.0, 'sodium_mg' => 0.0],
            ],
            'Bajra Atta / Pearl Millet Flour' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 339.0, 'protein_g' => 13.7, 'carbs_g' => 72.6, 'fat_g' => 1.9, 'fiber_g' => 12.2, 'sodium_mg' => 5.0],
            ],
            'Basmati Rice Brown' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 367.0, 'protein_g' => 8.0, 'carbs_g' => 77.0, 'fat_g' => 3.0, 'fiber_g' => 3.5, 'sodium_mg' => 7.0],
            ],
            'Basmati Rice White' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 345.0, 'protein_g' => 7.0, 'carbs_g' => 78.0, 'fat_g' => 1.0, 'fiber_g' => 0.2, 'sodium_mg' => 17.0],
            ],
            'Sona Masoori - White Rice' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 345.0, 'protein_g' => 7.0, 'carbs_g' => 78.0, 'fat_g' => 1.0, 'fiber_g' => 0.2, 'sodium_mg' => 17.0],
            ],
            'Chana Besan / Bengal Gram Flour' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 374.0, 'protein_g' => 22.4, 'carbs_g' => 58.0, 'fat_g' => 6.0, 'fiber_g' => 12.2, 'sodium_mg' => 7.0],
            ],
            'Finger (Ragi) Millet Flour' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 340.0, 'protein_g' => 11.0, 'carbs_g' => 73.0, 'fat_g' => 0.4, 'fiber_g' => 8.0, 'sodium_mg' => 0.0],
            ],
            'Jowar Atta/Sorghum Flour' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 371.0, 'protein_g' => 11.0, 'carbs_g' => 75.0, 'fat_g' => 3.0, 'fiber_g' => 6.0, 'sodium_mg' => 6.0],
            ],
            'Wheat Flour' => [
                'source' => 'packaging_verified',
                'verified_label' => 'Prakruti Certified Packaging Fact',
                'per_100g' => ['energy_kcal' => 346.0, 'protein_g' => 12.0, 'carbs_g' => 71.0, 'fat_g' => 1.5, 'fiber_g' => 12.0, 'sodium_mg' => 0.0],
            ],
            'Multi Grain Flour' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 350.0, 'protein_g' => 13.0, 'carbs_g' => 70.0, 'fat_g' => 2.5, 'fiber_g' => 10.0, 'sodium_mg' => 10.0],
            ],
            'Jaggery Powder' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 383.0, 'protein_g' => 0.4, 'carbs_g' => 85.0, 'fat_g' => 0.1, 'fiber_g' => 0.0, 'sodium_mg' => 20.0],
            ],
            'Off White Sugar Light' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 398.0, 'protein_g' => 0.0, 'carbs_g' => 99.8, 'fat_g' => 0.0, 'fiber_g' => 0.0, 'sodium_mg' => 1.0],
            ],
            'Raw Sugar / Khandasari Sugar (Brown)' => [
                'source' => 'ifct_reference_estimate',
                'verified_label' => 'ICMR-NIN / IFCT 2024 Reference Standard',
                'per_100g' => ['energy_kcal' => 387.0, 'protein_g' => 0.1, 'carbs_g' => 99.5, 'fat_g' => 0.0, 'fiber_g' => 0.0, 'sodium_mg' => 2.0],
            ],
        ];

        foreach ($products as $index => $item) {
            [$name, $categoryName, $price] = $item;
            $sku = 'PRK-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
            $category = $categoryMap[$categoryName] ?? reset($categoryMap);
            $isFeatured = in_array($name, $featured, true);
            $nutritionalInfo = $nutritionProfiles[$name] ?? null;

            Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'slug' => Str::slug($name) . '-' . ($index + 1),
                    'category_id' => $category->id,
                    'brand_id' => $brand->id,
                    'price' => $price,
                    'sale_price' => $isFeatured ? round($price * 0.9, 2) : null,
                    'gst_percentage' => 5,
                    'stock_qty' => 100,
                    'low_stock_qty' => 10,
                    'unit' => 'pack',
                    'min_order_qty' => 1,
                    'weight' => 1,
                    'short_desc' => "Premium organic {$name} sourced from trusted farms.",
                    'description' => "Premium quality {$name} sourced from organic farms. Clean, natural, and essential for healthy everyday cooking.",
                    'nutritional_info' => $nutritionalInfo,
                    'status' => 'active',
                    'featured' => $isFeatured,
                ]
            );
        }
    }
}
