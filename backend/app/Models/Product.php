<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'sku', 'hsn_code', 'category_id', 'sub_category_id', 'sub_sub_category_id', 'brand_id',
        'price', 'sale_price', 'gst_percentage', 'cost_price', 'stock_qty', 'reserved_stock', 'low_stock_qty',
        'unit', 'min_order_qty', 'weight', 'image', 'short_desc', 'description', 'nutritional_info', 'product_information',
        'seo_title', 'seo_desc', 'seo_keywords', 'status', 'featured',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'gst_percentage' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'featured' => 'boolean',
        'product_information' => 'array',
    ];

    protected $hidden = [
        'cost_price',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function subSubCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_sub_category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class)->orderBy('id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class)->latest();
    }

    public function vendorProducts(): HasMany
    {
        return $this->hasMany(VendorProduct::class);
    }

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'vendor_products')
            ->withPivot(['vendor_sku', 'purchase_price', 'tax_rate', 'moq', 'lead_time_days', 'is_primary'])
            ->withTimestamps();
    }

    public function primaryVendorProduct()
    {
        return $this->hasOne(VendorProduct::class)->where('is_primary', true);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function activeBatches(): HasMany
    {
        return $this->hasMany(ProductBatch::class)->where('status', 'active');
    }

    public function getDisplayPriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->price);
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return route('media.file', ['path' => $this->image]);
        }

        $firstGallery = $this->relationLoaded('images') ? $this->images->first() : $this->images()->first();

        return $firstGallery ? $firstGallery->image_url : null;
    }

    public function getAvailableStockAttribute(): int
    {
        if ($this->batches()->exists()) {
            $batchQty = (int) $this->batches()->availableForSale()->sum('current_qty');
            return max($batchQty - (int) ($this->reserved_stock ?? 0), 0);
        }

        $activeVars = $this->relationLoaded('variations')
            ? $this->variations->where('status', 'active')
            : $this->variations()->where('status', 'active')->get();

        if ($activeVars->isNotEmpty()) {
            $varStock = (int) $activeVars->sum('stock_qty');
            return max($varStock - (int) ($this->reserved_stock ?? 0), 0);
        }

        return max((int) $this->stock_qty - (int) ($this->reserved_stock ?? 0), 0);
    }

    public function isInStock(): bool
    {
        return $this->available_stock > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock_qty <= $this->low_stock_qty;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
}
