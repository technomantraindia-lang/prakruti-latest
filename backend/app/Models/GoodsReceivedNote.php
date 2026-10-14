<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GoodsReceivedNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_number',
        'purchase_order_id',
        'vendor_id',
        'vendor_invoice_no',
        'invoice_date',
        'received_date',
        'status',
        'inward_freight_charge',
        'inward_handling_charge',
        'total_items_taxable',
        'total_tax_amount',
        'total_landed_cost',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'received_date' => 'date',
        'inward_freight_charge' => 'decimal:2',
        'inward_handling_charge' => 'decimal:2',
        'total_items_taxable' => 'decimal:2',
        'total_tax_amount' => 'decimal:2',
        'total_landed_cost' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceivedItem::class, 'grn_id');
    }

    public function purchaseBill(): HasOne
    {
        return $this->hasOne(PurchaseBill::class, 'grn_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public static function generateGrnNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return 'GRN-' . $year . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
