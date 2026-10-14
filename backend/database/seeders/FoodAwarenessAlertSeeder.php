<?php

namespace Database\Seeders;

use App\Models\FoodAwarenessAlert;
use Illuminate\Database\Seeder;

class FoodAwarenessAlertSeeder extends Seeder
{
    public function run(): void
    {
        $alerts = [
            [
                'title' => 'Industrial Dyes & Chemical Colors Seized in Commercial Spices',
                'category' => 'spices',
                'category_label' => 'Spices & Masalas',
                'tag' => '🚨 Industrial Dyes Alert',
                'severity' => 'critical',
                'media_type' => 'image',
                'image' => 'food-awareness/spices.png',
                'video' => null,
                'source' => 'National Consumer & Food Safety Reports',
                'incident_date' => 'Recent National Exposures',
                'summary' => 'Lab testing and market raids uncovered synthetic industrial dyes (Metanil Yellow, Lead Chromate, Sudan Red) and sawdust fillers blended into commercial turmeric, chili, and coriander powders.',
                'details' => 'Commercial spice supply chains frequently cut costs by adding powdered husk, chalk, and non-permitted coal-tar dyes to mimic vibrant harvest color. Continued exposure to these industrial pigments is linked to kidney damage, liver toxicity, and chronic digestive disorders. Prakruti uses 100% whole heirloom spices, stone-ground at slow speed with zero artificial color, preservatives, or chemical polish.',
                'how_to_test' => 'Glass Water Test: Dissolve 1 teaspoon of turmeric powder in a glass of lukewarm water without stirring. Pure organic turmeric slowly settles to the bottom leaving light, transparent water. Chemically dyed spices instantly turn the water intense cloudy neon yellow with colored streaks.',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Counterfeit Ghee Rackets: Hydrogenated Palm Oil & Synthetic Essence',
                'category' => 'ghee_dairy',
                'category_label' => 'Ghee & Dairy',
                'tag' => '⚠️ Fake Ghee & Dairy Raid',
                'severity' => 'critical',
                'media_type' => 'video',
                'image' => 'food-awareness/bestseller_gir_ghee.jpg',
                'video' => 'food-awareness/fake_ghee_expose.mp4',
                'source' => 'Food Safety Enforcement Raids',
                'incident_date' => 'Multi-State Raids',
                'summary' => 'Large-scale counterfeiting operations were busted synthesizing fake ghee from industrial hydrogenated fats, animal tallow, and chemical aroma compounds (diacetyl) packaged in imitation branded jars.',
                'details' => 'Laboratory analyses of counterfeit market ghee showed zero actual cow milk solids. Instead, trans-fat laden vegetable oils, artificial colors, and synthetic aroma chemicals are processed to mimic the aroma and texture of pure bilona ghee. Consuming these fake fats severely elevates LDL cholesterol and arterial plaque.',
                'how_to_test' => 'Palm & Heat Test: Put half a teaspoon of ghee on your palm. Pure Vedic A2 ghee has a low melting point and melts instantly from body heat. Fake fat ghee stays solid and greasy. Also, heat 1 tsp of ghee in a pan: authentic ghee melts immediately into dark brown golden aroma, whereas adulterated ghee takes long to melt and stays pale yellow.',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Calcium Carbide & Toxic Hormone Sprays in Market Fruits & Greens',
                'category' => 'produce',
                'category_label' => 'Fruits & Vegetables',
                'tag' => '🧪 Artificial Ripening Warning',
                'severity' => 'warning',
                'media_type' => 'image',
                'image' => 'food-awareness/cereal_pulses.png',
                'video' => null,
                'source' => 'Agricultural Safety Authority',
                'incident_date' => 'Seasonal Market Advisory',
                'summary' => 'Banned industrial calcium carbide sachets and oxytocin hormone injections are widely documented in wholesale mandis to force unripe mangoes, bananas, and gourds into overnight artificial ripening.',
                'details' => 'Commercial traders use industrial carbide (\'masala\') which releases acetylene gas contaminated with traces of arsenic and phosphorus hydrides. This causes severe stomach burning, mouth ulcers, dizziness, and neurological fatigue. Genuine organic fruits and vegetables are allowed to mature slowly under native solar sunlight.',
                'how_to_test' => 'Bucket Float Test: Place fruits like mangoes in a bucket of fresh water. Naturally sun-ripened organic fruits are dense with natural plant sugars and sink to the bottom. Artificially gas-ripened fruits often float on top. Additionally, naturally ripened fruit has a sweet organic fragrance around the stem, whereas chemically ripened fruit smells faint or chemical-like.',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'title' => 'Liquid Paraffin & Talc Stone Polish on Everyday Dals & Rice',
                'category' => 'grains_pulses',
                'category_label' => 'Grains, Dals & Pulses',
                'tag' => '⚠️ Chemical Polish Warning',
                'severity' => 'warning',
                'media_type' => 'image',
                'image' => 'food-awareness/farm_gallery_spelt_wheat.jpg',
                'video' => null,
                'source' => 'Consumer Food Chemistry Studies',
                'incident_date' => 'Industry Reality Report',
                'summary' => 'To make old grain stocks look glossy and attractive on supermarket shelves, commercial pulses and rice are treated with mineral oil (liquid paraffin), soapstone powder, and chemical polish.',
                'details' => 'High-speed machine polishing strips away the grain\'s vital aleurone layer (where B-vitamins, iron, and dietary fiber live), leaving empty starch. Paraffin wax and chemical dust are then applied to give the grains a shiny, unblemished cosmetic finish. Prakruti pulses and grains are completely unpolished, raw, and unadulterated directly from partner organic farmlands.',
                'how_to_test' => 'White Napkin Rub Test: Take a handful of raw dal or rice and rub it firmly against a clean moist white paper napkin. If oily stains, chalky white residue, or artificial yellow color transfers onto the napkin, the grain is chemically polished. Pure organic unpolished grains leave zero residue and have an earthy matte finish.',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($alerts as $alert) {
            FoodAwarenessAlert::updateOrCreate(
                ['title' => $alert['title']],
                $alert
            );
        }
    }
}
