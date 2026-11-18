<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('website_visits')) {
            return;
        }

        Schema::table('website_visits', function (Blueprint $table) {
            if (! Schema::hasColumn('website_visits', 'marketing_consent')) {
                $table->boolean('marketing_consent')->default(false)->after('is_authenticated');
            }
            if (! Schema::hasColumn('website_visits', 'contact_submitted_at')) {
                $table->timestamp('contact_submitted_at')->nullable()->after('marketing_consent');
            }
            if (! Schema::hasColumn('website_visits', 'follow_up_sent_at')) {
                $table->timestamp('follow_up_sent_at')->nullable()->after('contact_submitted_at');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('website_visits')) {
            Schema::table('website_visits', function (Blueprint $table) {
                foreach (['follow_up_sent_at', 'contact_submitted_at', 'marketing_consent'] as $column) {
                    if (Schema::hasColumn('website_visits', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
