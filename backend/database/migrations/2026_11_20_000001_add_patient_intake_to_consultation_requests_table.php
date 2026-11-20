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
        Schema::table('consultation_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('consultation_requests', 'patient_intake_data')) {
                $table->json('patient_intake_data')->nullable()->after('concern');
            }
            if (! Schema::hasColumn('consultation_requests', 'lab_reports')) {
                $table->json('lab_reports')->nullable()->after('patient_intake_data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            if (Schema::hasColumn('consultation_requests', 'lab_reports')) {
                $table->dropColumn('lab_reports');
            }
            if (Schema::hasColumn('consultation_requests', 'patient_intake_data')) {
                $table->dropColumn('patient_intake_data');
            }
        });
    }
};
