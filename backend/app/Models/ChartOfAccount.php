<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type', // asset, liability, equity, revenue, expense
        'parent_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(JournalEntryItem::class, 'account_id');
    }

    public function currentBalance(): float
    {
        $debit = (float) $this->items()->sum('debit');
        $credit = (float) $this->items()->sum('credit');

        // Assets and Expenses are debit normal balance. Liabilities, Equity, Revenues are credit normal balance.
        if (in_array(strtolower($this->type), ['asset', 'expense'])) {
            return round($debit - $credit, 2);
        }

        return round($credit - $debit, 2);
    }
}
