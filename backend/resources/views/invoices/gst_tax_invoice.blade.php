<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice - {{ $order->invoice_number ?? ('ORD-' . $order->id) }}</title>
    <style>
        @page {
            margin: 18mm 12mm 18mm 12mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .logo-title {
            font-size: 20px;
            font-weight: bold;
            color: #047857;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge {
            display: inline-block;
            background-color: #047857;
            color: white;
            font-size: 12px;
            font-weight: bold;
            padding: 4px 12px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .box {
            border: 1px solid #cbd5e1;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 10px;
            background-color: #f8fafc;
        }
        .box-title {
            font-weight: bold;
            color: #334155;
            font-size: 11px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .items-table {
            margin-top: 10px;
            margin-bottom: 10px;
            border: 1px solid #cbd5e1;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            padding: 6px 4px;
            border: 1px solid #cbd5e1;
            text-align: center;
        }
        .items-table td {
            padding: 6px 5px;
            border: 1px solid #cbd5e1;
            font-size: 10.5px;
            vertical-align: middle;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .summary-table td {
            padding: 4px 6px;
        }
        .total-row {
            background-color: #f1f5f9;
            font-weight: bold;
            font-size: 12px;
            border-top: 2px solid #047857;
        }
        .hsn-table {
            font-size: 9.5px;
            border: 1px solid #cbd5e1;
            margin-top: 10px;
        }
        .hsn-table th, .hsn-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            text-align: right;
        }
        .hsn-table th {
            background-color: #f8fafc;
            text-align: center;
        }
        .footer-note {
            font-size: 9px;
            color: #64748b;
            margin-top: 15px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
        }
        .signature-box {
            text-align: right;
            margin-top: 25px;
            padding-right: 15px;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table" style="margin-bottom: 12px;">
        <tr>
            <td style="width: 55%;">
                <div class="logo-title">PRAKRUTI AYURVEDIC</div>
                <div style="font-size: 10px; color: #475569; margin-top: 2px;">
                    Authentic Classical Ayurveda & Wellness Products<br>
                    Plot No. 42, GIDC Phase II, Vatva, Ahmedabad, Gujarat - 382445<br>
                    <strong>GSTIN:</strong> 24AAACP9876K1Z9 &nbsp;|&nbsp; <strong>State:</strong> Gujarat (Code: 24)<br>
                    <strong>Email:</strong> support@prakrutiaurvedic.com &nbsp;|&nbsp; <strong>Phone:</strong> +91 79 2583 0000
                </div>
            </td>
            <td style="width: 45%; text-align: right;">
                <div class="badge">TAX INVOICE</div>
                <div style="margin-top: 6px; font-size: 11px;">
                    <strong>Invoice No:</strong> {{ $order->invoice_number ?? ('INV-' . str_pad($order->id, 5, '0', STR_PAD_LEFT)) }}<br>
                    <strong>Invoice Date:</strong> {{ $order->created_at ? $order->created_at->format('d-M-Y') : date('d-M-Y') }}<br>
                    <strong>Order ID:</strong> #{{ $order->id }}<br>
                    <strong>Place of Supply:</strong> {{ $placeOfSupply ?? ($order->shipping_state ?? 'Gujarat (24)') }}<br>
                    <strong>Reverse Charge:</strong> No
                </div>
            </td>
        </tr>
    </table>

    <!-- Buyer & Shipping Info -->
    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="width: 49%; vertical-align: top;">
                <div class="box" style="min-height: 110px;">
                    <div class="box-title">Details of Receiver (Billed To)</div>
                    @if($order->is_business_order && $order->customer_gstin)
                        <strong style="color: #047857;">{{ $order->business_name ?: $order->shipping_name }}</strong><br>
                        <strong>GSTIN:</strong> <span style="font-family: monospace; font-size: 11px; font-weight: bold;">{{ $order->customer_gstin }}</span><br>
                    @else
                        <strong>{{ $order->shipping_name }}</strong><br>
                        <span style="color: #64748b;">(Consumer / B2C Outward Supply)</span><br>
                    @endif
                    <span>{{ $order->shipping_address }}</span><br>
                    <span>{{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_pincode }}</span><br>
                    <span><strong>Phone:</strong> {{ $order->shipping_phone }} &nbsp;|&nbsp; <strong>Email:</strong> {{ $order->shipping_email }}</span>
                </div>
            </td>
            <td style="width: 2%;"></td>
            <td style="width: 49%; vertical-align: top;">
                <div class="box" style="min-height: 110px;">
                    <div class="box-title">Shipping Address (Shipped To)</div>
                    <strong>{{ $order->shipping_name }}</strong><br>
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
                <th style="width: 30%;">Product Description</th>
                <th style="width: 10%;">HSN/SAC</th>
                <th style="width: 12%;">Batch No</th>
                <th style="width: 6%;">Qty</th>
                <th style="width: 10%;">Rate (₹)</th>
                <th style="width: 13%;">Taxable (₹)</th>
                @if($isInterstate)
                    <th style="width: 15%;">IGST (₹)</th>
                @else
                    <th style="width: 7.5%;">CGST (₹)</th>
                    <th style="width: 7.5%;">SGST (₹)</th>
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
                    <td class="text-center">{{ $item['hsn_code'] }}</td>
                    <td class="text-center" style="font-family: monospace;">{{ $item['batch_number'] ?? 'DEFAULT' }}</td>
                    <td class="text-center">{{ $item['qty'] }}</td>
                    <td class="text-right">{{ number_format($item['unit_taxable'], 2) }}</td>
                    <td class="text-right">{{ number_format($item['taxable'], 2) }}</td>
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
                    <td class="text-right font-bold">{{ number_format($item['total'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Summary Section -->
    <table style="width: 100%;">
        <tr>
            <!-- Left: HSN Summary & Amount in Words -->
            <td style="width: 58%; vertical-align: top;">
                <div style="font-size: 10px; font-weight: bold; margin-bottom: 4px;">HSN/SAC Tax Breakup:</div>
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

                <div style="margin-top: 15px; font-size: 10.5px; background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 6px;">
                    <strong>Amount in Words (INR):</strong><br>
                    <span style="font-style: italic; color: #047857; font-weight: bold;">
                        {{ $amountInWords ?? 'Rupees Only' }}
                    </span>
                </div>
            </td>

            <!-- Right: Calculations & Totals -->
            <td style="width: 42%; vertical-align: top;">
                <table class="summary-table" style="width: 100%; border: 1px solid #cbd5e1;">
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
                            <td>Delivery / Shipping Charges:</td>
                            <td class="text-right font-bold">₹{{ number_format($order->shipping_charge, 2) }}</td>
                        </tr>
                    @endif
                    @if($order->discount_amount > 0)
                        <tr>
                            <td>Discount Applied:</td>
                            <td class="text-right" style="color: #dc2626;">-₹{{ number_format($order->discount_amount, 2) }}</td>
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
                        <td style="padding: 8px 6px;">Grand Total (Inclusive):</td>
                        <td class="text-right" style="padding: 8px 6px; color: #047857; font-size: 14px;">
                            ₹{{ number_format($roundedGrandTotal, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Signature and Declarations -->
    <table style="width: 100%; margin-top: 15px;">
        <tr>
            <td style="width: 60%; vertical-align: top; font-size: 9.5px; color: #475569;">
                <strong>Terms & Conditions:</strong><br>
                1. Goods once sold will only be replaced in case of manufacturing defect or transit damage.<br>
                2. Input Tax Credit (ITC) eligibility is subject to the provisions of Section 16 of the CGST Act, 2017.<br>
                3. All disputes are subject to Ahmedabad (Gujarat) Jurisdiction only.<br>
                4. This is a computer-generated tax invoice issued in accordance with GST Rule 46.
            </td>
            <td style="width: 40%; text-align: right; vertical-align: bottom;">
                <div style="font-size: 10.5px; font-weight: bold; margin-bottom: 30px;">
                    For PRAKRUTI AYURVEDIC
                </div>
                <div style="border-top: 1px solid #94a3b8; display: inline-block; padding-top: 4px; font-size: 10px;">
                    Authorised Signatory
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
