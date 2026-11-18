<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('website_visits')) {
            return;
        }

        Schema::create('website_visits', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_key', 128);
            $table->date('visit_date');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->string('visitor_phone', 40)->nullable();
            $table->boolean('is_authenticated')->default(false);
            $table->boolean('marketing_consent')->default(false);
            $table->timestamp('contact_submitted_at')->nullable();
            $table->timestamp('follow_up_sent_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable();
            $table->string('landing_path', 500)->nullable();
            $table->string('page_title')->nullable();
            $table->unsignedInteger('visit_count')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['visitor_key', 'visit_date']);
            $table->index(['visit_date', 'is_authenticated']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_visits');
    }
};
