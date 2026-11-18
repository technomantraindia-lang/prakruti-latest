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

        $current = DB::table('settings')->where('key', 'free_shipping_min_amount')->value('value');
        if ($current === null || in_array((string) $current, ['500', '550'], true)) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'free_shipping_min_amount'],
                ['value' => '1000', 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        // Keep the administrator's current delivery threshold on rollback.
    }
};
