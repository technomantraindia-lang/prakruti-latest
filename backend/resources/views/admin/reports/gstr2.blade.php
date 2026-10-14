@extends('admin.layouts.app')

@section('title', 'GSTR-2 Inward Supplies (ITC Register)')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">GSTR-2: Inward Supplies &amp; ITC Register</h1>
            <p class="text-muted small mb-0">Record of vendor purchases, bills, and Input Tax Credit available for GST offset.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.reports.gstr2', ['month' => $month, 'export' => 'csv']) }}" class="btn btn-outline-success">
                <i class="fas fa-file-csv me-1"></i> Export ITC Register CSV
            </a>
            <a href="{{ route('admin.reports.gstr1', ['month' => $month]) }}" class="btn btn-outline-primary">
                <i class="fas fa-file-invoice me-1"></i> View GSTR-1
            </a>
            <a href="{{ route('admin.reports.gstr3b', ['month' => $month]) }}" class="btn btn-primary">
                <i class="fas fa-calculator me-1"></i> View GSTR-3B Summary
            </a>
        </div>
    </div>

    <!-- Month Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.reports.gstr2') }}" class="row align-items-center g-3">
                <div class="col-auto">
                    <label class="form-label fw-bold mb-0">Select Filing Period (Month):</label>
                </div>
                <div class="col-auto">
                    <input type="month" name="month" class="form-control" value="{{ $month }}" onchange="this.form.submit()">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-dark"><i class="fas fa-sync-alt me-1"></i> Load Inward Bills</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-left-primary p-3 bg-white">
                <div class="text-uppercase text-primary fw-bold small">Total Purchase Bills</div>
                <div class="h3 mb-0 fw-bold mt-1 text-dark">{{ $purchaseBills->count() }}</div>
                <div class="text-muted small mt-1">From Registered & Unregistered Vendors</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-success p-3 bg-white">
                <div class="text-uppercase text-success fw-bold small">Total Taxable Purchases</div>
                <div class="h3 mb-0 fw-bold mt-1 text-dark">₹{{ number_format($totTaxable, 2) }}</div>
                <div class="text-muted small mt-1">Raw Materials & Finished Ayurvedic Goods</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-info p-3 bg-white">
                <div class="text-uppercase text-info fw-bold small">Eligible Input Tax Credit (ITC)</div>
                <div class="h3 mb-0 fw-bold mt-1 text-success">₹{{ number_format($netItc, 2) }}</div>
                <div class="text-muted small mt-1">CGST: ₹{{ number_format($totCgst, 2) }} | SGST: ₹{{ number_format($totSgst, 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-warning p-3 bg-white">
                <div class="text-uppercase text-warning fw-bold small">Total Inward Bill Value</div>
                <div class="h3 mb-0 fw-bold mt-1 text-dark">₹{{ number_format($totAmount, 2) }}</div>
                <div class="text-muted small mt-1">Accounts Payable Incurred</div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-receipt me-2"></i> Inward Purchase Bills &amp; ITC Register</h6>
            <span class="badge bg-success">Inputs Eligible</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Supplier GSTIN</th>
                            <th>Vendor Name</th>
                            <th>Bill Number</th>
                            <th>Invoice Date</th>
                            <th class="text-end">Taxable (₹)</th>
                            <th class="text-end">CGST (₹)</th>
                            <th class="text-end">SGST (₹)</th>
                            <th class="text-end">IGST (₹)</th>
                            <th class="text-end">Total Amount (₹)</th>
                            <th class="text-center">ITC Eligibility</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseBills as $bill)
                            <tr>
                                <td class="font-monospace fw-bold text-primary">{{ $bill->vendor->gstin ?: 'Unregistered' }}</td>
                                <td>
                                    <strong>{{ $bill->vendor->name ?? 'N/A' }}</strong><br>
                                    <small class="text-muted">{{ $bill->vendor->company_name ?? '' }}</small>
                                </td>
                                <td class="font-monospace">{{ $bill->bill_number }}</td>
                                <td>{{ $bill->invoice_date?->format('d-M-Y') }}</td>
                                <td class="text-end fw-bold">{{ number_format($bill->total_taxable, 2) }}</td>
                                <td class="text-end text-success">{{ number_format($bill->cgst, 2) }}</td>
                                <td class="text-end text-success">{{ number_format($bill->sgst, 2) }}</td>
                                <td class="text-end text-success">{{ number_format($bill->igst, 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($bill->total_amount, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Eligible (Inputs)</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fas fa-truck-loading fa-3x mb-3 text-secondary"></i>
                                    <p class="mb-0">No purchase bills recorded for the selected month.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4" class="text-end">Total Inward Supplies / ITC:</td>
                            <td class="text-end">₹{{ number_format($totTaxable, 2) }}</td>
                            <td class="text-end text-success">₹{{ number_format($totCgst, 2) }}</td>
                            <td class="text-end text-success">₹{{ number_format($totSgst, 2) }}</td>
                            <td class="text-end text-success">₹{{ number_format($totIgst, 2) }}</td>
                            <td class="text-end">₹{{ number_format($totAmount, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
