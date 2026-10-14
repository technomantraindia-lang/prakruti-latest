<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Purchase, Vendor, Inward Stock (GRN), Batches, and Purchase Bills.
     */
    public function up(): void
    {
        // 1. Vendors Master Table
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('gstin', 15)->nullable()->index();
            $table->string('pan', 10)->nullable()->index();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('state_code', 2)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->unsignedInteger('payment_terms_days')->default(30);
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Vendor Products (Product to Vendor mapping with purchase price & MOQ)
        Schema::create('vendor_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->onDelete('cascade');
            $table->string('vendor_sku')->nullable();
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->unsignedInteger('moq')->default(1);
            $table->unsignedInteger('lead_time_days')->default(7);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['vendor_id', 'product_id']);
        });

        // 3. Purchase Orders
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('restrict');
            $table->string('status', 30)->default('draft'); // draft, sent, partially_received, received, cancelled
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->date('expected_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 4. Purchase Order Items
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
            $table->unsignedInteger('ordered_qty');
            $table->unsignedInteger('received_qty')->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        // 5. Goods Received Notes (GRN / Inward)
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 50)->unique();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('restrict');
            $table->string('vendor_invoice_no', 100)->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('received_date');
            $table->string('status', 30)->default('verified'); // draft, verified, cancelled
            $table->decimal('inward_freight_charge', 10, 2)->default(0);
            $table->decimal('inward_handling_charge', 10, 2)->default(0);
            $table->decimal('total_items_taxable', 12, 2)->default(0);
            $table->decimal('total_tax_amount', 12, 2)->default(0);
            $table->decimal('total_landed_cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 6. Goods Received Items
        Schema::create('goods_received_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grn_id')->constrained('goods_received_notes')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->unsignedInteger('received_qty');
            $table->unsignedInteger('accepted_qty');
            $table->unsignedInteger('rejected_qty')->default(0);
            $table->string('batch_number', 100);
            $table->date('mfg_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('unit_purchase_price', 10, 2)->default(0);
            $table->decimal('allocated_landed_cost', 10, 2)->default(0);
            $table->decimal('effective_unit_cost', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });

        // 7. Product Batches (For FIFO/FEFO and Expiry Tracking)
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('grn_item_id')->nullable()->constrained('goods_received_items')->nullOnDelete();
            $table->string('batch_number', 100)->index();
            $table->date('mfg_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->unsignedInteger('initial_qty');
            $table->unsignedInteger('current_qty');
            $table->decimal('unit_purchase_price', 10, 2)->default(0);
            $table->decimal('landed_unit_cost', 10, 2)->default(0);
            $table->string('status', 30)->default('active')->index(); // active, near_expiry, quarantined, expired, depleted
            $table->timestamps();

            $table->index(['product_id', 'status', 'expiry_date']);
        });

        // 8. Purchase Bills (Accounts Payable)
        Schema::create('purchase_bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number', 50)->unique();
            $table->foreignId('grn_id')->nullable()->constrained('goods_received_notes')->nullOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('restrict');
            $table->string('vendor_invoice_no', 100)->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('total_taxable', 12, 2)->default(0);
            $table->decimal('cgst', 12, 2)->default(0);
            $table->decimal('sgst', 12, 2)->default(0);
            $table->decimal('igst', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('payment_status', 30)->default('unpaid'); // unpaid, partially_paid, paid
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_bills');
        Schema::dropIfExists('product_batches');
        Schema::dropIfExists('goods_received_items');
        Schema::dropIfExists('goods_received_notes');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('vendor_products');
        Schema::dropIfExists('vendors');
    }
};
