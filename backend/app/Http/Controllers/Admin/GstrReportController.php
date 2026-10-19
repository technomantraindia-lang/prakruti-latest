<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PurchaseBill;
use App\Models\VendorDebitNote;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GstrReportController extends Controller
{
    /**
     * GSTR-1: Outward Supplies Return (Sales).
     */
    public function gstr1(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = Carbon::parse($month . '-01')->endOfMonth();

        $orders = Order::with(['items.product', 'items.variation'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->get();

        // 1. Table 4A: B2B Invoices (Registered Customers with GSTIN)
        $b2bInvoices = [];
        $b2bTaxable = 0.0;
        $b2bCgst = 0.0;
        $b2bSgst = 0.0;
        $b2bIgst = 0.0;

        // 2. Table 7: B2C Small Invoices (Unregistered Consumers)
        $b2cInvoices = [];
        $b2cTaxable = 0.0;
        $b2cCgst = 0.0;
        $b2cSgst = 0.0;
        $b2cIgst = 0.0;

        // 3. Table 12: HSN Summary
        $hsnSummary = [];

        // 4. Table 13: Document Summary
        $minInvoiceNum = null;
        $maxInvoiceNum = null;
        $docCount = $orders->count();

        foreach ($orders as $order) {
            $isB2b = $order->is_business_order && !empty($order->customer_gstin);
            $invNum = $order->invoice_number ?: ('INV-' . $order->id);

            if ($minInvoiceNum === null || strcmp($invNum, $minInvoiceNum) < 0) {
                $minInvoiceNum = $invNum;
            }
            if ($maxInvoiceNum === null || strcmp($invNum, $maxInvoiceNum) > 0) {
                $maxInvoiceNum = $invNum;
            }

            $orderTaxable = 0.0;
            $orderCgst = 0.0;
            $orderSgst = 0.0;
            $orderIgst = 0.0;

            // Determine if interstate
            $isInterstate = false;
            $stateCode = '24';
            if ($order->customer_gstin && strlen($order->customer_gstin) >= 2) {
                $stateCode = substr($order->customer_gstin, 0, 2);
                if ($stateCode !== '24') {
                    $isInterstate = true;
                }
            } elseif ($order->customer_state && !preg_match('/gujarat/i', $order->customer_state)) {
                $isInterstate = true;
            }

            foreach ($order->items as $item) {
                $gstRate = (float) ($item->product?->gst_percentage ?? 12);
                $totalInc = (float) ($item->line_total ?: ($item->price * $item->qty));
                $taxable = round(($totalInc * 100) / (100 + $gstRate), 2);
                $tax = round($totalInc - $taxable, 2);

                $cgst = 0; $sgst = 0; $igst = 0;
                if ($isInterstate) {
                    $igst = $tax;
                } else {
                    $cgst = round($tax / 2, 2);
                    $sgst = round($tax - $cgst, 2);
                }

                $orderTaxable += $taxable;
                $orderCgst += $cgst;
                $orderSgst += $sgst;
                $orderIgst += $igst;

                // Accumulate in HSN summary
                $hsn = $item->hsn_code ?: ($item->product?->hsn_code ?: '30049011');
                if (!isset($hsnSummary[$hsn])) {
                    $hsnSummary[$hsn] = [
                        'hsn' => $hsn,
                        'description' => $item->product_name ?: 'Organic Products',
                        'uqc' => 'NOS',
                        'total_qty' => 0,
                        'total_taxable' => 0.0,
                        'cgst' => 0.0,
                        'sgst' => 0.0,
                        'igst' => 0.0,
                        'total_tax' => 0.0,
                    ];
                }
                $hsnSummary[$hsn]['total_qty'] += (int) $item->qty;
                $hsnSummary[$hsn]['total_taxable'] += $taxable;
                $hsnSummary[$hsn]['cgst'] += $cgst;
                $hsnSummary[$hsn]['sgst'] += $sgst;
                $hsnSummary[$hsn]['igst'] += $igst;
                $hsnSummary[$hsn]['total_tax'] += $tax;
            }

            $invData = [
                'invoice_number' => $invNum,
                'invoice_date' => $order->created_at->format('d-M-Y'),
                'buyer_name' => $order->business_name ?: ($order->user?->name ?: 'Consumer'),
                'gstin' => $order->customer_gstin ?: 'URP',
                'place_of_supply' => ($order->customer_state ?: 'Gujarat') . " ({$stateCode})",
                'taxable' => $orderTaxable,
                'cgst' => $orderCgst,
                'sgst' => $orderSgst,
                'igst' => $orderIgst,
                'total' => (float) $order->total,
            ];

            if ($isB2b) {
                $b2bInvoices[] = $invData;
                $b2bTaxable += $orderTaxable;
                $b2bCgst += $orderCgst;
                $b2bSgst += $orderSgst;
                $b2bIgst += $orderIgst;
            } else {
                $b2cInvoices[] = $invData;
                $b2cTaxable += $orderTaxable;
                $b2cCgst += $orderCgst;
                $b2cSgst += $orderSgst;
                $b2cIgst += $orderIgst;
            }
        }

        // CSV Export handler
        if ($request->get('export') === 'csv') {
            return $this->exportGstr1Csv($month, $b2bInvoices, $b2cInvoices, $hsnSummary);
        }

        return view('admin.reports.gstr1', compact(
            'month',
            'b2bInvoices', 'b2bTaxable', 'b2bCgst', 'b2bSgst', 'b2bIgst',
            'b2cInvoices', 'b2cTaxable', 'b2cCgst', 'b2cSgst', 'b2cIgst',
            'hsnSummary',
            'docCount', 'minInvoiceNum', 'maxInvoiceNum'
        ));
    }

    /**
     * GSTR-2: Inward Supplies / Input Tax Credit (ITC Register).
     */
    public function gstr2(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = Carbon::parse($month . '-01')->endOfMonth();

        $purchaseBills = PurchaseBill::with('vendor')
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->latest()
            ->get();

        $totTaxable = (float) $purchaseBills->sum('total_taxable');
        $totCgst = (float) $purchaseBills->sum('cgst');
        $totSgst = (float) $purchaseBills->sum('sgst');
        $totIgst = (float) $purchaseBills->sum('igst');
        $totAmount = (float) $purchaseBills->sum('total_amount');
        $netItc = $totCgst + $totSgst + $totIgst;

        if ($request->get('export') === 'csv') {
            return $this->exportGstr2Csv($month, $purchaseBills);
        }

        return view('admin.reports.gstr2', compact(
            'month', 'purchaseBills',
            'totTaxable', 'totCgst', 'totSgst', 'totIgst', 'totAmount', 'netItc'
        ));
    }

    /**
     * GSTR-3B: Monthly Summary Return (Computation of Output Tax & ITC Set-off).
     */
    public function gstr3b(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = Carbon::parse($month . '-01')->endOfMonth();

        // 1. Outward Supplies (from Orders)
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->get();

        $outwardTaxable = 0.0;
        $outwardCgst = 0.0;
        $outwardSgst = 0.0;
        $outwardIgst = 0.0;

        foreach ($orders as $o) {
            $tax = (float) $o->gst_amt;
            $taxable = max(0, (float) $o->total - $tax);
            $outwardTaxable += $taxable;

            if ($o->customer_gstin && substr($o->customer_gstin, 0, 2) !== '24') {
                $outwardIgst += $tax;
            } elseif ($o->customer_state && !preg_match('/gujarat/i', $o->customer_state)) {
                $outwardIgst += $tax;
            } else {
                $half = round($tax / 2, 2);
                $outwardCgst += $half;
                $outwardSgst += round($tax - $half, 2);
            }
        }

        // 2. Eligible Inward ITC (from Purchase Bills)
        $bills = PurchaseBill::whereBetween('invoice_date', [$startDate, $endDate])->get();
        $inwardTaxable = (float) $bills->sum('total_taxable');
        $inwardCgst = (float) $bills->sum('cgst');
        $inwardSgst = (float) $bills->sum('sgst');
        $inwardIgst = (float) $bills->sum('igst');

        // 3. ITC Reversal (from Vendor Debit Notes / Purchase Returns)
        $debitNotes = VendorDebitNote::whereBetween('created_at', [$startDate, $endDate])->get();
        $revCgst = 0.0;
        $revSgst = 0.0;
        $revIgst = 0.0;
        foreach ($debitNotes as $dn) {
            $ret = $dn->purchaseReturn;
            if ($ret) {
                $revCgst += (float) $ret->cgst;
                $revSgst += (float) $ret->sgst;
                $revIgst += (float) $ret->igst;
            }
        }

        // Net ITC available
        $netItcCgst = max(0, $inwardCgst - $revCgst);
        $netItcSgst = max(0, $inwardSgst - $revSgst);
        $netItcIgst = max(0, $inwardIgst - $revIgst);

        // Tax Payable in Cash (Liability minus Net ITC)
        $cashPayableCgst = max(0, $outwardCgst - $netItcCgst);
        $cashPayableSgst = max(0, $outwardSgst - $netItcSgst);
        $cashPayableIgst = max(0, $outwardIgst - $netItcIgst);
        $totalCashPayable = $cashPayableCgst + $cashPayableSgst + $cashPayableIgst;

        return view('admin.reports.gstr3b', compact(
            'month',
            'outwardTaxable', 'outwardCgst', 'outwardSgst', 'outwardIgst',
            'inwardTaxable', 'inwardCgst', 'inwardSgst', 'inwardIgst',
            'revCgst', 'revSgst', 'revIgst',
            'netItcCgst', 'netItcSgst', 'netItcIgst',
            'cashPayableCgst', 'cashPayableSgst', 'cashPayableIgst', 'totalCashPayable'
        ));
    }

    private function exportGstr1Csv(string $month, array $b2b, array $b2c, array $hsn)
    {
        $filename = "GSTR1_{$month}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($b2b, $b2c, $hsn) {
            $file = fopen('php://output', 'w');
            
            // Section 1: B2B
            fputcsv($file, ['=== TABLE 4A: B2B INVOICES ===']);
            fputcsv($file, ['GSTIN/UIN', 'Receiver Name', 'Invoice Number', 'Invoice Date', 'Place Of Supply', 'Taxable Value', 'CGST', 'SGST', 'IGST', 'Invoice Value']);
            foreach ($b2b as $r) {
                fputcsv($file, [$r['gstin'], $r['buyer_name'], $r['invoice_number'], $r['invoice_date'], $r['place_of_supply'], $r['taxable'], $r['cgst'], $r['sgst'], $r['igst'], $r['total']]);
            }

            fputcsv($file, []);
            // Section 2: HSN Summary
            fputcsv($file, ['=== TABLE 12: HSN SUMMARY ===']);
            fputcsv($file, ['HSN', 'Description', 'UQC', 'Total Quantity', 'Total Taxable Value', 'CGST', 'SGST', 'IGST', 'Total Tax']);
            foreach ($hsn as $h) {
                fputcsv($file, [$h['hsn'], $h['description'], $h['uqc'], $h['total_qty'], $h['total_taxable'], $h['cgst'], $h['sgst'], $h['igst'], $h['total_tax']]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportGstr2Csv(string $month, $bills)
    {
        $filename = "GSTR2_ITC_Register_{$month}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($bills) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['=== GSTR-2 INWARD SUPPLIES (ITC REGISTER) ===']);
            fputcsv($file, ['Vendor Name', 'Vendor GSTIN', 'Bill Number', 'Invoice Date', 'Taxable Value', 'Input CGST', 'Input SGST', 'Input IGST', 'Total Bill Amount', 'Eligibility']);
            foreach ($bills as $b) {
                fputcsv($file, [
                    $b->vendor->name ?? 'N/A',
                    $b->vendor->gstin ?? 'Unregistered',
                    $b->bill_number,
                    $b->invoice_date?->format('d-M-Y'),
                    $b->total_taxable,
                    $b->cgst,
                    $b->sgst,
                    $b->igst,
                    $b->total_amount,
                    'Eligible (Inputs)',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
