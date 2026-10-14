<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'product_id',
        'product_variation_id',
        'vendor_sku',
        'purchase_price',
        'tax_rate',
        'moq',
        'lead_time_days',
        'is_primary',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'moq' => 'integer',
        'lead_time_days' => 'integer',
        'is_primary' => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }
}
