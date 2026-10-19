<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('consultation_requests')) {
            Schema::table('consultation_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('consultation_requests', 'doctor_phone')) {
                    $table->string('doctor_phone', 30)->nullable()->after('assigned_to');
                }
                if (!Schema::hasColumn('consultation_requests', 'doctor_email')) {
                    $table->string('doctor_email', 191)->nullable()->after('doctor_phone');
                }
                if (!Schema::hasColumn('consultation_requests', 'doctor_notified_at')) {
                    $table->timestamp('doctor_notified_at')->nullable()->after('doctor_email');
                }
                if (!Schema::hasColumn('consultation_requests', 'doctor_notified_channel')) {
                    $table->string('doctor_notified_channel', 30)->nullable()->after('doctor_notified_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('consultation_requests')) {
            Schema::table('consultation_requests', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('consultation_requests', 'doctor_phone')) $cols[] = 'doctor_phone';
                if (Schema::hasColumn('consultation_requests', 'doctor_email')) $cols[] = 'doctor_email';
                if (Schema::hasColumn('consultation_requests', 'doctor_notified_at')) $cols[] = 'doctor_notified_at';
                if (Schema::hasColumn('consultation_requests', 'doctor_notified_channel')) $cols[] = 'doctor_notified_channel';
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
