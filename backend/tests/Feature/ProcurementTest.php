<?php

namespace Tests\Feature;

use App\Models\GoodsReceivedNote;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\PurchaseBill;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorProduct;
use App\Services\ProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementTest extends TestCase
{
    /**
     * Test full procurement flow: Vendor creation, GRN processing, Landed Cost calculation,
     * batch generation, and automatic live inventory increment.
     */
    public function test_vendor_and_inward_stock_flow_with_landed_costs(): void
    {
        $admin = User::first() ?? User::factory()->create();

        // 1. Create a Vendor
        $vendor = Vendor::create([
            'name' => 'Kiran Varma',
            'company_name' => 'Sardar Organic Supplies',
            'gstin' => '24AAACS1234A1Z1',
            'state' => 'Gujarat',
            'state_code' => '24',
            'payment_terms_days' => 30,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'company_name' => 'Sardar Organic Supplies']);

        // 2. Fetch a catalog product and record its initial stock
        $product = Product::first();
        $this->assertNotNull($product, 'Catalog product should exist.');
        $initialStock = (int) $product->stock_qty;

        // Map product to vendor
        VendorProduct::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'purchase_price' => 150.00,
            'tax_rate' => 5.00,
            'moq' => 10,
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('vendor_products', [
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'purchase_price' => 150.00,
        ]);

        // 3. Process Inward Stock (GRN) through ProcurementService
        /** @var ProcurementService $procurementService */
        $procurementService = app(ProcurementService::class);

        $inwardData = [
            'vendor_id' => $vendor->id,
            'vendor_invoice_no' => 'SOS-INV-2026-99',
            'invoice_date' => now()->toDateString(),
            'received_date' => now()->toDateString(),
            'inward_freight_charge' => 100.00,
            'inward_handling_charge' => 50.00, // Total expenses = 150.00
            'notes' => 'Prakruti organic fresh batch delivery',
            'items' => [
                [
                    'product_id' => $product->id,
                    'received_qty' => 50,
                    'accepted_qty' => 50,
                    'rejected_qty' => 0,
                    'batch_number' => 'BATCH-TEST-2026-A1',
                    'mfg_date' => now()->subDays(5)->toDateString(),
                    'expiry_date' => now()->addMonths(12)->toDateString(),
                    'unit_purchase_price' => 150.00,
                    'tax_rate' => 5.00,
                ],
            ],
        ];

        $grn = $procurementService->processGoodsReceivedNote($inwardData, $admin);

        // 4. Assert GRN and Line Item created
        $this->assertInstanceOf(GoodsReceivedNote::class, $grn);
        $this->assertEquals(50 * 150.00, $grn->total_items_taxable); // 7500.00
        $this->assertEquals(round(7500.00 * 0.05, 2), $grn->total_tax_amount); // 375.00

        // Landed cost = 7500 + 375 + 150 expenses = 8025.00
        $this->assertEquals(8025.00, $grn->total_landed_cost);

        // 5. Assert Effective Unit Landed Cost per item
        // Freight/Handling allocated per unit = 150 / 50 = 3.00
        // Effective Landed Cost = 150.00 + 3.00 = 153.00 per unit
        $grnItem = $grn->items()->first();
        $this->assertEquals(153.00, $grnItem->effective_unit_cost);

        // 6. Assert ProductBatch record created
        $batch = ProductBatch::where('batch_number', 'BATCH-TEST-2026-A1')->first();
        $this->assertNotNull($batch);
        $this->assertEquals(50, $batch->current_qty);
        $this->assertEquals(153.00, $batch->landed_unit_cost);
        $this->assertEquals('active', $batch->status);

        // 7. Assert Live Physical Stock Increased by 50
        $product->refresh();
        $this->assertEquals($initialStock + 50, $product->stock_qty);

        // 8. Assert InventoryTransaction logged
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'type' => 'purchase',
            'quantity' => 50,
            'new_stock' => $initialStock + 50,
            'reference_type' => GoodsReceivedNote::class,
            'reference_id' => $grn->id,
        ]);

        // 9. Assert PurchaseBill created for Accounts Payable
        $bill = PurchaseBill::where('grn_id', $grn->id)->first();
        $this->assertNotNull($bill);
        $this->assertEquals(7500.00, $bill->total_taxable);
        $this->assertEquals(7875.00, $bill->total_amount); // 7500 + 375 GST
        $this->assertEquals('unpaid', $bill->payment_status);
        $this->assertEquals(187.50, $bill->cgst); // 375 / 2 since intra-state Gujarat
        $this->assertEquals(187.50, $bill->sgst);
        $this->assertEquals(0.00, $bill->igst);
    }
}
