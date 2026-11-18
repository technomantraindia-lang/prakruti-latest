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
                if (!Schema::hasColumn('consultation_requests', 'zoom_link')) {
                    $table->string('zoom_link', 500)->nullable()->after('doctor_notified_channel');
                }
                if (!Schema::hasColumn('consultation_requests', 'zoom_meeting_id')) {
                    $table->string('zoom_meeting_id', 100)->nullable()->after('zoom_link');
                }
                if (!Schema::hasColumn('consultation_requests', 'zoom_passcode')) {
                    $table->string('zoom_passcode', 50)->nullable()->after('zoom_meeting_id');
                }
                if (!Schema::hasColumn('consultation_requests', 'scheduled_at')) {
                    $table->dateTime('scheduled_at')->nullable()->after('zoom_passcode');
                }
                if (!Schema::hasColumn('consultation_requests', 'video_status')) {
                    $table->string('video_status', 50)->default('pending')->after('scheduled_at');
                }
                if (!Schema::hasColumn('consultation_requests', 'customer_notified_at')) {
                    $table->dateTime('customer_notified_at')->nullable()->after('video_status');
                }
                if (!Schema::hasColumn('consultation_requests', 'customer_notified_channel')) {
                    $table->string('customer_notified_channel', 50)->nullable()->after('customer_notified_at');
                }
                if (!Schema::hasColumn('consultation_requests', 'is_order_followup')) {
                    $table->boolean('is_order_followup')->default(false)->after('customer_notified_channel');
                }
                if (!Schema::hasColumn('consultation_requests', 'order_id')) {
                    $table->unsignedBigInteger('order_id')->nullable()->after('is_order_followup');
                }
                if (!Schema::hasColumn('consultation_requests', 'consultant_notes')) {
                    $table->text('consultant_notes')->nullable()->after('order_id');
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
                    'zoom_link',
                    'zoom_meeting_id',
                    'zoom_passcode',
                    'scheduled_at',
                    'video_status',
                    'customer_notified_at',
                    'customer_notified_channel',
                    'is_order_followup',
                    'order_id',
                    'consultant_notes',
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
