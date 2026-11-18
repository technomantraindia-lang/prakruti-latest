<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $now = now();
        $defaults = [
            'standard_shipping_charge' => '50',
            'free_shipping_min_amount' => '1000',
            'bill_discount_enabled' => '0',
            'bill_discount_rules' => '[]',
        ];

        foreach ($defaults as $key => $value) {
            $existing = DB::table('settings')->where('key', $key)->value('value');
            if ($existing === null || $existing === '') {
                DB::table('settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => $value, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        // Keep existing administrator settings when rolling back this default seed.
    }
};
