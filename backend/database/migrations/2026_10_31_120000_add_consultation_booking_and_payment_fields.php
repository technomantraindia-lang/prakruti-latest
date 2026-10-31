<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('consultation_requests')) {
            return;
        }

        Schema::table('consultation_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('consultation_requests', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('reference_no')->index();
            }
            if (! Schema::hasColumn('consultation_requests', 'session_type')) {
                $table->string('session_type', 30)->default('new_consultation')->after('consent')->index();
            }
            if (! Schema::hasColumn('consultation_requests', 'session_number')) {
                $table->unsignedInteger('session_number')->default(1)->after('session_type');
            }
            if (! Schema::hasColumn('consultation_requests', 'payment_status')) {
                $table->string('payment_status', 30)->default('paid_demo')->after('session_number')->index();
            }
            if (! Schema::hasColumn('consultation_requests', 'payment_method')) {
                $table->string('payment_method', 30)->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('consultation_requests', 'payment_gateway')) {
                $table->string('payment_gateway', 50)->default('demo')->after('payment_method');
            }
            if (! Schema::hasColumn('consultation_requests', 'payment_transaction_id')) {
                $table->string('payment_transaction_id', 100)->nullable()->after('payment_gateway');
            }
            if (! Schema::hasColumn('consultation_requests', 'payment_amount')) {
                $table->decimal('payment_amount', 10, 2)->default(1500)->after('payment_transaction_id');
            }
            if (! Schema::hasColumn('consultation_requests', 'payment_paid_at')) {
                $table->timestamp('payment_paid_at')->nullable()->after('payment_amount');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('consultation_requests')) {
            return;
        }

        $columns = [
            'user_id', 'session_type', 'session_number', 'payment_status',
            'payment_method', 'payment_gateway', 'payment_transaction_id',
            'payment_amount', 'payment_paid_at',
        ];

        $existing = array_values(array_filter($columns, static fn (string $column) => Schema::hasColumn('consultation_requests', $column)));
        if ($existing) {
            Schema::table('consultation_requests', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};
