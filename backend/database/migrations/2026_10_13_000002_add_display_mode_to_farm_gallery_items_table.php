<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_gallery_items', function (Blueprint $table) {
            if (! Schema::hasColumn('farm_gallery_items', 'display_mode')) {
                $table->string('display_mode', 30)->default('image_text')->after('image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('farm_gallery_items', function (Blueprint $table) {
            if (Schema::hasColumn('farm_gallery_items', 'display_mode')) {
                $table->dropColumn('display_mode');
            }
        });
    }
};
