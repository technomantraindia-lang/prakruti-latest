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
        Schema::create('guidelines', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->default('who'); // who, icmr_nin, fssai, prakruti, clinical
            $table->string('category_label')->nullable();
            $table->string('organization')->nullable(); // WHO, ICMR - NIN, FSSAI, Prakruti
            $table->text('description')->nullable();
            $table->string('file_path')->nullable(); // Uploaded PDF
            $table->string('external_url', 1000)->nullable(); // Official online PDF / document link
            $table->string('file_size')->nullable(); // e.g. "3.2 MB"
            $table->string('published_year')->nullable(); // e.g. "2024"
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->unsignedBigInteger('download_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guidelines');
    }
};
