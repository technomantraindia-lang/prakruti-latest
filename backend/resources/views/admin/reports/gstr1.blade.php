@extends('admin.layouts.app')

@section('title', 'GSTR-1 Outward Supplies Return')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">GSTR-1: Outward Supplies Return</h1>
            <p class="text-muted small mb-0">Statutory return for sales and outward supplies (Tables 4A B2B, 7 B2C, 12 HSN, 13 Documents).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.reports.gstr1', ['month' => $month, 'export' => 'csv']) }}" class="btn btn-outline-success">
                <i class="fas fa-file-csv me-1"></i> Export GST Portal CSV
            </a>
            <a href="{{ route('admin.reports.gstr2', ['month' => $month]) }}" class="btn btn-outline-primary">
                <i class="fas fa-file-invoice me-1"></i> Go to GSTR-2 (ITC)
            </a>
            <a href="{{ route('admin.reports.gstr3b', ['month' => $month]) }}" class="btn btn-primary">
                <i class="fas fa-calculator me-1"></i> View GSTR-3B
            </a>
        </div>
    </div>

    <!-- Month Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.reports.gstr1') }}" class="row align-items-center g-3">
                <div class="col-auto">
                    <label class="form-label fw-bold mb-0">Select Filing Period (Month):</label>
                </div>
                <div class="col-auto">
                    <input type="month" name="month" class="form-control" value="{{ $month }}" onchange="this.form.submit()">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-dark"><i class="fas fa-sync-alt me-1"></i> Load Return</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-left-primary p-3 bg-white">
                <div class="text-uppercase text-primary fw-bold small">Total Invoices Issued</div>
                <div class="h3 mb-0 fw-bold mt-1 text-dark">{{ $docCount }}</div>
                <div class="text-muted small mt-1 font-monospace">From: {{ $minInvoiceNum ?: 'N/A' }} To: {{ $maxInvoiceNum ?: 'N/A' }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-success p-3 bg-white">
                <div class="text-uppercase text-success fw-bold small">Total Taxable Turnover</div>
                <div class="h3 mb-0 fw-bold mt-1 text-dark">₹{{ number_format($b2bTaxable + $b2cTaxable, 2) }}</div>
                <div class="text-muted small mt-1">B2B: ₹{{ number_format($b2bTaxable, 2) }} | B2C: ₹{{ number_format($b2cTaxable, 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-info p-3 bg-white">
                <div class="text-uppercase text-info fw-bold small">Total Central Tax (CGST)</div>
                <div class="h3 mb-0 fw-bold mt-1 text-dark">₹{{ number_format($b2bCgst + $b2cCgst, 2) }}</div>
                <div class="text-muted small mt-1">Output Central Tax Liability</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-warning p-3 bg-white">
                <div class="text-uppercase text-warning fw-bold small">Total State / IGST Tax</div>
                <div class="h3 mb-0 fw-bold mt-1 text-dark">₹{{ number_format($b2bSgst + $b2cSgst + $b2bIgst + $b2cIgst, 2) }}</div>
                <div class="text-muted small mt-1">SGST: ₹{{ number_format($b2bSgst + $b2cSgst, 2) }} | IGST: ₹{{ number_format($b2bIgst + $b2cIgst, 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Table 4A: B2B Invoices -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-building me-2"></i> Table 4A: Taxable Outward Supplies to Registered Persons (B2B)</h6>
            <span class="badge bg-primary">{{ count($b2bInvoices) }} Invoice(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>GSTIN / UIN</th>
                            <th>Receiver Business Name</th>
                            <th>Invoice Number</th>
                            <th>Invoice Date</th>
                            <th>Place of Supply</th>
                            <th class="text-end">Taxable Value (₹)</th>
                            <th class="text-end">CGST (₹)</th>
                            <th class="text-end">SGST (₹)</th>
                            <th class="text-end">IGST (₹)</th>
                            <th class="text-end">Invoice Total (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($b2bInvoices as $inv)
                            <tr>
                                <td class="font-monospace fw-bold text-primary">{{ $inv['gstin'] }}</td>
                                <td>{{ $inv['buyer_name'] }}</td>
                                <td class="font-monospace">{{ $inv['invoice_number'] }}</td>
                                <td>{{ $inv['invoice_date'] }}</td>
                                <td>{{ $inv['place_of_supply'] }}</td>
                                <td class="text-end fw-bold">{{ number_format($inv['taxable'], 2) }}</td>
                                <td class="text-end">{{ number_format($inv['cgst'], 2) }}</td>
                                <td class="text-end">{{ number_format($inv['sgst'], 2) }}</td>
                                <td class="text-end">{{ number_format($inv['igst'], 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($inv['total'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    No B2B registered orders recorded for this month.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="5" class="text-end">Total B2B Outward Supplies:</td>
                            <td class="text-end">₹{{ number_format($b2bTaxable, 2) }}</td>
                            <td class="text-end">₹{{ number_format($b2bCgst, 2) }}</td>
                            <td class="text-end">₹{{ number_format($b2bSgst, 2) }}</td>
                            <td class="text-end">₹{{ number_format($b2bIgst, 2) }}</td>
                            <td class="text-end">₹{{ number_format($b2bTaxable + $b2bCgst + $b2bSgst + $b2bIgst, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Table 12: HSN Summary -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-tags me-2"></i> Table 12: HSN Summary of Outward Supplies</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>HSN Code</th>
                            <th>Description</th>
                            <th class="text-center">UQC</th>
                            <th class="text-center">Total Quantity</th>
                            <th class="text-end">Taxable Value (₹)</th>
                            <th class="text-end">CGST (₹)</th>
                            <th class="text-end">SGST (₹)</th>
                            <th class="text-end">IGST (₹)</th>
                            <th class="text-end">Total Tax Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hsnSummary as $hsn => $item)
                            <tr>
                                <td class="font-monospace fw-bold text-center">{{ $hsn }}</td>
                                <td>{{ $item['description'] }}</td>
                                <td class="text-center">{{ $item['uqc'] }}</td>
                                <td class="text-center fw-bold">{{ $item['total_qty'] }}</td>
                                <td class="text-end fw-bold">{{ number_format($item['total_taxable'], 2) }}</td>
                                <td class="text-end">{{ number_format($item['cgst'], 2) }}</td>
                                <td class="text-end">{{ number_format($item['sgst'], 2) }}</td>
                                <td class="text-end">{{ number_format($item['igst'], 2) }}</td>
                                <td class="text-end fw-bold text-success">{{ number_format($item['total_tax'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    No outward supplies for the selected month.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
