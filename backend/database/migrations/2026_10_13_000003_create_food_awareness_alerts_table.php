<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('food_awareness_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->default('general');
            $table->string('category_label')->nullable();
            $table->string('tag')->nullable();
            $table->string('severity')->default('warning'); // critical, warning, info
            $table->string('media_type')->default('image'); // image, video
            $table->string('image')->nullable();
            $table->string('video')->nullable();
            $table->string('source')->nullable();
            $table->string('incident_date')->nullable();
            $table->text('summary');
            $table->longText('details')->nullable();
            $table->text('how_to_test')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_awareness_alerts');
    }
};
