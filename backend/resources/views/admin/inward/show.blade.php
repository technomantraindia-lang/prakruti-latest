@extends('admin.layouts.app')
@section('title', 'GRN #' . $grn->grn_number)

@push('styles')
<style>
    /* Screen Styles */
    .grn-voucher-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .grn-gold-bar {
        height: 5px;
        background: linear-gradient(90deg, #184e28 0%, #2d5a27 60%, #d9ad28 100%);
    }
    .grn-voucher-body {
        padding: 30px;
    }
    .grn-logo {
        max-height: 58px;
        max-width: 130px;
        object-fit: contain;
    }
    .grn-badge-box {
        border: 2px solid #184e28;
        border-radius: 6px;
        overflow: hidden;
        background: #ffffff;
    }
    .grn-badge-header {
        background: #184e28;
        color: #ffffff;
        font-size: 11px;
        font-weight: 800;
        padding: 5px 12px;
        letter-spacing: 0.8px;
        text-align: center;
        text-transform: uppercase;
    }
    .grn-badge-body {
        padding: 8px 14px;
        font-size: 10px;
        background: #f8fafc;
    }
    .grn-num {
        font-family: 'JetBrains Mono', monospace;
        font-size: 15px;
        font-weight: 800;
        color: #184e28;
        letter-spacing: 0.5px;
    }
    .grn-info-panel {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        padding: 16px;
        height: 100%;
    }
    .grn-info-panel-title {
        font-size: 11px;
        font-weight: 800;
        color: #184e28;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding-bottom: 6px;
        margin-bottom: 10px;
        border-bottom: 1.5px solid #cbd5e1;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .grn-table {
        border: 1px solid #cbd5e1;
        border-collapse: collapse;
        width: 100%;
    }
    .grn-table th {
        background: #184e28 !important;
        color: #ffffff !important;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        padding: 8px 10px;
        border: 1px solid #143d20;
    }
    .grn-table td {
        padding: 8px 10px;
        border: 1px solid #e2e8f0;
        font-size: 11px;
        vertical-align: middle;
    }
    .grn-table tbody tr:nth-child(even) td {
        background-color: #f8fafc;
    }
    .grn-signatures-row {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }
    .grn-sign-line {
        border-top: 1.5px solid #0f172a;
        margin-top: 48px;
        padding-top: 5px;
        font-weight: 700;
        font-size: 11px;
        text-align: center;
    }
    .grn-sign-role {
        font-size: 10px;
        color: #64748b;
        text-align: center;
    }
    .grn-footer-note {
        margin-top: 24px;
        text-align: center;
        font-size: 9.5px;
        color: #94a3b8;
        border-top: 1px dashed #e2e8f0;
        padding-top: 8px;
    }

    /* Print Specific Styles */
    @media print {
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 12mm 12mm;
        }
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            background: #ffffff !important;
            color: #0f172a !important;
            font-size: 9.5px !important;
        }
        .sidebar, .admin-sidebar, .navbar, .breadcrumb, .alert, .no-print, .mobile-menu-toggle, .sidebar-backdrop, footer {
            display: none !important;
        }
        .main-content {
            padding: 0 !important;
            margin: 0 !important;
        }
        .grn-voucher-card {
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        .grn-voucher-body {
            padding: 0 !important;
        }
        .grn-table th {
            background: #184e28 !important;
            color: #ffffff !important;
            font-size: 9px !important;
            padding: 5px 6px !important;
        }
        .grn-table td {
            font-size: 9.5px !important;
            padding: 5px 6px !important;
        }
        .grn-table tr {
            page-break-inside: avoid !important;
        }
        .grn-signatures-row {
            page-break-inside: avoid !important;
            margin-top: 24px !important;
            padding-top: 14px !important;
        }
        .grn-sign-line {
            margin-top: 40px !important;
            font-size: 9.5px !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $isPaid = ($grn->payment_status === 'paid' || $grn->purchaseBill?->payment_status === 'paid');
@endphp

<!-- Top Action Bar (Hidden on Print) -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 no-print">
    <div>
        <a href="{{ route('admin.inward.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i> Back to Inward List
        </a>
        <h2 class="mb-0 font-monospace d-flex align-items-center gap-2 flex-wrap">
            GRN: {{ $grn->grn_number }}
            <span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>Verified &amp; Inwarded</span>
            @if($isPaid)
                <span class="badge bg-success fs-6"><i class="fas fa-check-double me-1"></i>PAID</span>
            @else
                <span class="badge bg-danger fs-6"><i class="fas fa-exclamation-circle me-1"></i>NOT PAID</span>
            @endif
        </h2>
        <div class="text-muted small mt-1">
            Inward Date: <strong>{{ $grn->received_date->format('d M, Y') }}</strong> &bull; 
            Vendor: <strong>{{ $grn->vendor?->display_name }}</strong>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <!-- Quick Payment Status Toggle -->
        <form action="{{ route('admin.inward.payment-status', $grn) }}" method="POST" class="d-inline">
            @csrf
            @method('PATCH')
            @if($isPaid)
                <input type="hidden" name="payment_status" value="unpaid">
                <button type="submit" class="btn btn-outline-danger" title="Mark this GRN as Not Paid">
                    <i class="fas fa-times-circle me-1"></i> Mark as Not Paid
                </button>
            @else
                <input type="hidden" name="payment_status" value="paid">
                <button type="submit" class="btn btn-outline-success" title="Mark this GRN as Paid">
                    <i class="fas fa-check-circle me-1"></i> Mark as Paid
                </button>
            @endif
        </form>

        <a href="{{ route('admin.inward.create') }}" class="btn btn-outline-primary">
            <i class="fas fa-plus me-1"></i> New Inward Entry
        </a>
        <a href="{{ route('admin.inward.print', $grn) }}" target="_blank" class="btn btn-outline-secondary">
            <i class="fas fa-external-link-alt me-1"></i> Fullscreen Voucher
        </a>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print GRN
        </button>
    </div>
</div>

<!-- Official GRN Document Sheet -->
<div class="grn-voucher-card">
    <div class="grn-gold-bar"></div>
    <div class="grn-voucher-body">

        <!-- Company Header & Doc Badge -->
        <div class="row align-items-start mb-3">
            <div class="col-7 col-sm-8">
                <div class="d-flex align-items-start gap-3">
                    <img src="{{ asset('assets/prakruti-logo.png') }}" alt="Prakruti Organic logo" class="grn-logo">
                    <div>
                        <h3 class="mb-0 fw-bold" style="color: #184e28; letter-spacing: 0.5px; font-size: 1.35rem;">PRAKRUTI CORPORATION</h3>
                        <div class="small fw-bold text-uppercase" style="color: #b45309; letter-spacing: 0.5px; font-size: 0.72rem;">
                            Crafted Traditionally, Tested Scientifically
                        </div>
                        <div class="text-muted" style="font-size: 0.76rem; line-height: 1.35;">
                            Plot No. 42, GIDC Phase II, Vatva, Ahmedabad, Gujarat - 382445<br>
                            <strong>GSTIN:</strong> 24AAACP9876K1Z9 &nbsp;|&nbsp; <strong>State Code:</strong> 24 (Gujarat)<br>
                            <strong>Email:</strong> care@prakruticorporation.com &nbsp;|&nbsp; <strong>Phone:</strong> +91 79 2583 0000<br>
                            <strong>Web:</strong> www.prakruticorporation.com
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-5 col-sm-4 text-end">
                <div class="grn-badge-box d-inline-block text-start w-100" style="max-width: 280px;">
                    <div class="grn-badge-header">GOODS RECEIPT NOTE (GRN)</div>
                    <div class="grn-badge-body">
                        <div><span class="text-muted">GRN No:</span> <span class="grn-num">{{ $grn->grn_number }}</span></div>
                        <div class="mt-1"><span class="text-muted">Inward Date:</span> <strong>{{ $grn->received_date->format('d M, Y') }}</strong></div>
                        <div class="mt-1"><span class="text-muted">Inward:</span> <strong class="text-success"><i class="fas fa-check-circle"></i> Verified &amp; Inwarded</strong></div>
                        <div class="mt-1">
                            <span class="text-muted">Payment:</span>
                            @if($isPaid)
                                <strong class="text-success"><i class="fas fa-check-circle"></i> PAID</strong>
                            @else
                                <strong class="text-danger"><i class="fas fa-times-circle"></i> NOT PAID</strong>
                            @endif
                        </div>
                        <div class="mt-1"><span class="text-muted">Location:</span> Central Warehouse (Bay A)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supplier & Invoice Details Box -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="grn-info-panel">
                    <div class="grn-info-panel-title">
                        <i class="fas fa-truck text-primary"></i> SUPPLIER / VENDOR DETAILS
                    </div>
                    <h5 class="fw-bold mb-1" style="font-size: 1rem;">{{ $grn->vendor?->display_name }}</h5>
                    @if($grn->vendor?->gstin)
                        <div class="small font-monospace mb-1"><span class="text-muted">GSTIN:</span> <strong>{{ $grn->vendor->gstin }}</strong></div>
                    @endif
                    <div class="small text-muted mb-1">
                        @if($grn->vendor?->phone)<span><i class="fas fa-phone me-1"></i>{{ $grn->vendor->phone }}</span>@endif
                        @if($grn->vendor?->email)<span class="ms-2"><i class="fas fa-envelope me-1"></i>{{ $grn->vendor->email }}</span>@endif
                    </div>
                    <div class="small text-muted">
                        <span class="text-muted">Address:</span>
                        {{ $grn->vendor?->address ?: 'N/A' }}, {{ $grn->vendor?->city }} {{ $grn->vendor?->state }} - {{ $grn->vendor?->pincode }}
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="grn-info-panel">
                    <div class="grn-info-panel-title">
                        <i class="fas fa-receipt text-info"></i> INVOICE &amp; INWARD REFERENCES
                    </div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Vendor Invoice / DC No:</span>
                        <strong class="font-monospace fs-6">{{ $grn->vendor_invoice_no ?: '-' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Vendor Invoice Date:</span>
                        <span>{{ $grn->invoice_date ? $grn->invoice_date->format('d M, Y') : '-' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Physical Inward Date:</span>
                        <span>{{ $grn->received_date->format('d M, Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Verified &amp; Received By:</span>
                        <strong>{{ $grn->receiver?->name ?? 'Store Admin' }}</strong>
                    </div>
                    @if($grn->purchaseBill)
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Purchase Bill Ref:</span>
                        <span class="font-monospace fw-bold">{{ $grn->purchaseBill->bill_number }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">Payment Status:</span>
                        @if($isPaid)
                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>PAID</span>
                        @else
                            <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>NOT PAID</span>
                        @endif
                    </div>
                    @if($grn->notes)
                    <div class="mt-2 pt-2 border-top small text-muted">
                        <span class="fw-semibold">Inward Remarks:</span> {{ $grn->notes }}
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Received Line Items & Batch Breakdown Table -->
        <div class="table-responsive mb-4">
            <table class="table grn-table mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 35px;">#</th>
                        <th>Product Description &amp; SKU</th>
                        <th class="text-center" style="width: 110px;">Batch No.</th>
                        <th class="text-center" style="width: 90px;">Mfg Date</th>
                        <th class="text-center" style="width: 90px;">Expiry</th>
                        <th class="text-center" style="width: 95px;">Accepted</th>
                        <th class="text-end" style="width: 95px;">Purchase Rate</th>
                        <th class="text-end" style="width: 105px;">Landed Unit Cost</th>
                        <th class="text-center" style="width: 65px;">GST</th>
                        <th class="text-end" style="width: 110px;">Total (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grn->items as $idx => $item)
                    <tr>
                        <td class="text-center text-muted fw-bold">{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $item->product?->name }}</strong>
                            @if($item->variation)
                                <div class="small text-muted">Var: {{ $item->variation->name ?? $item->variation->weight }}</div>
                            @endif
                            <div class="small text-muted font-monospace">SKU: {{ $item->product?->sku ?? '-' }}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border font-monospace">{{ $item->batch_number }}</span>
                        </td>
                        <td class="text-center">{{ $item->mfg_date ? $item->mfg_date->format('d M, Y') : '-' }}</td>
                        <td class="text-center">
                            @if($item->expiry_date)
                                @php $daysLeft = (int) now()->startOfDay()->diffInDays($item->expiry_date->startOfDay(), false); @endphp
                                <span>{{ $item->expiry_date->format('d M, Y') }}</span>
                                @if($daysLeft <= 60 && $daysLeft >= 0)
                                    <div class="badge bg-warning text-dark" style="font-size: 0.65rem;">Near Expiry ({{ $daysLeft }}d)</div>
                                @elseif($daysLeft < 0)
                                    <div class="badge bg-danger" style="font-size: 0.65rem;">Expired</div>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success fs-6">{{ $item->accepted_qty }} units</span>
                            @if($item->rejected_qty > 0)
                                <div class="small text-danger fw-bold mt-1">Rej: {{ $item->rejected_qty }}</div>
                            @endif
                        </td>
                        <td class="text-end">₹{{ number_format($item->unit_purchase_price, 2) }}</td>
                        <td class="text-end">
                            <strong class="text-primary fs-6">₹{{ number_format($item->effective_unit_cost, 2) }}</strong>
                        </td>
                        <td class="text-center">{{ (float)$item->tax_rate }}%</td>
                        <td class="text-end fw-bold">₹{{ number_format($item->total_amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Financials & Quality Declaration -->
        <div class="row g-3 mb-4">
            <div class="col-md-7">
                <div class="p-3 border rounded bg-light h-100" style="font-size: 0.8rem; line-height: 1.45; color: #475569;">
                    <div class="fw-bold text-uppercase mb-2" style="color: #184e28; font-size: 0.85rem;">
                        <i class="fas fa-shield-alt me-1"></i> Quality Inspection &amp; Inward Declaration
                    </div>
                    Certified that the goods listed above have been physically inspected upon arrival at the central warehouse. Quantity, packaging integrity, batch numbers, manufacturing &amp; expiry dates have been verified and reconciled with the vendor delivery documents. All items meet the quality standards and have been taken into active stock.
                    @if($grn->purchaseBill)
                    <div class="mt-3 pt-2 border-top">
                        <span class="text-muted">Associated Bill:</span>
                        <span class="font-monospace fw-bold text-dark">{{ $grn->purchaseBill->bill_number }}</span>
                        &bull;
                        <span class="text-muted">Payment Status:</span>
                        <span class="badge {{ $grn->purchaseBill->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ ucfirst($grn->purchaseBill->payment_status) }}
                        </span>
                        &bull;
                        <span class="text-muted">Due Date:</span>
                        <span>{{ $grn->purchaseBill->due_date ? $grn->purchaseBill->due_date->format('d M, Y') : '-' }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <div class="col-md-5">
                <div class="card border">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Items Taxable Subtotal:</span>
                            <span>₹{{ number_format($grn->total_items_taxable, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Total GST (Input Tax Credit):</span>
                            <span>₹{{ number_format($grn->total_tax_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1 small">
                            <span class="text-muted">Inward Freight / Cartage:</span>
                            <span class="text-primary">+₹{{ number_format($grn->inward_freight_charge, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">Handling / Unloading Charges:</span>
                            <span class="text-primary">+₹{{ number_format($grn->inward_handling_charge, 2) }}</span>
                        </div>
                        <div class="p-2 rounded d-flex justify-content-between align-items-center mb-2" style="background: #184e28; color: #ffffff;">
                            <span class="fw-bold small text-uppercase">TOTAL LANDED VALUATION:</span>
                            <strong class="fs-5">₹{{ number_format($grn->total_landed_cost, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="small text-muted fw-bold">VENDOR PAYMENT STATUS:</span>
                            @if($isPaid)
                                <span class="badge bg-success fs-6"><i class="fas fa-check-double me-1"></i>PAID</span>
                            @else
                                <span class="badge bg-danger fs-6"><i class="fas fa-clock me-1"></i>NOT PAID</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Official Signatures Block -->
        <div class="row grn-signatures-row text-center">
            <div class="col-4">
                <div class="grn-sign-line">{{ $grn->receiver?->name ?? 'Store In-charge' }}</div>
                <div class="grn-sign-role">Goods Received &amp; Counted By</div>
            </div>
            <div class="col-4">
                <div class="grn-sign-line">Quality Control In-Charge</div>
                <div class="grn-sign-role">Batch Verification &amp; Inspection</div>
            </div>
            <div class="col-4">
                <div class="grn-sign-line">For Prakruti Corporation</div>
                <div class="grn-sign-role">Authorized Signatory / Warehouse Head</div>
            </div>
        </div>

        <!-- Footer Stamp -->
        <div class="grn-footer-note">
            This is a computer-generated Goods Receipt Note (GRN) issued under Prakruti Organic Central ERP &bull; Generated on {{ now()->format('d M, Y h:i A') }} &bull; Page 1 of 1
        </div>

    </div>
</div>
@endsection
