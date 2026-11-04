<?php

use App\Support\PackSize;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds an explicit pack size (quantity + unit) so the Family Pack never has to guess
 * whether "1" means 1 kg, 1 litre or 1 piece.
 *
 * Backfill is deliberately cautious: only labels that already contain a unit ("500g", "1 kg", "1 L")
 * are copied. Bare numbers ("250") and unit-less weights are left empty for an admin to confirm.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'product_variations'] as $table) {
            if (! Schema::hasColumn($table, 'net_qty')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->decimal('net_qty', 10, 3)->nullable();
                    $t->string('net_unit', 8)->nullable(); // g, kg, ml, l
                });
            }
        }

        DB::table('product_variations')->whereNull('net_qty')->whereNotNull('attr_val')->orderBy('id')->each(function ($v) {
            $p = PackSize::parse($v->attr_val);
            if (! $p || $p['basis'] !== 'label') {
                return;
            }
            [$qty, $unit] = $p['grams'] !== null ? [$p['grams'], 'g'] : [$p['ml'], 'ml'];
            DB::table('product_variations')->where('id', $v->id)->update(['net_qty' => $qty, 'net_unit' => $unit]);
        });
    }

    public function down(): void
    {
        foreach (['products', 'product_variations'] as $table) {
            if (Schema::hasColumn($table, 'net_qty')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn(['net_qty', 'net_unit']);
                });
            }
        }
    }
};
