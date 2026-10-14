<?php

namespace Database\Seeders;

use App\Models\Guideline;
use Illuminate\Database\Seeder;

class GuidelineSeeder extends Seeder
{
    public function run(): void
    {
        $guidelines = [
            [
                'title' => 'ICMR–NIN Dietary Guidelines for Indians (2024)',
                'slug' => 'icmr-nin-dietary-guidelines-2024',
                'category' => 'icmr_nin',
                'category_label' => 'ICMR-NIN Standards',
                'organization' => 'ICMR - National Institute of Nutrition',
                'description' => 'Comprehensive national nutritional recommendations for Indians covering recommended dietary allowances (RDA), macronutrient ratios, daily pulses, whole grains, and disease prevention through traditional foods.',
                'file_path' => null,
                'external_url' => 'https://nin.res.in/RDA_Full_Report_2024.html',
                'file_size' => '8.4 MB',
                'published_year' => '2024',
                'is_active' => true,
                'sort_order' => 1,
                'download_count' => 1240,
            ],
            [
                'title' => 'WHO Guideline: Sodium Reduction & Salt Intake Benchmark',
                'slug' => 'who-guideline-sodium-intake',
                'category' => 'who',
                'category_label' => 'WHO Global Guidelines',
                'organization' => 'World Health Organization (WHO)',
                'description' => 'Global public health benchmark limiting adult salt intake to under 5 grams per day (approx. 2g sodium) to reduce hypertension and cardiovascular risks.',
                'file_path' => null,
                'external_url' => 'https://www.who.int/news-room/fact-sheets/detail/sodium-reduction',
                'file_size' => '1.8 MB',
                'published_year' => '2024',
                'is_active' => true,
                'sort_order' => 2,
                'download_count' => 890,
            ],
            [
                'title' => 'WHO Guideline: Sugars Intake for Adults and Children',
                'slug' => 'who-guideline-sugars-intake',
                'category' => 'who',
                'category_label' => 'WHO Global Guidelines',
                'organization' => 'World Health Organization (WHO)',
                'description' => 'Recommendations restricting free sugar consumption to less than 10% (ideally below 5%) of total daily energy to prevent obesity, fatty liver, and dental caries.',
                'file_path' => null,
                'external_url' => 'https://www.who.int/publications/i/item/9789241549028',
                'file_size' => '2.1 MB',
                'published_year' => '2023',
                'is_active' => true,
                'sort_order' => 3,
                'download_count' => 745,
            ],
            [
                'title' => 'WHO Guideline: Saturated Fatty Acid and Trans-Fatty Acid Intake',
                'slug' => 'who-guideline-fats-oils-intake',
                'category' => 'who',
                'category_label' => 'WHO Global Guidelines',
                'organization' => 'World Health Organization (WHO)',
                'description' => 'Guidance on replacing industrial trans-fats and excess saturated fats with polyunsaturated and monounsaturated fatty acids from cold-pressed plant sources.',
                'file_path' => null,
                'external_url' => 'https://www.who.int/westernpacific/publications/i/item/9789240073630',
                'file_size' => '3.6 MB',
                'published_year' => '2023',
                'is_active' => true,
                'sort_order' => 4,
                'download_count' => 610,
            ],
            [
                'title' => 'WHO Guideline: Carbohydrate Quality & Whole Grain Intake',
                'slug' => 'who-guideline-carbohydrates-quality',
                'category' => 'who',
                'category_label' => 'WHO Global Guidelines',
                'organization' => 'World Health Organization (WHO)',
                'description' => 'Focusing on carbohydrates from unrefined whole grains, millets, pulses, and vegetables providing at least 25-30g of prebiotic dietary fiber daily.',
                'file_path' => null,
                'external_url' => 'https://www.who.int/publications/i/item/9789240073593',
                'file_size' => '2.9 MB',
                'published_year' => '2023',
                'is_active' => true,
                'sort_order' => 5,
                'download_count' => 520,
            ],
            [
                'title' => 'FSSAI Food Safety, Labelling and Organic Certification Standards',
                'slug' => 'fssai-labelling-organic-standards',
                'category' => 'fssai',
                'category_label' => 'FSSAI Standards',
                'organization' => 'Food Safety and Standards Authority of India (FSSAI)',
                'description' => 'National regulatory frameworks governing clean food labels, front-of-pack nutritional declarations, Jaivik Bharat organic certification, and food safety protocols.',
                'file_path' => null,
                'external_url' => 'https://fssai.gov.in/',
                'file_size' => '4.2 MB',
                'published_year' => '2024',
                'is_active' => true,
                'sort_order' => 6,
                'download_count' => 480,
            ],
            [
                'title' => 'Prakruti Vedic Nutrition Blueprint & Traditional Meal Guide',
                'slug' => 'prakruti-vedic-nutrition-blueprint',
                'category' => 'prakruti',
                'category_label' => 'Prakruti Wellness',
                'organization' => 'Prakruti Corporation',
                'description' => 'Practical handbook for Indian kitchens combining heirloom unpolished grains, slow cold-pressed seed oils, and Vedic bilona cow ghee for optimal digestion and vitality.',
                'file_path' => null,
                'external_url' => 'https://prakruticorp.org/',
                'file_size' => '1.5 MB',
                'published_year' => '2026',
                'is_active' => true,
                'sort_order' => 7,
                'download_count' => 1950,
            ],
        ];

        foreach ($guidelines as $item) {
            Guideline::updateOrCreate(
                ['slug' => $item['slug']],
                $item
            );
        }
    }
}
