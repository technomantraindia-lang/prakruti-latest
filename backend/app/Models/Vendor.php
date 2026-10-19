<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'company_name',
        'gstin',
        'pan',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'state_code',
        'pincode',
        'payment_terms_days',
        'bank_name',
        'bank_account_no',
        'bank_ifsc',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'payment_terms_days' => 'integer',
    ];

    public function vendorProducts(): HasMany
    {
        return $this->hasMany(VendorProduct::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'vendor_products')
            ->withPivot(['vendor_sku', 'purchase_price', 'tax_rate', 'moq', 'lead_time_days', 'is_primary'])
            ->withTimestamps();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function goodsReceivedNotes(): HasMany
    {
        return $this->hasMany(GoodsReceivedNote::class);
    }

    public function purchaseBills(): HasMany
    {
        return $this->hasMany(PurchaseBill::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ? "{$this->company_name} ({$this->name})" : $this->name;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
