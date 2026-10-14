<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove the two default gallery cards requested by the client.
        $itemIds = DB::table('farm_gallery_items')
            ->whereIn('title', [
                'Sustainable Eco-Friendly Packaging',
                'Traditional Spice Cultivation in Native Soils',
            ])
            ->pluck('id');

        DB::table('farm_gallery_videos')
            ->whereIn('farm_gallery_item_id', $itemIds)
            ->delete();

        DB::table('farm_gallery_items')
            ->whereIn('id', $itemIds)
            ->delete();

        // Remove the uploaded vegetables video from its gallery card.
        DB::table('farm_gallery_videos')
            ->where('video', 'farm-gallery/videos/0dedba8b-edbb-4553-aa59-1108f7b96738.mp4')
            ->delete();

        // The default ghee video must not appear in any gallery category.
        DB::table('farm_gallery_videos')
            ->where('video', 'farm-gallery/videos/default-ghee-video-1.mp4')
            ->delete();
    }

    public function down(): void
    {
        // The removed gallery media is intentionally not recreated on rollback.
    }
};
