<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GRN {{ $grn->grn_number }} - Goods Receipt Note - Prakruti Corporation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 12mm 12mm;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 11px;
            color: #0f172a;
            line-height: 1.4;
            background: #f1f5f9;
            margin: 0;
            padding: 0;
        }
        .no-print-bar {
            background: #184e28;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #d9ad28;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }
        .no-print-bar .brand-text {
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .no-print-bar .actions {
            display: flex;
            gap: 10px;
        }
        .btn-ui {
            border: none;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .btn-ui-back {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.35);
        }
        .btn-ui-back:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }
        .btn-ui-print {
            background: #d9ad28;
            color: #193a15;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        .btn-ui-print:hover {
            background: #eab308;
        }
        .grn-paper-wrap {
            max-width: 900px;
            margin: 24px auto;
            background: #ffffff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .grn-container {
            padding: 30px 36px 28px;
        }
        .gold-green-bar {
            height: 5px;
            background: linear-gradient(90deg, #184e28 0%, #2d5a27 60%, #d9ad28 100%);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-logo {
            max-height: 58px;
            max-width: 130px;
            object-fit: contain;
        }
        .company-title {
            font-size: 20px;
            font-weight: 800;
            color: #184e28;
            letter-spacing: 0.5px;
            margin: 0;
            line-height: 1.1;
        }
        .company-tagline {
            font-size: 9.5px;
            color: #b45309;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin: 3px 0 4px;
        }
        .company-meta {
            font-size: 9px;
            color: #475569;
            line-height: 1.35;
        }
        .doc-badge-box {
            border: 2px solid #184e28;
            border-radius: 6px;
            overflow: hidden;
            text-align: right;
            background: #ffffff;
        }
        .doc-badge-header {
            background: #184e28;
            color: #ffffff;
            font-size: 11.5px;
            font-weight: 800;
            padding: 5px 12px;
            letter-spacing: 0.8px;
            text-align: center;
            text-transform: uppercase;
        }
        .doc-badge-body {
            padding: 6px 12px;
            font-size: 9.5px;
            background: #f8fafc;
        }
        .doc-badge-body .grn-num {
            font-family: 'JetBrains Mono', monospace;
            font-size: 14px;
            font-weight: 800;
            color: #184e28;
            letter-spacing: 0.5px;
        }
        .info-cards-table {
            margin-top: 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
        }
        .info-cards-table td {
            vertical-align: top;
            padding: 10px 14px;
            font-size: 9.5px;
        }
        .info-col-title {
            font-size: 9.5px;
            font-weight: 800;
            color: #184e28;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding-bottom: 4px;
            margin-bottom: 6px;
            border-bottom: 1.5px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .info-label {
            color: #64748b;
            font-size: 9px;
            margin-right: 4px;
        }
        .info-val {
            color: #0f172a;
            font-weight: 600;
        }
        .items-table {
            margin-top: 14px;
            width: 100%;
            border: 1px solid #cbd5e1;
            border-collapse: collapse;
        }
        .items-table th {
            background: #184e28;
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 7px 8px;
            border: 1px solid #143d20;
            text-align: left;
        }
        .items-table th.center { text-align: center; }
        .items-table th.right { text-align: right; }
        .items-table td {
            padding: 7px 8px;
            border: 1px solid #e2e8f0;
            font-size: 9.5px;
            vertical-align: top;
        }
        .items-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .items-table .batch-tag {
            font-family: 'JetBrains Mono', monospace;
            font-size: 9px;
            font-weight: 700;
            color: #0f172a;
            background: #e2e8f0;
            padding: 2px 5px;
            border-radius: 3px;
            display: inline-block;
        }
        .items-table .qty-tag {
            font-weight: 700;
            color: #15803d;
            font-size: 10px;
        }
        .summary-block-table {
            margin-top: 12px;
            border-collapse: collapse;
        }
        .summary-block-table td {
            vertical-align: top;
        }
        .declaration-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 12px;
            background: #f8fafc;
            font-size: 8.5px;
            color: #475569;
            line-height: 1.4;
        }
        .declaration-title {
            font-weight: 700;
            color: #184e28;
            text-transform: uppercase;
            font-size: 9px;
            margin-bottom: 4px;
        }
        .totals-table {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
            background: #ffffff;
        }
        .totals-table td {
            padding: 5px 10px;
            font-size: 9.5px;
            border-bottom: 1px solid #f1f5f9;
        }
        .totals-table td:last-child {
            text-align: right;
            font-weight: 600;
        }
        .totals-table tr.grand-row td {
            background: #184e28;
            color: #ffffff;
            font-weight: 800;
            font-size: 11px;
            border-bottom: none;
            padding: 7px 10px;
        }
        .signatures-table {
            margin-top: 24px;
            border-top: 1px solid #cbd5e1;
            padding-top: 16px;
        }
        .sign-cell {
            text-align: center;
            vertical-align: bottom;
            padding: 0 10px;
            font-size: 9px;
        }
        .sign-line {
            border-top: 1.5px solid #0f172a;
            margin-top: 42px;
            padding-top: 4px;
            font-weight: 700;
            color: #0f172a;
        }
        .sign-role {
            font-size: 8.5px;
            color: #64748b;
        }
        .footer-note {
            margin-top: 16px;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px dashed #e2e8f0;
            padding-top: 6px;
        }
        @media print {
            body {
                background: #ffffff !important;
                font-size: 9.5px;
            }
            .no-print-bar {
                display: none !important;
            }
            .grn-paper-wrap {
                margin: 0 !important;
                max-width: 100% !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
            }
            .grn-container {
                padding: 0 !important;
            }
            .items-table tr {
                page-break-inside: avoid;
            }
            .signatures-table {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
@php
    $isPaid = ($grn->payment_status === 'paid' || $grn->purchaseBill?->payment_status === 'paid');
@endphp
<body>

    <!-- Sticky Screen Action Bar -->
    <div class="no-print-bar">
        <div class="brand-text">
            <i class="fas fa-file-invoice text-warning"></i>
            <span>Goods Receipt Note (GRN) Document Viewer</span>
            <span style="opacity: 0.6; font-weight: 400; font-size: 12px;">| {{ $grn->grn_number }}</span>
            @if($isPaid)
                <span style="background: #15803d; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 6px;">PAID</span>
            @else
                <span style="background: #dc2626; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 6px;">NOT PAID</span>
            @endif
        </div>
        <div class="actions">
            <a href="{{ route('admin.inward.show', $grn) }}" class="btn-ui btn-ui-back">
                <i class="fas fa-arrow-left"></i> Back to Inward View
            </a>
            <button type="button" class="btn-ui btn-ui-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- Official Paper Sheet -->
    <div class="grn-paper-wrap">
        <div class="gold-green-bar"></div>
        <div class="grn-container">

            <!-- Company Header & Doc Badge -->
            <table class="header-table" style="margin-bottom: 8px;">
                <tr>
                    <td style="width: 58%;">
                        <table style="width: 100%;">
                            <tr>
                                <td style="width: 72px; padding-right: 12px; vertical-align: top;">
                                    <img src="{{ asset('assets/prakruti-logo.png') }}" alt="Prakruti Organic logo" class="company-logo">
                                </td>
                                <td style="vertical-align: top;">
                                    <h1 class="company-title">PRAKRUTI CORPORATION</h1>
                                    <div class="company-tagline">Crafted Traditionally, Tested Scientifically</div>
                                    <div class="company-meta">
                                        Plot No. 42, GIDC Phase II, Vatva, Ahmedabad, Gujarat - 382445<br>
                                        <strong>GSTIN:</strong> 24AAACP9876K1Z9 &nbsp;|&nbsp; <strong>State Code:</strong> 24 (Gujarat)<br>
                                        <strong>Email:</strong> care@prakruticorporation.com &nbsp;|&nbsp; <strong>Phone:</strong> +91 79 2583 0000<br>
                                        <strong>Web:</strong> www.prakruticorporation.com
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 42%; vertical-align: top;">
                        <div class="doc-badge-box">
                            <div class="doc-badge-header">GOODS RECEIPT NOTE (GRN)</div>
                            <div class="doc-badge-body">
                                <div><span class="info-label">GRN No:</span> <span class="grn-num">{{ $grn->grn_number }}</span></div>
                                <div style="margin-top: 3px;"><span class="info-label">Inward Date:</span> <strong>{{ $grn->received_date->format('d M, Y') }}</strong></div>
                                <div style="margin-top: 2px;"><span class="info-label">Inward:</span> <strong style="color: #15803d;"><i class="fas fa-check-circle"></i> Verified &amp; Inwarded</strong></div>
                                <div style="margin-top: 2px;">
                                    <span class="info-label">Payment:</span>
                                    @if($isPaid)
                                        <strong style="color: #15803d;"><i class="fas fa-check-circle"></i> PAID</strong>
                                    @else
                                        <strong style="color: #dc2626;"><i class="fas fa-times-circle"></i> NOT PAID</strong>
                                    @endif
                                </div>
                                <div style="margin-top: 2px;"><span class="info-label">Location:</span> Central Warehouse (Bay A)</div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Supplier & Invoice Details Box -->
            <table class="info-cards-table">
                <tr>
                    <td style="width: 52%; border-right: 1px solid #cbd5e1;">
                        <div class="info-col-title">
                            <i class="fas fa-truck"></i> SUPPLIER / VENDOR DETAILS
                        </div>
                        <div style="font-size: 11px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">
                            {{ $grn->vendor?->display_name }}
                        </div>
                        @if($grn->vendor?->gstin)
                            <div><span class="info-label">GSTIN:</span> <strong style="font-family: 'JetBrains Mono', monospace;">{{ $grn->vendor->gstin }}</strong></div>
                        @endif
                        @if($grn->vendor?->phone || $grn->vendor?->email)
                            <div>
                                @if($grn->vendor?->phone)<span class="info-label">Phone:</span> {{ $grn->vendor->phone }} @endif
                                @if($grn->vendor?->email)&nbsp;|&nbsp; <span class="info-label">Email:</span> {{ $grn->vendor->email }} @endif
                            </div>
                        @endif
                        <div style="margin-top: 2px; color: #475569;">
                            <span class="info-label">Address:</span>
                            {{ $grn->vendor?->address ?: 'N/A' }}, {{ $grn->vendor?->city }} {{ $grn->vendor?->state }} - {{ $grn->vendor?->pincode }}
                        </div>
                    </td>
                    <td style="width: 48%;">
                        <div class="info-col-title">
                            <i class="fas fa-receipt"></i> INVOICE &amp; INWARD REFERENCES
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                            <span class="info-label">Vendor Invoice / DC No:</span>
                            <strong style="font-family: 'JetBrains Mono', monospace; font-size: 10px;">{{ $grn->vendor_invoice_no ?: '-' }}</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                            <span class="info-label">Vendor Invoice Date:</span>
                            <span>{{ $grn->invoice_date ? $grn->invoice_date->format('d M, Y') : '-' }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                            <span class="info-label">Physical Inward Date:</span>
                            <span>{{ $grn->received_date->format('d M, Y') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                            <span class="info-label">Verified &amp; Received By:</span>
                            <strong>{{ $grn->receiver?->name ?? 'Store Admin' }}</strong>
                        </div>
                        @if($grn->purchaseBill)
                        <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                            <span class="info-label">Purchase Bill Ref:</span>
                            <span style="font-family: 'JetBrains Mono', monospace; font-weight: 700;">{{ $grn->purchaseBill->bill_number }}</span>
                        </div>
                        @endif
                        <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                            <span class="info-label">Payment Status:</span>
                            @if($isPaid)
                                <strong style="color: #15803d;">PAID</strong>
                            @else
                                <strong style="color: #dc2626;">NOT PAID (PAYABLE)</strong>
                            @endif
                        </div>
                        @if($grn->notes)
                        <div style="margin-top: 4px; padding-top: 3px; border-top: 1px dashed #e2e8f0; font-size: 8.5px; color: #475569;">
                            <span class="info-label">Inward Remarks:</span> {{ $grn->notes }}
                        </div>
                        @endif
                    </td>
                </tr>
            </table>

            <!-- Received Line Items & Batch Breakdown Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="center" style="width: 26px;">#</th>
                        <th>Product Description &amp; SKU</th>
                        <th class="center" style="width: 90px;">Batch No.</th>
                        <th class="center" style="width: 65px;">Mfg Date</th>
                        <th class="center" style="width: 65px;">Expiry</th>
                        <th class="center" style="width: 68px;">Accepted</th>
                        <th class="right" style="width: 65px;">Rate (₹)</th>
                        <th class="right" style="width: 68px;">Landed/Unit</th>
                        <th class="center" style="width: 44px;">GST</th>
                        <th class="right" style="width: 78px;">Total (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grn->items as $idx => $item)
                    <tr>
                        <td class="center" style="font-weight: 600; color: #64748b;">{{ $idx + 1 }}</td>
                        <td>
                            <strong style="color: #0f172a;">{{ $item->product?->name }}</strong>
                            @if($item->variation)
                                <div style="font-size: 8.5px; color: #475569;">Var: {{ $item->variation->name ?? $item->variation->weight }}</div>
                            @endif
                            <div style="font-size: 8.5px; color: #64748b; font-family: 'JetBrains Mono', monospace;">SKU: {{ $item->product?->sku ?? '-' }}</div>
                        </td>
                        <td class="center">
                            <span class="batch-tag">{{ $item->batch_number }}</span>
                        </td>
                        <td class="center">{{ $item->mfg_date ? $item->mfg_date->format('d/m/Y') : '-' }}</td>
                        <td class="center">
                            @if($item->expiry_date)
                                <span>{{ $item->expiry_date->format('d/m/Y') }}</span>
                            @else
                                <span style="color: #94a3b8;">-</span>
                            @endif
                        </td>
                        <td class="center">
                            <span class="qty-tag">{{ $item->accepted_qty }} units</span>
                            @if($item->rejected_qty > 0)
                                <div style="color: #dc2626; font-size: 8px;">Rej: {{ $item->rejected_qty }}</div>
                            @endif
                        </td>
                        <td class="right">₹{{ number_format($item->unit_purchase_price, 2) }}</td>
                        <td class="right" style="font-weight: 700; color: #184e28;">₹{{ number_format($item->effective_unit_cost, 2) }}</td>
                        <td class="center">{{ (float)$item->tax_rate }}%</td>
                        <td class="right" style="font-weight: 700;">₹{{ number_format($item->total_amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Financials & Quality Declaration -->
            <table class="summary-block-table" style="width: 100%;">
                <tr>
                    <td style="width: 55%; padding-right: 14px;">
                        <div class="declaration-box">
                            <div class="declaration-title"><i class="fas fa-shield-alt"></i> Quality Inspection &amp; Inward Declaration</div>
                            Certified that the goods listed above have been physically inspected upon arrival at the central warehouse. Quantity, packaging integrity, batch numbers, manufacturing &amp; expiry dates have been verified and reconciled with the vendor delivery documents. All items meet the quality standards and have been taken into active stock.
                            @if($grn->purchaseBill)
                            <div style="margin-top: 6px; padding-top: 4px; border-top: 1px dashed #cbd5e1; font-weight: 600; color: #1e293b;">
                                Associated Bill: <span style="font-family: 'JetBrains Mono', monospace;">{{ $grn->purchaseBill->bill_number }}</span> &nbsp;|&nbsp; 
                                Bill Status: <span style="text-transform: uppercase; color: {{ $grn->purchaseBill->payment_status === 'paid' ? '#15803d' : '#b45309' }};">{{ $grn->purchaseBill->payment_status }}</span>
                            </div>
                            @endif
                        </div>
                    </td>
                    <td style="width: 45%;">
                        <table class="totals-table" style="width: 100%;">
                            <tr>
                                <td style="color: #64748b;">Items Taxable Subtotal:</td>
                                <td>₹{{ number_format($grn->total_items_taxable, 2) }}</td>
                            </tr>
                            <tr>
                                <td style="color: #64748b;">Total GST (Input Tax Credit):</td>
                                <td>₹{{ number_format($grn->total_tax_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td style="color: #64748b;">Inward Freight / Cartage:</td>
                                <td style="color: #0284c7;">+₹{{ number_format($grn->inward_freight_charge, 2) }}</td>
                            </tr>
                            <tr>
                                <td style="color: #64748b;">Handling / Unloading Charges:</td>
                                <td style="color: #0284c7;">+₹{{ number_format($grn->inward_handling_charge, 2) }}</td>
                            </tr>
                            <tr class="grand-row">
                                <td>TOTAL LANDED VALUATION:</td>
                                <td>₹{{ number_format($grn->total_landed_cost, 2) }}</td>
                            </tr>
                            <tr style="background: #f8fafc;">
                                <td style="color: #475569; font-weight: 700; font-size: 9.5px;">VENDOR PAYMENT STATUS:</td>
                                <td style="font-weight: 800; font-size: 10px; color: {{ $isPaid ? '#15803d' : '#dc2626' }};">
                                    {{ $isPaid ? 'PAID' : 'NOT PAID' }}
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- Signatures Section -->
            <table class="signatures-table" style="width: 100%;">
                <tr>
                    <td class="sign-cell" style="width: 33.33%;">
                        <div class="sign-line">{{ $grn->receiver?->name ?? 'Store In-charge' }}</div>
                        <div class="sign-role">Goods Received &amp; Counted By</div>
                    </td>
                    <td class="sign-cell" style="width: 33.33%;">
                        <div class="sign-line">Quality Control In-Charge</div>
                        <div class="sign-role">Batch Verification &amp; Inspection</div>
                    </td>
                    <td class="sign-cell" style="width: 33.33%;">
                        <div class="sign-line">For Prakruti Corporation</div>
                        <div class="sign-role">Authorized Signatory / Warehouse Head</div>
                    </td>
                </tr>
            </table>

            <!-- Footer Stamp -->
            <div class="footer-note">
                This is a computer-generated Goods Receipt Note (GRN) issued under Prakruti Organic Central ERP &bull; Generated on {{ now()->format('d M, Y h:i A') }} &bull; Page 1 of 1
            </div>

        </div>
    </div>

</body>
</html>
