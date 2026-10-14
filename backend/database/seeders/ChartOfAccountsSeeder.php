<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Current Assets
            ['code' => '1000', 'name' => 'Cash on Hand', 'type' => 'asset'],
            ['code' => '1010', 'name' => 'Bank / Online Clearing (Razorpay/COD)', 'type' => 'asset'],
            ['code' => '1100', 'name' => 'Inventory Asset (Finished Ayurvedic Goods)', 'type' => 'asset'],
            ['code' => '1200', 'name' => 'Input Tax Credit - CGST', 'type' => 'asset'],
            ['code' => '1210', 'name' => 'Input Tax Credit - SGST', 'type' => 'asset'],
            ['code' => '1220', 'name' => 'Input Tax Credit - IGST', 'type' => 'asset'],

            // Current Liabilities
            ['code' => '2000', 'name' => 'Accounts Payable (Sundry Creditors / Vendors)', 'type' => 'liability'],
            ['code' => '2100', 'name' => 'Output Tax Liability - CGST', 'type' => 'liability'],
            ['code' => '2110', 'name' => 'Output Tax Liability - SGST', 'type' => 'liability'],
            ['code' => '2120', 'name' => 'Output Tax Liability - IGST', 'type' => 'liability'],

            // Equity
            ['code' => '3000', 'name' => 'Owner Capital / Retained Earnings', 'type' => 'equity'],

            // Revenue
            ['code' => '4000', 'name' => 'Sales Revenue - Ayurvedic Products', 'type' => 'revenue'],
            ['code' => '4100', 'name' => 'Shipping & Delivery Charges Collected', 'type' => 'revenue'],

            // Expenses & COGS
            ['code' => '5000', 'name' => 'Cost of Goods Sold (COGS)', 'type' => 'expense'],
            ['code' => '5100', 'name' => 'Inward Freight & Handling Expense', 'type' => 'expense'],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::firstOrCreate(
                ['code' => $acc['code']],
                [
                    'name' => $acc['name'],
                    'type' => $acc['type'],
                    'is_active' => true,
                ]
            );
        }
    }
}
