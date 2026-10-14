<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckReorderLevelsCommand extends Command
{
    protected $signature = 'prakruti:check-reorder-levels';
    protected $description = 'Scan product stock levels and automatically generate Purchase Orders to vendors when stock drops to 10% or below.';

    public function handle(): int
    {
        $this->info('Starting automated stock level and vendor reorder audit...');

        $products = Product::where('status', 'active')
            ->with(['vendorProducts.vendor', 'batches'])
            ->get();

        $poCreatedCount = 0;

        foreach ($products as $product) {
            $availableStock = (int) $product->available_stock;

            // Calculate threshold (10% of standard batch inward or 10 units)
            $totalInward = (int) $product->batches()->sum('initial_qty');
            $threshold = $totalInward > 0 ? (int) ceil($totalInward * 0.10) : 10;
            if ($threshold < 5) {
                $threshold = 5;
            }

            if ($availableStock <= $threshold) {
                $this->warn("Product #{$product->id} [{$product->name}] has low stock: {$availableStock} units (Reorder threshold: {$threshold}).");

                // Find primary vendor
                $vendorProduct = $product->vendorProducts()
                    ->orderByDesc('is_primary')
                    ->first();

                if (! $vendorProduct || ! $vendorProduct->vendor) {
                    $this->error("  -> No active vendor mapping found for '{$product->name}'. Skipping PO creation.");
                    continue;
                }

                $vendor = $vendorProduct->vendor;

                // Check if an open Purchase Order already exists for this vendor and product
                $existingOpenPo = PurchaseOrder::where('vendor_id', $vendor->id)
                    ->whereIn('status', ['draft', 'sent'])
                    ->whereHas('items', function ($iq) use ($product) {
                        $iq->where('product_id', $product->id);
                    })
                    ->first();

                if ($existingOpenPo) {
                    $this->line("  -> Existing open PO {$existingOpenPo->po_number} already active for vendor {$vendor->name}.");
                    continue;
                }

                // Determine reorder quantity
                $reorderQty = $vendorProduct->moq > 0 ? (int) $vendorProduct->moq : 50;
                $unitCost = (float) ($vendorProduct->purchase_price > 0 ? $vendorProduct->purchase_price : 100);
                $subtotal = round($reorderQty * $unitCost, 2);
                $gstRate = (float) ($product->gst_percentage ?? 12);
                $taxAmount = round(($subtotal * $gstRate) / 100, 2);
                $totalAmount = round($subtotal + $taxAmount, 2);

                $po = PurchaseOrder::create([
                    'po_number' => PurchaseOrder::generatePoNumber(),
                    'vendor_id' => $vendor->id,
                    'status' => 'sent',
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'expected_date' => now()->addDays((int) ($vendorProduct->lead_time_days ?: 7))->toDateString(),
                    'notes' => "AUTOMATIC REORDER TRIGGER: Current available stock is {$availableStock} units (<= {$threshold} unit threshold). Auto-dispatched to vendor {$vendor->name}.",
                ]);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $product->id,
                    'ordered_qty' => $reorderQty,
                    'unit_cost' => $unitCost,
                    'tax_rate' => $gstRate,
                    'total_amount' => $totalAmount,
                ]);

                $poCreatedCount++;
                $this->info("  -> Auto-generated PO {$po->po_number} for {$reorderQty} units to vendor {$vendor->name} (Total: ₹{$totalAmount}).");

                Log::info("Low stock trigger: Automated PO {$po->po_number} created for {$product->name} to vendor {$vendor->name}.");
            }
        }

        $this->info("Reorder audit complete. Generated {$poCreatedCount} new automated Purchase Orders.");
        return Command::SUCCESS;
    }
}
