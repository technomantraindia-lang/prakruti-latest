<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('farm_gallery_items')
            ->whereIn('category', ['crops-livestock', 'fresh-produce'])
            ->update(['category' => 'crops-vegetables', 'updated_at' => now()]);

        $existing = DB::table('farm_gallery_categories')->where('slug', 'crops-vegetables')->exists();

        if ($existing) {
            DB::table('farm_gallery_categories')
                ->where('slug', 'crops-vegetables')
                ->update([
                    'name' => 'Crops, Cows & Vegetables',
                    'sort_order' => 3,
                    'status' => 'active',
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('farm_gallery_categories')
                ->where('slug', 'crops-livestock')
                ->update([
                    'slug' => 'crops-vegetables',
                    'name' => 'Crops, Cows & Vegetables',
                    'sort_order' => 3,
                    'status' => 'active',
                    'updated_at' => now(),
                ]);
        }

        DB::table('farm_gallery_categories')->whereIn('slug', ['crops-livestock', 'fresh-produce'])->delete();
    }

    public function down(): void
    {
        DB::table('farm_gallery_items')
            ->where('category', 'crops-vegetables')
            ->update(['category' => 'crops-livestock', 'updated_at' => now()]);

        DB::table('farm_gallery_categories')->where('slug', 'crops-vegetables')->delete();
        DB::table('farm_gallery_categories')->updateOrInsert(
            ['slug' => 'crops-livestock'],
            [
                'name' => 'Cold-Press, Ghee & Packaging',
                'sort_order' => 3,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
};
