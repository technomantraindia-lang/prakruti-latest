<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Prepare full dataset for GST Rule 46 Tax Invoice.
     */
    public function prepareInvoiceData(Order $order): array
    {
        $order->loadMissing(['items.product', 'items.variation']);

        // Check if Inter-State or Intra-State
        // Gujarat state code is 24
        $isInterstate = false;
        $customerGstin = $order->customer_gstin ? trim(strtoupper($order->customer_gstin)) : null;
        $stateCode = '24';

        if ($customerGstin && strlen($customerGstin) >= 2) {
            $stateCode = substr($customerGstin, 0, 2);
            if ($stateCode !== '24') {
                $isInterstate = true;
            }
        } elseif ($order->shipping_state && !preg_match('/gujarat/i', $order->shipping_state)) {
            $isInterstate = true;
            $stateCode = $this->guessStateCode($order->shipping_state);
        }

        $itemsData = [];
        $hsnSummary = [];

        foreach ($order->items as $item) {
            $product = $item->product;
            $hsn = $item->hsn_code ?: ($product->hsn_code ?? '30049011');
            $gstRate = (float) ($product->gst_percentage ?? 12);
            $qty = (int) $item->quantity;
            $totalInclusive = (float) $item->total;

            // Display price is inclusive of GST. Reverse tax extraction:
            // Taxable = (Total Inclusive * 100) / (100 + Rate)
            $taxable = round(($totalInclusive * 100) / (100 + $gstRate), 2);
            $totalTax = round($totalInclusive - $taxable, 2);
            $unitTaxable = $qty > 0 ? round($taxable / $qty, 2) : 0;

            $cgst = 0;
            $sgst = 0;
            $igst = 0;

            if ($isInterstate) {
                $igst = $totalTax;
            } else {
                $cgst = round($totalTax / 2, 2);
                $sgst = round($totalTax - $cgst, 2);
            }

            $itemsData[] = [
                'product_name' => $product ? $product->name : 'Ayurvedic Product',
                'variation_name' => $item->variation ? $item->variation->name : null,
                'hsn_code' => $hsn,
                'batch_number' => $item->batch_number,
                'qty' => $qty,
                'unit_taxable' => $unitTaxable,
                'taxable' => $taxable,
                'gst_rate' => $gstRate,
                'cgst' => $cgst,
                'sgst' => $sgst,
                'igst' => $igst,
                'total' => $totalInclusive,
            ];

            // Accumulate in HSN summary
            if (!isset($hsnSummary[$hsn])) {
                $hsnSummary[$hsn] = [
                    'taxable' => 0,
                    'cgst' => 0,
                    'sgst' => 0,
                    'igst' => 0,
                    'total_tax' => 0,
                ];
            }
            $hsnSummary[$hsn]['taxable'] += $taxable;
            $hsnSummary[$hsn]['cgst'] += $cgst;
            $hsnSummary[$hsn]['sgst'] += $sgst;
            $hsnSummary[$hsn]['igst'] += $igst;
            $hsnSummary[$hsn]['total_tax'] += $totalTax;
        }

        $grandTotal = round((float) $order->total_amount);
        $amountInWords = $this->numberToWordsIndian($grandTotal) . ' Rupees Only';

        return [
            'order' => $order,
            'items' => $itemsData,
            'hsnSummary' => $hsnSummary,
            'isInterstate' => $isInterstate,
            'stateCode' => $stateCode,
            'placeOfSupply' => ($order->shipping_state ?: 'Gujarat') . " ({$stateCode})",
            'amountInWords' => $amountInWords,
        ];
    }

    /**
     * Render DomPDF and store PDF to disk.
     */
    public function generatePdf(Order $order): string
    {
        $data = $this->prepareInvoiceData($order);

        $pdf = Pdf::loadView('invoices.gst_tax_invoice', $data);
        $pdf->setPaper('A4', 'portrait');

        $invoiceNum = $order->invoice_number ?: ('INV-' . str_pad($order->id, 5, '0', STR_PAD_LEFT));
        $cleanName = preg_replace('/[^A-Za-z0-9_-]/', '_', $invoiceNum);
        $filename = "invoices/{$cleanName}.pdf";

        Storage::disk('public')->put($filename, $pdf->output());

        $order->update([
            'invoice_number' => $order->invoice_number ?: $invoiceNum,
            'invoice_pdf_path' => $filename,
        ]);

        return $filename;
    }

    /**
     * Download or stream PDF instance.
     */
    public function downloadPdf(Order $order)
    {
        $data = $this->prepareInvoiceData($order);
        $pdf = Pdf::loadView('invoices.gst_tax_invoice', $data);
        $pdf->setPaper('A4', 'portrait');

        $invoiceNum = $order->invoice_number ?: ('INV-' . str_pad($order->id, 5, '0', STR_PAD_LEFT));
        return $pdf->download("Tax-Invoice-{$invoiceNum}.pdf");
    }

    /**
     * Guess GST state code from Indian state name.
     */
    private function guessStateCode(?string $stateName): string
    {
        if (!$stateName) {
            return '24';
        }

        $codes = [
            'jammu' => '01', 'himachal' => '02', 'punjab' => '03', 'chandigarh' => '04',
            'uttarakhand' => '05', 'haryana' => '06', 'delhi' => '07', 'rajasthan' => '08',
            'uttar pradesh' => '09', 'bihar' => '10', 'sikkim' => '11', 'arunachal' => '12',
            'nagaland' => '13', 'manipur' => '14', 'mizoram' => '15', 'tripura' => '16',
            'meghalaya' => '17', 'assam' => '18', 'west bengal' => '19', 'jharkhand' => '20',
            'odisha' => '21', 'chhattisgarh' => '22', 'madhya pradesh' => '23', 'gujarat' => '24',
            'daman' => '26', 'maharashtra' => '27', 'andhra' => '28', 'karnataka' => '29',
            'goa' => '30', 'lakshadweep' => '31', 'kerala' => '32', 'tamil nadu' => '33',
            'puducherry' => '34', 'telangana' => '36',
        ];

        $lower = strtolower($stateName);
        foreach ($codes as $key => $code) {
            if (strpos($lower, $key) !== false) {
                return $code;
            }
        }

        return '24';
    }

    /**
     * Convert positive integer into Indian Numbering Format words (Lakh, Crore, Thousand).
     */
    private function numberToWordsIndian(float $number): string
    {
        $no = (int) floor($number);
        $words = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
            30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
            80 => 'Eighty', 90 => 'Ninety',
        ];

        if ($no === 0) {
            return 'Zero';
        }

        $crore = (int) ($no / 10000000);
        $no %= 10000000;
        $lakh = (int) ($no / 100000);
        $no %= 100000;
        $thousand = (int) ($no / 1000);
        $no %= 1000;
        $hundred = (int) ($no / 100);
        $tens = (int) ($no % 100);

        $res = [];
        if ($crore > 0) {
            $res[] = $this->numberToWordsIndian($crore) . ' Crore';
        }
        if ($lakh > 0) {
            $res[] = $this->numberToWordsIndian($lakh) . ' Lakh';
        }
        if ($thousand > 0) {
            $res[] = $this->numberToWordsIndian($thousand) . ' Thousand';
        }
        if ($hundred > 0) {
            $res[] = $words[$hundred] . ' Hundred';
        }
        if ($tens > 0) {
            if ($tens < 20) {
                $res[] = $words[$tens];
            } else {
                $t = ((int) ($tens / 10)) * 10;
                $u = $tens % 10;
                $res[] = $words[$t] . ($u > 0 ? ' ' . $words[$u] : '');
            }
        }

        return trim(implode(' ', $res));
    }
}
