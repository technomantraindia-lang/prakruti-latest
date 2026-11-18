<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('consultation_requests') || Schema::hasColumn('consultation_requests', 'doctor_profile_id')) {
            return;
        }

        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('doctor_profile_id')->nullable()->after('assigned_to')->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('consultation_requests') && Schema::hasColumn('consultation_requests', 'doctor_profile_id')) {
            Schema::table('consultation_requests', function (Blueprint $table) {
                $table->dropColumn('doctor_profile_id');
            });
        }
    }
};
