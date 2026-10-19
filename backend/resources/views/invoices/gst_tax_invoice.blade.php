<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice - {{ $order->invoice_number ?? ('INV-' . str_pad($order->id, 5, '0', STR_PAD_LEFT)) }}</title>
    <style>
        @page {
            margin: 12mm 10mm 14mm 10mm;
            size: A4 portrait;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10.5px;
            color: #1e293b;
            line-height: 1.35;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        .no-print {
            background: #184e28;
            padding: 10px 18px;
            text-align: right;
            border-bottom: 2px solid #d9ad28;
        }
        .btn-print {
            background: #ffffff;
            color: #184e28;
            border: none;
            padding: 7px 16px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .btn-print:hover {
            background: #f1f5f9;
        }
        .invoice-container {
            padding: 16px 20px;
            max-width: 900px;
            margin: 0 auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-logo {
            max-height: 62px;
            max-width: 140px;
            object-fit: contain;
            display: inline-block;
        }
        .logo-title {
            font-size: 21px;
            font-weight: 800;
            color: #184e28;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.1;
        }
        .company-tagline {
            font-size: 10px;
            color: #b45309;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-top: 2px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .company-meta {
            font-size: 9.5px;
            color: #475569;
            line-height: 1.35;
        }
        .badge-invoice {
            display: inline-block;
            background: #184e28;
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
            padding: 5px 14px;
            border-radius: 4px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .invoice-meta-table {
            margin-top: 6px;
            font-size: 10px;
            color: #1e293b;
        }
        .invoice-meta-table td {
            padding: 1.5px 0;
        }
        .box {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            border-radius: 4px;
            background-color: #f8fafc;
            min-height: 95px;
        }
        .box-title {
            font-weight: 800;
            color: #184e28;
            font-size: 10px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .items-table {
            margin-top: 10px;
            margin-bottom: 10px;
            border: 1px solid #cbd5e1;
        }
        .items-table th {
            background-color: #184e28;
            color: #ffffff;
            font-weight: 700;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 6px 4px;
            border: 1px solid #133e20;
            text-align: center;
        }
        .items-table td {
            padding: 5px 5px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
            vertical-align: middle;
        }
        .items-table tbody tr:nth-child(even) {
            background-color: #fafbfb;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 700; }
        .summary-table td {
            padding: 3.5px 6px;
            font-size: 10px;
            border: 1px solid #e2e8f0;
        }
        .total-row td {
            background-color: #eef7ee;
            font-weight: 800;
            font-size: 11.5px;
            border-top: 2px solid #184e28 !important;
            border-bottom: 2px solid #184e28 !important;
            color: #184e28;
        }
        .hsn-table {
            font-size: 9px;
            border: 1px solid #cbd5e1;
            margin-top: 6px;
        }
        .hsn-table th, .hsn-table td {
            border: 1px solid #cbd5e1;
            padding: 3.5px 4px;
            text-align: right;
        }
        .hsn-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-align: center;
        }
        .amount-words-card {
            margin-top: 10px;
            font-size: 10px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-left: 4px solid #184e28;
            padding: 6px 8px;
        }
        .footer-terms {
            font-size: 8.5px;
            color: #64748b;
            line-height: 1.4;
        }
        .sign-title {
            font-size: 10.5px;
            font-weight: 800;
            color: #184e28;
            margin-bottom: 34px;
        }
        .sign-line {
            border-top: 1px solid #94a3b8;
            display: inline-block;
            padding-top: 3px;
            font-size: 9.5px;
            font-weight: 700;
            color: #334155;
        }
        @media print {
            .no-print { display: none !important; }
            .invoice-container { padding: 0; max-width: 100%; }
            body { font-size: 10px; }
        }
    </style>
</head>
<body>

    @if(!isset($isPdfDownload) || !$isPdfDownload)
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">
            🖨️ Print / Download Invoice
        </button>
    </div>
    @endif

    <div class="invoice-container">
        <!-- Top Accent Bar -->
        <div style="height: 4px; background: linear-gradient(90deg, #184e28 0%, #d9ad28 100%); margin-bottom: 12px; border-radius: 2px;"></div>

        <!-- Header Section -->
        <table class="header-table" style="margin-bottom: 10px;">
            <tr>
                <td style="width: 58%;">
                    <table style="width: 100%;">
                        <tr>
                            @if(!empty($logoBase64))
                            <td style="width: 80px; vertical-align: top; padding-right: 12px;">
                                <img src="{{ $logoBase64 }}" class="company-logo" alt="Prakruti Corporation Logo">
                            </td>
                            @endif
                            <td style="vertical-align: top;">
                                <h1 class="logo-title">PRAKRUTI CORPORATION</h1>
                                <div class="company-tagline">Pure Organic Ecosystem</div>
                                <div class="company-meta">
                                    Plot No. 42, GIDC Phase II, Vatva, Ahmedabad, Gujarat - 382445<br>
                                    <strong>GSTIN:</strong> 24AAACP9876K1Z9 &nbsp;|&nbsp; <strong>State Code:</strong> 24 (Gujarat)<br>
                                    <strong>Email:</strong> care@prakruticorporation.com &nbsp;|&nbsp; <strong>Phone:</strong> +91 79 2583 0000<br>
                                    <strong>Website:</strong> www.prakruticorporation.com
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="width: 42%; text-align: right; vertical-align: top;">
                    <div class="badge-invoice">TAX INVOICE</div>
                    <div style="font-size: 8.5px; color: #64748b; margin-top: 2px; font-weight: 600;">(Original for Recipient / GST Rule 46)</div>
                    <table class="invoice-meta-table" style="margin-left: auto; margin-top: 6px;">
                        <tr>
                            <td class="text-right" style="color: #64748b; padding-right: 6px;"><strong>Invoice No:</strong></td>
                            <td class="text-right font-bold" style="color: #184e28;">{{ $order->invoice_number ?? ('INV-' . str_pad($order->id, 5, '0', STR_PAD_LEFT)) }}</td>
                        </tr>
                        <tr>
                            <td class="text-right" style="color: #64748b; padding-right: 6px;"><strong>Invoice Date:</strong></td>
                            <td class="text-right font-bold">{{ $order->created_at ? $order->created_at->format('d-M-Y') : date('d-M-Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-right" style="color: #64748b; padding-right: 6px;"><strong>Order ID:</strong></td>
                            <td class="text-right font-bold">#{{ $order->id }}</td>
                        </tr>
                        <tr>
                            <td class="text-right" style="color: #64748b; padding-right: 6px;"><strong>Place of Supply:</strong></td>
                            <td class="text-right font-bold">{{ $placeOfSupply ?? ($order->shipping_state ?? 'Gujarat (24)') }}</td>
                        </tr>
                        <tr>
                            <td class="text-right" style="color: #64748b; padding-right: 6px;"><strong>Reverse Charge:</strong></td>
                            <td class="text-right font-bold">No</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Buyer & Shipping Info -->
        <table style="width: 100%; margin-bottom: 8px;">
            <tr>
                <td style="width: 49%; vertical-align: top;">
                    <div class="box">
                        <div class="box-title">Details of Receiver (Billed To)</div>
                        @if($order->is_business_order && $order->customer_gstin)
                            <strong style="color: #184e28; font-size: 11px;">{{ $order->business_name ?: $order->shipping_name }}</strong><br>
                            <strong>GSTIN:</strong> <span style="font-family: monospace; font-size: 11px; font-weight: bold; color: #047857;">{{ $order->customer_gstin }}</span><br>
                        @else
                            <strong style="font-size: 11px;">{{ $order->shipping_name }}</strong><br>
                            <span style="color: #64748b; font-size: 9px;">(Consumer / B2C Outward Supply)</span><br>
                        @endif
                        <span>{{ $order->shipping_address }}</span><br>
                        <span>{{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_pincode }}</span><br>
                        <span><strong>Phone:</strong> {{ $order->shipping_phone }} &nbsp;|&nbsp; <strong>Email:</strong> {{ $order->shipping_email }}</span>
                    </div>
                </td>
                <td style="width: 2%;"></td>
                <td style="width: 49%; vertical-align: top;">
                    <div class="box">
                        <div class="box-title">Shipping Address (Shipped To)</div>
                        <strong style="font-size: 11px;">{{ $order->shipping_name }}</strong><br>
                        <span>{{ $order->shipping_address }}</span><br>
                        <span>{{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_pincode }}</span><br>
                        <span><strong>State Code:</strong> {{ $stateCode ?? '24' }}</span><br>
                        <span><strong>Payment Method:</strong> {{ strtoupper($order->payment_method ?? 'PREPAID') }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Line Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 32%;">Product Description</th>
                    <th style="width: 10%;">HSN/SAC</th>
                    <th style="width: 11%;">Batch No</th>
                    <th style="width: 5%;">Qty</th>
                    <th style="width: 10%;">Rate (₹)</th>
                    <th style="width: 12%;">Taxable (₹)</th>
                    @if($isInterstate)
                        <th style="width: 13%;">IGST (₹)</th>
                    @else
                        <th style="width: 6.5%;">CGST (₹)</th>
                        <th style="width: 6.5%;">SGST (₹)</th>
                    @endif
                    <th style="width: 13%;">Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totTaxable = 0;
                    $totCgst = 0;
                    $totSgst = 0;
                    $totIgst = 0;
                    $grandTotal = 0;
                @endphp
                @foreach($items as $idx => $item)
                    @php
                        $totTaxable += $item['taxable'];
                        $totCgst += $item['cgst'];
                        $totSgst += $item['sgst'];
                        $totIgst += $item['igst'];
                        $grandTotal += $item['total'];
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $item['product_name'] }}</strong>
                            @if(!empty($item['variation_name']))
                                <br><small style="color: #64748b;">Variant: {{ $item['variation_name'] }}</small>
                            @endif
                        </td>
                        <td class="text-center" style="font-family: monospace;">{{ $item['hsn_code'] }}</td>
                        <td class="text-center" style="font-family: monospace;">{{ $item['batch_number'] ?? 'DEFAULT' }}</td>
                        <td class="text-center font-bold">{{ $item['qty'] }}</td>
                        <td class="text-right">{{ number_format($item['unit_taxable'], 2) }}</td>
                        <td class="text-right font-bold">{{ number_format($item['taxable'], 2) }}</td>
                        @if($isInterstate)
                            <td class="text-right">
                                {{ number_format($item['igst'], 2) }}<br>
                                <small style="color: #64748b;">({{ $item['gst_rate'] }}%)</small>
                            </td>
                        @else
                            <td class="text-right">
                                {{ number_format($item['cgst'], 2) }}<br>
                                <small style="color: #64748b;">({{ $item['gst_rate'] / 2 }}%)</small>
                            </td>
                            <td class="text-right">
                                {{ number_format($item['sgst'], 2) }}<br>
                                <small style="color: #64748b;">({{ $item['gst_rate'] / 2 }}%)</small>
                            </td>
                        @endif
                        <td class="text-right font-bold" style="color: #184e28;">{{ number_format($item['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary Section -->
        <table style="width: 100%;">
            <tr>
                <!-- Left: HSN Summary & Amount in Words -->
                <td style="width: 56%; vertical-align: top; padding-right: 12px;">
                    <div style="font-size: 9.5px; font-weight: 700; color: #184e28; margin-bottom: 2px;">HSN / SAC Tax Breakup:</div>
                    <table class="hsn-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>HSN/SAC</th>
                                <th>Taxable (₹)</th>
                                @if($isInterstate)
                                    <th>IGST (₹)</th>
                                @else
                                    <th>CGST (₹)</th>
                                    <th>SGST (₹)</th>
                                @endif
                                <th>Total Tax (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($hsnSummary as $hsn => $summary)
                                <tr>
                                    <td class="text-center font-bold">{{ $hsn }}</td>
                                    <td>{{ number_format($summary['taxable'], 2) }}</td>
                                    @if($isInterstate)
                                        <td>{{ number_format($summary['igst'], 2) }}</td>
                                    @else
                                        <td>{{ number_format($summary['cgst'], 2) }}</td>
                                        <td>{{ number_format($summary['sgst'], 2) }}</td>
                                    @endif
                                    <td class="font-bold">{{ number_format($summary['total_tax'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="amount-words-card">
                        <strong style="color: #475569;">Amount in Words:</strong><br>
                        <span style="color: #184e28; font-weight: 800; font-size: 10.5px;">
                            {{ $amountInWords ?? 'Rupees Only' }}
                        </span>
                    </div>
                </td>

                <!-- Right: Calculations & Totals -->
                <td style="width: 44%; vertical-align: top;">
                    <table class="summary-table" style="width: 100%;">
                        <tr>
                            <td>Total Taxable Value:</td>
                            <td class="text-right font-bold">₹{{ number_format($totTaxable, 2) }}</td>
                        </tr>
                        @if($isInterstate)
                            <tr>
                                <td>Total IGST:</td>
                                <td class="text-right font-bold">₹{{ number_format($totIgst, 2) }}</td>
                            </tr>
                        @else
                            <tr>
                                <td>Total CGST:</td>
                                <td class="text-right font-bold">₹{{ number_format($totCgst, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Total SGST:</td>
                                <td class="text-right font-bold">₹{{ number_format($totSgst, 2) }}</td>
                            </tr>
                        @endif
                        @if($order->shipping_charge > 0)
                            <tr>
                                <td>Delivery & Shipping Charges:</td>
                                <td class="text-right font-bold">₹{{ number_format($order->shipping_charge, 2) }}</td>
                            </tr>
                        @endif
                        @if($order->discount_amount > 0)
                            <tr>
                                <td>Discount Applied:</td>
                                <td class="text-right" style="color: #dc2626; font-weight: bold;">-₹{{ number_format($order->discount_amount, 2) }}</td>
                            </tr>
                        @endif
                        @php
                            $roundedGrandTotal = round($order->total_amount);
                            $roundOff = round($roundedGrandTotal - $order->total_amount, 2);
                        @endphp
                        @if($roundOff != 0)
                            <tr>
                                <td>Round Off:</td>
                                <td class="text-right">{{ $roundOff > 0 ? '+' : '' }}₹{{ number_format($roundOff, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="total-row">
                            <td style="padding: 7px 6px;">Grand Total (INR):</td>
                            <td class="text-right" style="padding: 7px 6px; font-size: 13.5px;">
                                ₹{{ number_format($roundedGrandTotal, 2) }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Signature and Declarations -->
        <table style="width: 100%; margin-top: 14px; border-top: 1px solid #cbd5e1; padding-top: 8px;">
            <tr>
                <td style="width: 62%; vertical-align: top;" class="footer-terms">
                    <strong style="color: #1e293b;">Terms & Conditions:</strong><br>
                    1. Goods once sold will be replaced only in case of verified transit damage or manufacturing defect.<br>
                    2. Input Tax Credit (ITC) eligibility is subject to the provisions of Section 16 of the CGST Act, 2017.<br>
                    3. All disputes are subject to Ahmedabad (Gujarat) Jurisdiction only.<br>
                    4. This is an authenticated computer-generated GST tax invoice.
                </td>
                <td style="width: 38%; text-align: right; vertical-align: bottom;">
                    <div class="sign-title">
                        For PRAKRUTI CORPORATION
                    </div>
                    <div class="sign-line">
                        Authorised Signatory
                    </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
