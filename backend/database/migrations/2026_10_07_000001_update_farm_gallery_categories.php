<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $categories = [
            ['old' => 'harvesting', 'slug' => 'farmer-community', 'name' => 'Farmer & Community', 'sort_order' => 1],
            ['old' => 'fields', 'slug' => 'fields-cultivation', 'name' => 'Fields, Soil & Cultivation', 'sort_order' => 2],
            ['old' => 'processing', 'slug' => 'crops-livestock', 'name' => 'Crops, Cows & Livestock', 'sort_order' => 3],
            ['old' => 'testing-purpose', 'slug' => 'fresh-produce', 'name' => 'Fresh Produce', 'sort_order' => 4],
            ['old' => null, 'slug' => 'awards-events', 'name' => 'Awards & Events', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            if ($category['old'] && $category['old'] !== $category['slug']) {
                DB::table('farm_gallery_items')
                    ->where('category', $category['old'])
                    ->update(['category' => $category['slug'], 'updated_at' => now()]);

                $oldCategory = DB::table('farm_gallery_categories')->where('slug', $category['old'])->first();
                $newCategoryExists = DB::table('farm_gallery_categories')->where('slug', $category['slug'])->exists();

                if ($oldCategory && ! $newCategoryExists) {
                    DB::table('farm_gallery_categories')
                        ->where('slug', $category['old'])
                        ->update([
                            'slug' => $category['slug'],
                            'name' => $category['name'],
                            'sort_order' => $category['sort_order'],
                            'updated_at' => now(),
                        ]);
                    continue;
                }

                if ($oldCategory) {
                    DB::table('farm_gallery_categories')->where('slug', $category['old'])->delete();
                }
            }

            DB::table('farm_gallery_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'sort_order' => $category['sort_order'],
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        $categories = [
            ['new' => 'farmer-community', 'old' => 'harvesting', 'name' => 'Manual Harvesting', 'sort_order' => 2],
            ['new' => 'fields-cultivation', 'old' => 'fields', 'name' => 'Farmlands & Sowing', 'sort_order' => 1],
            ['new' => 'crops-livestock', 'old' => 'processing', 'name' => 'Cold-Press, Ghee & Packaging', 'sort_order' => 3],
            ['new' => 'fresh-produce', 'old' => 'testing-purpose', 'name' => 'testing purpose', 'sort_order' => 4],
        ];

        foreach ($categories as $category) {
            DB::table('farm_gallery_items')
                ->where('category', $category['new'])
                ->update(['category' => $category['old'], 'updated_at' => now()]);

            DB::table('farm_gallery_categories')->where('slug', $category['new'])->delete();
            DB::table('farm_gallery_categories')->updateOrInsert(
                ['slug' => $category['old']],
                [
                    'name' => $category['name'],
                    'sort_order' => $category['sort_order'],
                    'status' => 'active',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        DB::table('farm_gallery_categories')->where('slug', 'awards-events')->delete();
    }
};
