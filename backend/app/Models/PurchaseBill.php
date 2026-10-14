<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_number',
        'grn_id',
        'vendor_id',
        'vendor_invoice_no',
        'invoice_date',
        'due_date',
        'total_taxable',
        'cgst',
        'sgst',
        'igst',
        'total_amount',
        'paid_amount',
        'payment_status',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'total_taxable' => 'decimal:2',
        'cgst' => 'decimal:2',
        'sgst' => 'decimal:2',
        'igst' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'grn_id');
    }

    public static function generateBillNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return 'PB-' . $year . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public function getTaxableAmountAttribute(): float
    {
        return (float) ($this->attributes['total_taxable'] ?? 0);
    }

    public function getBillDateAttribute()
    {
        return $this->invoice_date;
    }
}
