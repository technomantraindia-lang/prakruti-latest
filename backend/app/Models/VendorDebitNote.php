<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorDebitNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'debit_note_number',
        'purchase_return_id',
        'vendor_id',
        'purchase_bill_id',
        'total_amount',
        'itc_reversal_status',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseBill()
    {
        return $this->belongsTo(PurchaseBill::class);
    }
}
