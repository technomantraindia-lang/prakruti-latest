<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GoodsReceivedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_id',
        'product_id',
        'product_variation_id',
        'purchase_order_item_id',
        'received_qty',
        'accepted_qty',
        'rejected_qty',
        'batch_number',
        'mfg_date',
        'expiry_date',
        'unit_purchase_price',
        'allocated_landed_cost',
        'effective_unit_cost',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'rejection_reason',
    ];

    protected $casts = [
        'received_qty' => 'integer',
        'accepted_qty' => 'integer',
        'rejected_qty' => 'integer',
        'mfg_date' => 'date',
        'expiry_date' => 'date',
        'unit_purchase_price' => 'decimal:2',
        'allocated_landed_cost' => 'decimal:2',
        'effective_unit_cost' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function batch(): HasOne
    {
        return $this->hasOne(ProductBatch::class, 'grn_item_id');
    }
}
