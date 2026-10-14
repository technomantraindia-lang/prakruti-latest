<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_variation_id',
        'vendor_id',
        'grn_item_id',
        'batch_number',
        'mfg_date',
        'expiry_date',
        'initial_qty',
        'current_qty',
        'unit_purchase_price',
        'landed_unit_cost',
        'status',
    ];

    protected $casts = [
        'mfg_date' => 'date',
        'expiry_date' => 'date',
        'initial_qty' => 'integer',
        'current_qty' => 'integer',
        'unit_purchase_price' => 'decimal:2',
        'landed_unit_cost' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function grnItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedItem::class, 'grn_item_id');
    }

    /**
     * Check how many days remain before expiry.
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expiry_date->startOfDay(), false);
    }

    /**
     * Scope to find batches available for sale (active, non-zero qty, >60 days until expiry).
     */
    public function scopeAvailableForSale($query)
    {
        return $query->where('status', 'active')
            ->where('current_qty', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>', now()->addDays(60));
            });
    }

    /**
     * Scope to order by FEFO (First Expired, First Out).
     */
    public function scopeFefoOrder($query)
    {
        return $query->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, expiry_date ASC, id ASC');
    }
}
