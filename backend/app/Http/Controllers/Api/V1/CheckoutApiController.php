<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ShippingMethod;
use App\Services\BillDiscountService;
use App\Services\GstService;
use App\Services\InventoryService;
use App\Services\ShippingChargeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CheckoutApiController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.var_id' => 'nullable|exists:product_variations,id',
            'items.*.qty' => 'required|integer|min:1',
            'shipping_address' => 'nullable|string',
            'billing_address' => 'nullable|string',
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'payment_method' => 'nullable|string',
            'order_type' => 'nullable|in:standard,family_pack',
            'is_business_order' => 'nullable|boolean',
            'business_name' => 'nullable|string|max:255',
            'customer_gstin' => ['nullable', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'customer_state' => 'nullable|string|max:100',
        ]);

        $user = auth()->user();

        return DB::transaction(function () use ($request, $user) {
            $subtotal = 0;
            $orderItemsData = [];

            // 1. Calculate Authoritative Subtotal & Validate Stock
            foreach ($request->items as $itemReq) {
                $product = Product::with(['category', 'subCategory', 'subSubCategory'])->lockForUpdate()->find($itemReq['product_id']);

                if (! $product || $product->status !== 'active') {
                    throw new \Exception("Product #{$itemReq['product_id']} is unavailable.");
                }

                $variation = null;
                if (! empty($itemReq['var_id'])) {
                    $variation = ProductVariation::where('id', $itemReq['var_id'])
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $variation || $variation->status !== 'active') {
                        throw new \Exception("Selected package for '{$product->name}' is unavailable.");
                    }
                } else {
                    $variation = ProductVariation::where('product_id', $product->id)
                        ->where('status', 'active')
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->first();
                }

                $stockItem = $variation ?: $product;
                $availableStock = (int) $stockItem->stock_qty;
                if ($availableStock <= 0) {
                    throw new \Exception("Product '{$product->name}' is currently out of stock.");
                }
                if ($availableStock < $itemReq['qty']) {
                    throw new \Exception("Product '{$product->name}' does not have sufficient stock. Available: {$availableStock}.");
                }

                // Authoritative Database Price (MRP - Inclusive of GST)
                $itemPrice = $variation
                    ? (float) ($variation->sale_price ?? $variation->price ?? $product->sale_price ?? $product->price)
                    : (float) ($product->sale_price ?? $product->price);
                $lineTotal = $itemPrice * $itemReq['qty'];
                $subtotal += $lineTotal;

                $orderItemsData[] = [
                    'product' => $product,
                    'stock_item' => $stockItem,
                    'variation' => $variation,
                    'product_id' => $product->id,
                    'var_id' => $variation?->id,
                    'product_name' => $product->name,
                    'sku' => $variation?->sku ?: $product->sku,
                    'hsn_code' => $product->hsn_code ?: '30049011',
                    'qty' => $itemReq['qty'],
                    'price' => $itemPrice,
                    'gst_pct' => app(GstService::class)->productGstPercentage($product),
                    'line_total' => $lineTotal,
                ];
            }

            // 2. Automatic bill discount from admin settings
            $discountRule = app(BillDiscountService::class)->calculate($subtotal);
            $discount = (float) $discountRule['discount'];

            // 3. Reverse GST Extraction from inclusive prices
            $gstService = app(GstService::class);
            $gstAmt = $gstService->calculateTotal($orderItemsData, $subtotal, $discount);

            // 4. Authoritative Shipping Calculation
            $shipId = null;
            $shipMethod = null;
            if ($request->filled('shipping_method_id')) {
                $shipMethod = ShippingMethod::find($request->shipping_method_id);
                if ($shipMethod) {
                    $shipId = $shipMethod->id;
                }
            }
            $shipping = app(ShippingChargeService::class)->calculate($subtotal, $shipMethod);
            $shipCharge = $shipping['charge'];

            // Inclusive Pricing Total: Subtotal already contains GST
            $total = max(0, $subtotal - $discount) + $shipCharge;

            // Calculate GST Split (Intra-state CGST+SGST vs Inter-state IGST)
            $customerState = $request->get('customer_state', 'Gujarat');
            $sellerState = 'Gujarat';
            $gstSplit = $gstService->calculateSplit($gstAmt, $sellerState, $customerState);

            $orderNum = 'PRK-' . strtoupper(Str::random(6)) . '-' . rand(100, 999);
            $invoiceNum = Order::generateInvoiceNumber();
            $isBusiness = $request->boolean('is_business_order') || ! empty($request->customer_gstin);

            // 5. Create Order Record
            $orderPayload = [
                'order_num' => $orderNum,
                'invoice_number' => $invoiceNum,
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'gst_amt' => $gstAmt,
                'cgst_amt' => $gstSplit['cgst'],
                'sgst_amt' => $gstSplit['sgst'],
                'igst_amt' => $gstSplit['igst'],
                'seller_state' => $sellerState,
                'customer_state' => $customerState,
                'is_business_order' => $isBusiness,
                'business_name' => $isBusiness ? $request->get('business_name') : null,
                'customer_gstin' => $isBusiness ? strtoupper(trim($request->get('customer_gstin'))) : null,
                'ship_charge' => $shipCharge,
                'total' => $total,
                'ship_id' => $shipId,
                'status' => 'pending',
                'pay_status' => 'pending',
                'payment_method' => $request->get('payment_method', 'cod'),
                'ship_addr' => $request->get('shipping_address', 'Default Address'),
                'bill_addr' => $request->get('billing_address', 'Default Address'),
            ];

            if (Schema::hasColumn('orders', 'order_type')) {
                $orderPayload['order_type'] = $request->get('order_type') === 'family_pack' ? 'family_pack' : 'standard';
            }

            $order = Order::create($orderPayload);

            // 6. Create Order Items & Deduct Stock using FEFO Batch Allocation
            $inventoryService = app(InventoryService::class);
            $totalCogs = 0.0;
            foreach ($orderItemsData as $itemData) {
                // Deduct stock using FEFO (First Expired First Out)
                $fefoResult = $inventoryService->deductStockWithFefo(
                    $itemData['stock_item'],
                    $itemData['qty'],
                    "Order #{$order->order_num}",
                    $order
                );

                if (!empty($fefoResult['allocations'])) {
                    foreach ($fefoResult['allocations'] as $alloc) {
                        $cost = (float) ($alloc['batch']->effective_unit_cost ?? $alloc['batch']->unit_cost ?? $itemData['price']);
                        $totalCogs += ((int) $alloc['deducted'] * $cost);
                    }
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'var_id' => $itemData['var_id'],
                    'product_name' => $itemData['product_name'],
                    'sku' => $itemData['sku'],
                    'hsn_code' => $itemData['hsn_code'],
                    'batch_number' => $fefoResult['primary_batch_number'] ?? null,
                    'qty' => $itemData['qty'],
                    'price' => $itemData['price'],
                    'gst_pct' => $itemData['gst_pct'],
                    'line_total' => $itemData['line_total'],
                ]);
            }

            // 7. Record Order History
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'pending',
                'note' => 'Order created via storefront checkout',
            ]);

            // 8. Record Payment Entry
            Payment::create([
                'order_id' => $order->id,
                'amount' => $total,
                'method' => $request->get('payment_method', 'cod'),
                'status' => 'pending',
                'txn_id' => 'TXN-' . time() . '-' . rand(1000, 9999),
            ]);

            // 9. Generate Rule 46 GST Tax Invoice PDF, Post Accounting Journal, and Mail
            try {
                $invoiceService = app(\App\Services\InvoiceService::class);
                $pdfPath = $invoiceService->generatePdf($order);

                $accountingService = app(\App\Services\AccountingService::class);
                $accountingService->postSalesOrder($order, $totalCogs);

                $email = $user?->email ?? $request->get('email');
                if ($email) {
                    \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\OrderInvoiceMail($order, $pdfPath));
                }

                // If customer has an active family pack, automate 30-day replenishment countdown
                if ($user && \Illuminate\Support\Facades\Schema::hasTable('family_packs')) {
                    \App\Models\FamilyPack::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->update([
                            'next_purchase_at' => now()->addDays(30),
                            'next_reminder_at' => now()->addDays(27),
                            'reminder_sent_at' => null,
                        ]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Order post-processing error: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'data' => new OrderResource($order->load(['items.product', 'items.variation', 'payment'])),
            ], 201);
        });
    }
}
