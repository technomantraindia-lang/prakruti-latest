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
        if (Schema::hasTable('consultation_requests')) {
            Schema::table('consultation_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('consultation_requests', 'admin_approved')) {
                    $table->boolean('admin_approved')->default(false)->index()->after('status');
                }
                if (!Schema::hasColumn('consultation_requests', 'admin_approved_at')) {
                    $table->timestamp('admin_approved_at')->nullable()->after('admin_approved');
                }
                if (!Schema::hasColumn('consultation_requests', 'admin_approved_by')) {
                    $table->unsignedBigInteger('admin_approved_by')->nullable()->after('admin_approved_at');
                }
                if (!Schema::hasColumn('consultation_requests', 'customer_whatsapp_sent_at')) {
                    $table->timestamp('customer_whatsapp_sent_at')->nullable()->after('customer_notified_channel');
                }
                if (!Schema::hasColumn('consultation_requests', 'doctor_whatsapp_sent_at')) {
                    $table->timestamp('doctor_whatsapp_sent_at')->nullable()->after('customer_whatsapp_sent_at');
                }
                if (!Schema::hasColumn('consultation_requests', 'doctor_notes')) {
                    $table->text('doctor_notes')->nullable()->after('consultant_notes');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('consultation_requests')) {
            Schema::table('consultation_requests', function (Blueprint $table) {
                $cols = [
                    'admin_approved',
                    'admin_approved_at',
                    'admin_approved_by',
                    'customer_whatsapp_sent_at',
                    'doctor_whatsapp_sent_at',
                    'doctor_notes',
                ];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('consultation_requests', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
