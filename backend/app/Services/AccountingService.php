<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryItem;
use App\Models\Order;
use App\Models\PurchaseBill;
use App\Models\VendorDebitNote;
use Illuminate\Support\Facades\DB;
use Exception;

class AccountingService
{
    /**
     * Post a Purchase Bill to the General Ledger.
     * Dr: 1100 Inventory Asset (Taxable amount)
     * Dr: 1200 / 1210 / 1220 Input Tax Credit (CGST / SGST / IGST)
     * Cr: 2000 Accounts Payable (Total Bill Amount)
     */
    public function postPurchaseBill(PurchaseBill $bill): JournalEntry
    {
        return DB::transaction(function () use ($bill) {
            // Check if entry already exists
            $existing = JournalEntry::where('reference_type', PurchaseBill::class)
                ->where('reference_id', $bill->id)
                ->first();
            if ($existing) {
                return $existing;
            }

            $entryNumber = $this->generateEntryNumber('JE-PUR');

            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'entry_date' => $bill->bill_date ?? now()->toDateString(),
                'reference_type' => PurchaseBill::class,
                'reference_id' => $bill->id,
                'narration' => "Purchase Bill {$bill->bill_number} from vendor " . ($bill->vendor->name ?? 'Vendor'),
            ]);

            $accInventory = $this->getAccount('1100');
            $accPayable = $this->getAccount('2000');
            $accInputCgst = $this->getAccount('1200');
            $accInputSgst = $this->getAccount('1210');
            $accInputIgst = $this->getAccount('1220');

            // 1. Dr Inventory Asset
            JournalEntryItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $accInventory->id,
                'debit' => $bill->taxable_amount,
                'credit' => 0,
            ]);

            // 2. Dr Input CGST
            if ($bill->cgst > 0) {
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $accInputCgst->id,
                    'debit' => $bill->cgst,
                    'credit' => 0,
                ]);
            }

            // 3. Dr Input SGST
            if ($bill->sgst > 0) {
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $accInputSgst->id,
                    'debit' => $bill->sgst,
                    'credit' => 0,
                ]);
            }

            // 4. Dr Input IGST
            if ($bill->igst > 0) {
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $accInputIgst->id,
                    'debit' => $bill->igst,
                    'credit' => 0,
                ]);
            }

            // 5. Cr Accounts Payable
            JournalEntryItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $accPayable->id,
                'debit' => 0,
                'credit' => $bill->total_amount,
            ]);

            return $entry;
        });
    }

    /**
     * Post a Sales Order to the General Ledger.
     * Dr: 1010 Bank / Online Clearing (Total paid)
     * Cr: 4000 Sales Revenue (Net taxable product subtotal)
     * Cr: 4100 Shipping Revenue (Shipping charges if any)
     * Cr: 2100 / 2110 / 2120 Output Tax Liability (CGST / SGST / IGST)
     * Plus COGS entry if landed cost is known:
     * Dr: 5000 COGS
     * Cr: 1100 Inventory Asset
     */
    public function postSalesOrder(Order $order, float $cogs = 0): JournalEntry
    {
        return DB::transaction(function () use ($order, $cogs) {
            $existing = JournalEntry::where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->first();
            if ($existing) {
                return $existing;
            }

            $entryNumber = $this->generateEntryNumber('JE-SAL');

            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'entry_date' => $order->created_at ? $order->created_at->toDateString() : now()->toDateString(),
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'narration' => "Sales Invoice {$order->invoice_number} / Order #{$order->id} - Customer: {$order->shipping_name}",
            ]);

            $accBank = $this->getAccount('1010');
            $accSalesRev = $this->getAccount('4000');
            $accShippingRev = $this->getAccount('4100');
            $accOutputCgst = $this->getAccount('2100');
            $accOutputSgst = $this->getAccount('2110');
            $accOutputIgst = $this->getAccount('2120');

            $totalAmount = (float) $order->total_amount;
            $taxAmount = (float) $order->tax_amount;
            $shippingAmount = (float) ($order->shipping_charge ?? 0);
            
            // Taxable sales value = Total Amount - Tax Amount - Shipping Amount (assuming shipping is separate)
            $netSalesValue = round($totalAmount - $taxAmount - $shippingAmount, 2);
            if ($netSalesValue < 0) {
                $netSalesValue = round($totalAmount - $taxAmount, 2);
                $shippingAmount = 0;
            }

            // 1. Dr Bank / Gateway
            JournalEntryItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $accBank->id,
                'debit' => $totalAmount,
                'credit' => 0,
            ]);

            // 2. Cr Sales Revenue
            JournalEntryItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $accSalesRev->id,
                'debit' => 0,
                'credit' => $netSalesValue,
            ]);

            // 3. Cr Shipping Revenue
            if ($shippingAmount > 0) {
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $accShippingRev->id,
                    'debit' => 0,
                    'credit' => $shippingAmount,
                ]);
            }

            // 4. Cr Output Tax (CGST + SGST or IGST)
            // State check: Default Gujarat = 24
            $isInterstate = false;
            if ($order->customer_gstin && strlen($order->customer_gstin) >= 2) {
                $gstStateCode = substr($order->customer_gstin, 0, 2);
                if ($gstStateCode !== '24') {
                    $isInterstate = true;
                }
            } elseif ($order->shipping_state && !preg_match('/gujarat/i', $order->shipping_state)) {
                $isInterstate = true;
            }

            if ($taxAmount > 0) {
                if ($isInterstate) {
                    JournalEntryItem::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $accOutputIgst->id,
                        'debit' => 0,
                        'credit' => $taxAmount,
                    ]);
                } else {
                    $halfTax = round($taxAmount / 2, 2);
                    $remTax = round($taxAmount - $halfTax, 2);
                    JournalEntryItem::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $accOutputCgst->id,
                        'debit' => 0,
                        'credit' => $halfTax,
                    ]);
                    JournalEntryItem::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $accOutputSgst->id,
                        'debit' => 0,
                        'credit' => $remTax,
                    ]);
                }
            }

            // 5. Simultaneous COGS & Inventory Asset entry
            if ($cogs > 0) {
                $accCogs = $this->getAccount('5000');
                $accInventory = $this->getAccount('1100');

                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $accCogs->id,
                    'debit' => $cogs,
                    'credit' => 0,
                ]);

                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $accInventory->id,
                    'debit' => 0,
                    'credit' => $cogs,
                ]);
            }

            return $entry;
        });
    }

    /**
     * Post a Vendor Debit Note (Purchase Return) to the General Ledger.
     * Dr: 2000 Accounts Payable (Total Debit Note amount)
     * Cr: 1100 Inventory Asset (Taxable amount of returned stock)
     * Cr: 1200 / 1210 / 1220 Input Tax Credit (Tax reversal)
     */
    public function postPurchaseReturn(VendorDebitNote $debitNote): JournalEntry
    {
        return DB::transaction(function () use ($debitNote) {
            $existing = JournalEntry::where('reference_type', VendorDebitNote::class)
                ->where('reference_id', $debitNote->id)
                ->first();
            if ($existing) {
                return $existing;
            }

            $entryNumber = $this->generateEntryNumber('JE-DN');

            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'entry_date' => now()->toDateString(),
                'reference_type' => VendorDebitNote::class,
                'reference_id' => $debitNote->id,
                'narration' => "Debit Note {$debitNote->debit_note_number} for Return to " . ($debitNote->vendor->name ?? 'Vendor'),
            ]);

            $accPayable = $this->getAccount('2000');
            $accInventory = $this->getAccount('1100');
            $accInputCgst = $this->getAccount('1200');
            $accInputSgst = $this->getAccount('1210');
            $accInputIgst = $this->getAccount('1220');

            $return = $debitNote->purchaseReturn;
            $taxable = $return ? (float) $return->total_taxable : (float) $debitNote->total_amount;
            $cgst = $return ? (float) $return->cgst : 0;
            $sgst = $return ? (float) $return->sgst : 0;
            $igst = $return ? (float) $return->igst : 0;

            // 1. Dr Accounts Payable (reduce liability)
            JournalEntryItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $accPayable->id,
                'debit' => $debitNote->total_amount,
                'credit' => 0,
            ]);

            // 2. Cr Inventory Asset (remove goods valuation)
            JournalEntryItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $accInventory->id,
                'debit' => 0,
                'credit' => $taxable,
            ]);

            // 3. Cr Input Tax Credit reversal
            if ($cgst > 0) {
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $accInputCgst->id,
                    'debit' => 0,
                    'credit' => $cgst,
                ]);
            }
            if ($sgst > 0) {
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $accInputSgst->id,
                    'debit' => 0,
                    'credit' => $sgst,
                ]);
            }
            if ($igst > 0) {
                JournalEntryItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $accInputIgst->id,
                    'debit' => 0,
                    'credit' => $igst,
                ]);
            }

            return $entry;
        });
    }

    /**
     * Retrieve or instantiate account by code.
     */
    private function getAccount(string $code): ChartOfAccount
    {
        $account = ChartOfAccount::where('code', $code)->first();
        if (!$account) {
            throw new Exception("Chart of account with code {$code} not found. Please run ChartOfAccountsSeeder.");
        }
        return $account;
    }

    /**
     * Generate unique sequential entry number.
     */
    private function generateEntryNumber(string $prefix): string
    {
        $year = date('Y');
        $count = JournalEntry::where('entry_number', 'LIKE', "{$prefix}-{$year}-%")->count() + 1;
        return sprintf('%s-%s-%04d', $prefix, $year, $count);
    }
}
