<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('doctor_profiles')) {
            return;
        }

        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('specialization')->nullable();
            $table->string('qualification')->nullable();
            $table->string('phone', 30);
            $table->string('email', 191);
            $table->string('experience', 50)->nullable();
            $table->string('location')->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('doctor_profiles')->insert([
            'name' => 'Dr. Varsha Shah',
            'specialization' => 'Lifestyle Medicine',
            'qualification' => 'DCH, DNB (Pediatrics), MBBS · PGDLM (Lifestyle Medicine)',
            'phone' => '+91 98987 14271',
            'email' => 'drvarshas@yahoo.com',
            'experience' => '12+ Yrs Exp',
            'location' => 'Vadodara, Gujarat, India',
            'bio' => 'Lifestyle medicine consultant passionate about nutrition, disease reversal, and helping families build healthier everyday routines.',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_profiles');
    }
};
