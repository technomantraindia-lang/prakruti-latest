<?php

namespace App\Services;

use App\Models\GoodsReceivedItem;
use App\Models\GoodsReceivedNote;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductVariation;
use App\Models\PurchaseBill;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDebitNote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class ProcurementService
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Process an Inward Stock Goods Received Note (GRN).
     *
     * @param array $data
     * @param User|null $user
     * @return GoodsReceivedNote
     */
    public function processGoodsReceivedNote(array $data, ?User $user = null): GoodsReceivedNote
    {
        return DB::transaction(function () use ($data, $user) {
            $vendor = Vendor::findOrFail($data['vendor_id']);
            $freight = (float) ($data['inward_freight_charge'] ?? 0);
            $handling = (float) ($data['inward_handling_charge'] ?? 0);
            $totalInwardExpenses = $freight + $handling;

            $itemsData = $data['items'] ?? [];
            if (empty($itemsData)) {
                throw new InvalidArgumentException('GRN must contain at least one item.');
            }

            // 1. Calculate total taxable amount across all accepted items for proportional allocation
            $totalTaxable = 0.0;
            $totalTax = 0.0;
            $processedItems = [];

            foreach ($itemsData as $row) {
                $receivedQty = (int) ($row['received_qty'] ?? 0);
                $acceptedQty = (int) ($row['accepted_qty'] ?? $receivedQty);
                $rejectedQty = (int) ($row['rejected_qty'] ?? ($receivedQty - $acceptedQty));

                if ($receivedQty <= 0) {
                    throw new InvalidArgumentException('Received quantity must be greater than zero.');
                }
                if ($acceptedQty < 0 || $acceptedQty > $receivedQty) {
                    throw new InvalidArgumentException('Accepted quantity cannot be negative or exceed received quantity.');
                }

                $unitPrice = (float) ($row['unit_purchase_price'] ?? 0);
                $taxRate = (float) ($row['tax_rate'] ?? 0);
                $lineTaxable = $acceptedQty * $unitPrice;
                $lineTax = round($lineTaxable * ($taxRate / 100), 2);

                $totalTaxable += $lineTaxable;
                $totalTax += $lineTax;

                $processedItems[] = [
                    'product_id' => $row['product_id'],
                    'product_variation_id' => $row['product_variation_id'] ?? null,
                    'purchase_order_item_id' => $row['purchase_order_item_id'] ?? null,
                    'received_qty' => $receivedQty,
                    'accepted_qty' => $acceptedQty,
                    'rejected_qty' => $rejectedQty,
                    'batch_number' => trim($row['batch_number'] ?? 'BATCH-' . date('YmdHis')),
                    'mfg_date' => ! empty($row['mfg_date']) ? $row['mfg_date'] : null,
                    'expiry_date' => ! empty($row['expiry_date']) ? $row['expiry_date'] : null,
                    'unit_purchase_price' => $unitPrice,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'line_taxable' => $lineTaxable,
                    'rejection_reason' => $row['rejection_reason'] ?? null,
                ];
            }

            // 2. Allocate landed expenses proportionally to each line item based on purchase value
            $totalLandedCost = $totalTaxable + $totalTax + $totalInwardExpenses;

            $grn = GoodsReceivedNote::create([
                'grn_number' => GoodsReceivedNote::generateGrnNumber(),
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'vendor_id' => $vendor->id,
                'vendor_invoice_no' => $data['vendor_invoice_no'] ?? null,
                'invoice_date' => ! empty($data['invoice_date']) ? $data['invoice_date'] : now()->toDateString(),
                'received_date' => ! empty($data['received_date']) ? $data['received_date'] : now()->toDateString(),
                'status' => 'verified',
                'inward_freight_charge' => $freight,
                'inward_handling_charge' => $handling,
                'total_items_taxable' => $totalTaxable,
                'total_tax_amount' => $totalTax,
                'total_landed_cost' => $totalLandedCost,
                'notes' => $data['notes'] ?? null,
                'received_by' => $user?->id,
            ]);

            // 3. Process each line item, batch, and inventory transaction
            foreach ($processedItems as $item) {
                $allocatedLandedExpense = 0.0;
                if ($totalTaxable > 0 && $item['accepted_qty'] > 0) {
                    $ratio = $item['line_taxable'] / $totalTaxable;
                    $allocatedLandedExpense = round($totalInwardExpenses * $ratio, 2);
                }

                $effectiveUnitCost = $item['accepted_qty'] > 0
                    ? round($item['unit_purchase_price'] + ($allocatedLandedExpense / $item['accepted_qty']), 2)
                    : $item['unit_purchase_price'];

                $itemTotalAmount = $item['line_taxable'] + $item['tax_amount'] + $allocatedLandedExpense;

                $grnItem = GoodsReceivedItem::create([
                    'grn_id' => $grn->id,
                    'product_id' => $item['product_id'],
                    'product_variation_id' => $item['product_variation_id'],
                    'purchase_order_item_id' => $item['purchase_order_item_id'],
                    'received_qty' => $item['received_qty'],
                    'accepted_qty' => $item['accepted_qty'],
                    'rejected_qty' => $item['rejected_qty'],
                    'batch_number' => $item['batch_number'],
                    'mfg_date' => $item['mfg_date'],
                    'expiry_date' => $item['expiry_date'],
                    'unit_purchase_price' => $item['unit_purchase_price'],
                    'allocated_landed_cost' => $allocatedLandedExpense,
                    'effective_unit_cost' => $effectiveUnitCost,
                    'tax_rate' => $item['tax_rate'],
                    'tax_amount' => $item['tax_amount'],
                    'total_amount' => $itemTotalAmount,
                    'rejection_reason' => $item['rejection_reason'],
                ]);

                // 4. If accepted_qty > 0, create ProductBatch and increase live inventory
                if ($item['accepted_qty'] > 0) {
                    ProductBatch::create([
                        'product_id' => $item['product_id'],
                        'product_variation_id' => $item['product_variation_id'],
                        'vendor_id' => $vendor->id,
                        'grn_item_id' => $grnItem->id,
                        'batch_number' => $item['batch_number'],
                        'mfg_date' => $item['mfg_date'],
                        'expiry_date' => $item['expiry_date'],
                        'initial_qty' => $item['accepted_qty'],
                        'current_qty' => $item['accepted_qty'],
                        'unit_purchase_price' => $item['unit_purchase_price'],
                        'landed_unit_cost' => $effectiveUnitCost,
                        'status' => 'active',
                    ]);

                    $targetItem = ! empty($item['product_variation_id'])
                        ? ProductVariation::findOrFail($item['product_variation_id'])
                        : Product::findOrFail($item['product_id']);

                    $this->inventoryService->increaseStock(
                        $targetItem,
                        $item['accepted_qty'],
                        "GRN Inward {$grn->grn_number} from {$vendor->display_name}",
                        $grn,
                        $user,
                        [
                            'batch_number' => $item['batch_number'],
                            'effective_unit_cost' => $effectiveUnitCost,
                            'unit_purchase_price' => $item['unit_purchase_price'],
                            'expiry_date' => $item['expiry_date'],
                            'vendor_id' => $vendor->id,
                        ]
                    );
                }

                // Update PO Item if linked
                if (! empty($item['purchase_order_item_id'])) {
                    $poItem = PurchaseOrderItem::find($item['purchase_order_item_id']);
                    if ($poItem) {
                        $poItem->received_qty += $item['accepted_qty'];
                        $poItem->save();
                    }
                }
            }

            // 5. Update parent Purchase Order status if linked
            if ($grn->purchase_order_id) {
                $po = PurchaseOrder::with('items')->find($grn->purchase_order_id);
                if ($po) {
                    $allReceived = true;
                    $partiallyReceived = false;
                    foreach ($po->items as $pi) {
                        if ($pi->received_qty < $pi->ordered_qty) {
                            $allReceived = false;
                        }
                        if ($pi->received_qty > 0) {
                            $partiallyReceived = true;
                        }
                    }
                    $po->status = $allReceived ? 'received' : ($partiallyReceived ? 'partially_received' : $po->status);
                    $po->save();
                }
            }

            // 6. Generate PurchaseBill for Accounts Payable
            $companyGstin = Setting::where('key', 'gst_number')->value('value') ?? '24AAACP0000A1Z5';
            $companyStateCode = substr($companyGstin, 0, 2);
            $vendorStateCode = $vendor->state_code ?? ($vendor->gstin ? substr($vendor->gstin, 0, 2) : $companyStateCode);

            $cgst = 0.0;
            $sgst = 0.0;
            $igst = 0.0;

            if ($vendorStateCode === $companyStateCode) {
                $cgst = round($totalTax / 2, 2);
                $sgst = round($totalTax - $cgst, 2);
            } else {
                $igst = $totalTax;
            }

            $billTotal = $totalTaxable + $totalTax;
            $dueDate = now()->addDays($vendor->payment_terms_days ?: 30)->toDateString();

            $bill = PurchaseBill::create([
                'bill_number' => PurchaseBill::generateBillNumber(),
                'grn_id' => $grn->id,
                'vendor_id' => $vendor->id,
                'vendor_invoice_no' => $grn->vendor_invoice_no,
                'invoice_date' => $grn->invoice_date,
                'due_date' => $dueDate,
                'total_taxable' => $totalTaxable,
                'cgst' => $cgst,
                'sgst' => $sgst,
                'igst' => $igst,
                'total_amount' => $billTotal,
                'paid_amount' => 0.0,
                'payment_status' => 'unpaid',
            ]);

            // Real-time synchronization to General Ledger
            try {
                $accountingService = app(AccountingService::class);
                $accountingService->postPurchaseBill($bill);
            } catch (\Throwable $e) {
                Log::error('Accounting error on Purchase Bill: ' . $e->getMessage());
            }

            return $grn->load(['items.product', 'items.batch', 'vendor', 'purchaseBill']);
        });
    }

    /**
     * Process Return to Vendor (RTV) and generate Vendor Debit Note.
     */
    public function createPurchaseReturn(array $data, ?User $user = null): PurchaseReturn
    {
        return DB::transaction(function () use ($data, $user) {
            $vendor = Vendor::findOrFail($data['vendor_id']);
            $grn = ! empty($data['grn_id']) ? GoodsReceivedNote::find($data['grn_id']) : null;
            $purchaseBill = ! empty($data['purchase_bill_id']) ? PurchaseBill::find($data['purchase_bill_id']) : null;

            $year = date('Y');
            $count = PurchaseReturn::whereYear('created_at', $year)->count() + 1;
            $returnNum = 'PR-' . $year . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);

            $totalTaxable = 0.0;
            $totalTax = 0.0;
            $processedItems = [];

            foreach ($data['items'] as $item) {
                $qty = (int) $item['return_qty'];
                if ($qty <= 0) {
                    continue;
                }

                $product = Product::findOrFail($item['product_id']);
                $unitCost = (float) $item['unit_cost'];
                $taxRate = (float) ($item['tax_rate'] ?? $product->gst_percentage ?? 12);

                $lineTaxable = round($qty * $unitCost, 2);
                $lineTax = round(($lineTaxable * $taxRate) / 100, 2);
                $lineTotal = round($lineTaxable + $lineTax, 2);

                $totalTaxable += $lineTaxable;
                $totalTax += $lineTax;

                $processedItems[] = [
                    'product_id' => $product->id,
                    'product_variation_id' => $item['product_variation_id'] ?? null,
                    'batch_number' => $item['batch_number'] ?? null,
                    'return_qty' => $qty,
                    'unit_cost' => $unitCost,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'total_amount' => $lineTotal,
                ];

                // Deduct from batch if batch_number specified
                if (! empty($item['batch_number'])) {
                    $batch = ProductBatch::where('product_id', $product->id)
                        ->where('batch_number', $item['batch_number'])
                        ->first();
                    if ($batch) {
                        $batch->current_qty = max(0, $batch->current_qty - $qty);
                        if ($batch->current_qty == 0) {
                            $batch->status = 'depleted';
                        }
                        $batch->save();
                    }
                }
            }

            // Determine tax split based on vendor state code vs company state code
            $companyGstin = Setting::where('key', 'gst_number')->value('value') ?? '24AAACP0000A1Z5';
            $companyStateCode = substr($companyGstin, 0, 2);
            $vendorStateCode = $vendor->state_code ?? ($vendor->gstin ? substr($vendor->gstin, 0, 2) : $companyStateCode);

            $cgst = 0.0;
            $sgst = 0.0;
            $igst = 0.0;
            if ($vendorStateCode === $companyStateCode) {
                $cgst = round($totalTax / 2, 2);
                $sgst = round($totalTax - $cgst, 2);
            } else {
                $igst = $totalTax;
            }

            $grandTotal = round($totalTaxable + $totalTax, 2);

            $return = PurchaseReturn::create([
                'return_number' => $returnNum,
                'vendor_id' => $vendor->id,
                'grn_id' => $grn?->id,
                'purchase_bill_id' => $purchaseBill?->id,
                'status' => 'completed',
                'reason' => $data['reason'] ?? 'Returned to Vendor',
                'total_taxable' => $totalTaxable,
                'cgst' => $cgst,
                'sgst' => $sgst,
                'igst' => $igst,
                'total_amount' => $grandTotal,
            ]);

            foreach ($processedItems as $pItem) {
                $pItem['purchase_return_id'] = $return->id;
                PurchaseReturnItem::create($pItem);
            }

            // Generate Vendor Debit Note
            $dnCount = VendorDebitNote::whereYear('created_at', $year)->count() + 1;
            $dnNumber = 'DN-' . $year . '-' . str_pad((string) $dnCount, 4, '0', STR_PAD_LEFT);

            $debitNote = VendorDebitNote::create([
                'debit_note_number' => $dnNumber,
                'purchase_return_id' => $return->id,
                'vendor_id' => $vendor->id,
                'purchase_bill_id' => $purchaseBill?->id,
                'total_amount' => $grandTotal,
                'itc_reversal_status' => 'reversed',
                'notes' => 'Debit note against return ' . $returnNum . '. Reason: ' . ($data['reason'] ?? 'RTV'),
            ]);

            // Real-time double-entry posting to General Ledger
            try {
                $accountingService = app(AccountingService::class);
                $accountingService->postPurchaseReturn($debitNote);
            } catch (\Throwable $e) {
                Log::error('Accounting error on Purchase Return: ' . $e->getMessage());
            }

            return $return->load(['items.product', 'vendor', 'debitNote']);
        });
    }
}
