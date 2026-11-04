<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariation extends Model
{
    protected $fillable = [
        'product_id', 'attr_id', 'attr_val', 'sku', 'price', 'sale_price', 'cost_price',
        'stock_qty', 'reserved_stock', 'image', 'weight', 'status',
    ];

    protected $hidden = [
        'cost_price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attr_id');
    }

    public function multiAttributeValues(): HasMany
    {
        return $this->hasMany(VariationAttributeValue::class, 'product_variation_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class, 'product_variation_id');
    }

    public function getAvailableStockAttribute(): int
    {
        if ($this->relationLoaded('batches') && $this->batches->isNotEmpty()) {
            $cutoff = now()->addDays(60);
            $batchQty = (int) $this->batches
                ->filter(fn (ProductBatch $batch) => $batch->status === 'active'
                    && (int) $batch->current_qty > 0
                    && (! $batch->expiry_date || $batch->expiry_date->gt($cutoff)))
                ->sum('current_qty');

            return max($batchQty - (int) ($this->reserved_stock ?? 0), 0);
        }

        if ($this->batches()->exists()) {
            $batchQty = (int) $this->batches()->availableForSale()->sum('current_qty');
            return max($batchQty - (int) ($this->reserved_stock ?? 0), 0);
        }

        return max((int) $this->stock_qty - (int) ($this->reserved_stock ?? 0), 0);
    }

    public function getDisplayPriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->price ?? 0);
    }

    public function isInStock(): bool
    {
        return $this->available_stock > 0;
    }
}
