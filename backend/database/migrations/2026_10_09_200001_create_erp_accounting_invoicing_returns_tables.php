<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add GSTIN & Invoice fields to orders
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'is_business_order')) {
                $table->boolean('is_business_order')->default(false)->after('order_type');
            }
            if (! Schema::hasColumn('orders', 'business_name')) {
                $table->string('business_name')->nullable()->after('is_business_order');
            }
            if (! Schema::hasColumn('orders', 'customer_gstin')) {
                $table->string('customer_gstin', 15)->nullable()->index()->after('business_name');
            }
            if (! Schema::hasColumn('orders', 'invoice_number')) {
                $table->string('invoice_number', 50)->nullable()->unique()->after('customer_gstin');
            }
            if (! Schema::hasColumn('orders', 'invoice_pdf_path')) {
                $table->string('invoice_pdf_path')->nullable()->after('invoice_number');
            }
        });

        // 2. Add batch_number and hsn_code to order_items
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'batch_number')) {
                $table->string('batch_number', 100)->nullable()->after('var_id');
            }
            if (! Schema::hasColumn('order_items', 'hsn_code')) {
                $table->string('hsn_code', 20)->nullable()->after('batch_number');
            }
        });

        // 3. Add hsn_code to products
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'hsn_code')) {
                $table->string('hsn_code', 20)->nullable()->default('30049011')->after('gst_percentage');
            }
        });

        // 4. Purchase Returns (Return to Vendor)
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50)->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('restrict');
            $table->foreignId('grn_id')->nullable()->constrained('goods_received_notes')->nullOnDelete();
            $table->foreignId('purchase_bill_id')->nullable()->constrained('purchase_bills')->nullOnDelete();
            $table->string('status', 30)->default('completed');
            $table->string('reason')->nullable();
            $table->decimal('total_taxable', 12, 2)->default(0);
            $table->decimal('cgst', 12, 2)->default(0);
            $table->decimal('sgst', 12, 2)->default(0);
            $table->decimal('igst', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
            $table->string('batch_number', 100)->nullable();
            $table->unsignedInteger('return_qty');
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        // 5. Vendor Debit Notes
        Schema::create('vendor_debit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('debit_note_number', 50)->unique();
            $table->foreignId('purchase_return_id')->nullable()->constrained('purchase_returns')->nullOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('restrict');
            $table->foreignId('purchase_bill_id')->nullable()->constrained('purchase_bills')->nullOnDelete();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('itc_reversal_status', 30)->default('reversed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. Chart of Accounts (COA)
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('type', 30); // asset, liability, equity, revenue, expense
            $table->string('parent_code', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 7. General Ledger Journal Entries
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 50)->unique();
            $table->date('entry_date');
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('narration')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('journal_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('restrict');
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('vendor_debit_notes');
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('hsn_code');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'hsn_code']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['is_business_order', 'business_name', 'customer_gstin', 'invoice_number', 'invoice_pdf_path']);
        });
    }
};
