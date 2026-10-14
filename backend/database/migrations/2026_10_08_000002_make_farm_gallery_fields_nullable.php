<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_gallery_items', function (Blueprint $table) {
            $table->string('image')->nullable()->change();
            $table->text('description')->nullable()->change();
            $table->string('tag')->nullable()->change();
            $table->string('location')->nullable()->change();
            $table->text('details')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('farm_gallery_items', function (Blueprint $table) {
            $table->string('image')->nullable(false)->change();
            $table->text('description')->nullable(false)->change();
            $table->string('tag')->nullable(false)->change();
            $table->string('location')->nullable(false)->change();
            $table->text('details')->nullable(false)->change();
        });
    }
};
