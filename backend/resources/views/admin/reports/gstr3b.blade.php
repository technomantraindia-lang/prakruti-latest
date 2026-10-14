@extends('admin.layouts.app')

@section('title', 'GSTR-3B Monthly Return Summary')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">GSTR-3B: Monthly Summary Return</h1>
            <p class="text-muted small mb-0">Summary of outward sales liabilities, inward eligible ITC, and final cash tax payable.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.reports.gstr1', ['month' => $month]) }}" class="btn btn-outline-primary">
                <i class="fas fa-file-invoice me-1"></i> View GSTR-1
            </a>
            <a href="{{ route('admin.reports.gstr2', ['month' => $month]) }}" class="btn btn-outline-success">
                <i class="fas fa-receipt me-1"></i> View GSTR-2 (ITC)
            </a>
            <button onclick="window.print()" class="btn btn-dark">
                <i class="fas fa-print me-1"></i> Print Summary
            </button>
        </div>
    </div>

    <!-- Month Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.reports.gstr3b') }}" class="row align-items-center g-3">
                <div class="col-auto">
                    <label class="form-label fw-bold mb-0">Select Filing Period (Month):</label>
                </div>
                <div class="col-auto">
                    <input type="month" name="month" class="form-control" value="{{ $month }}" onchange="this.form.submit()">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-dark"><i class="fas fa-sync-alt me-1"></i> Compute Return</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Highlight: Net Tax Payable in Cash -->
    <div class="card shadow mb-4 border-left-success bg-white">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="fw-bold text-dark mb-1">Net GST Tax Payable in Cash for {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</h5>
                    <p class="text-muted small mb-0">Calculated after full offset of Eligible Input Tax Credit against Outward Tax Liabilities.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="display-6 fw-bold text-primary">₹{{ number_format($totalCashPayable, 2) }}</div>
                    <small class="text-muted">CGST: ₹{{ number_format($cashPayableCgst, 2) }} | SGST: ₹{{ number_format($cashPayableSgst, 2) }} | IGST: ₹{{ number_format($cashPayableIgst, 2) }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- 3.1 Details of Outward Supplies -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-arrow-up-right-from-square me-2"></i> 3.1 Details of Outward Supplies and Inward Supplies liable to reverse charge</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nature of Supplies</th>
                            <th class="text-end">Total Taxable Value (₹)</th>
                            <th class="text-end">Integrated Tax (IGST) (₹)</th>
                            <th class="text-end">Central Tax (CGST) (₹)</th>
                            <th class="text-end">State/UT Tax (SGST) (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>(a) Outward taxable supplies (other than zero rated, nil rated and exempted)</strong></td>
                            <td class="text-end fw-bold">₹{{ number_format($outwardTaxable, 2) }}</td>
                            <td class="text-end">₹{{ number_format($outwardIgst, 2) }}</td>
                            <td class="text-end">₹{{ number_format($outwardCgst, 2) }}</td>
                            <td class="text-end">₹{{ number_format($outwardSgst, 2) }}</td>
                        </tr>
                        <tr>
                            <td>(b) Outward taxable supplies (zero rated)</td>
                            <td class="text-end">₹0.00</td>
                            <td class="text-end">₹0.00</td>
                            <td class="text-end">₹0.00</td>
                            <td class="text-end">₹0.00</td>
                        </tr>
                        <tr>
                            <td>(c) Other outward supplies (Nil rated, exempted)</td>
                            <td class="text-end">₹0.00</td>
                            <td class="text-end">-</td>
                            <td class="text-end">-</td>
                            <td class="text-end">-</td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td>Total Outward Liability:</td>
                            <td class="text-end">₹{{ number_format($outwardTaxable, 2) }}</td>
                            <td class="text-end">₹{{ number_format($outwardIgst, 2) }}</td>
                            <td class="text-end">₹{{ number_format($outwardCgst, 2) }}</td>
                            <td class="text-end">₹{{ number_format($outwardSgst, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- 4. Eligible ITC -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 fw-bold text-success"><i class="fas fa-hand-holding-dollar me-2"></i> 4. Eligible Input Tax Credit (ITC)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Details</th>
                            <th class="text-end">Integrated Tax (IGST) (₹)</th>
                            <th class="text-end">Central Tax (CGST) (₹)</th>
                            <th class="text-end">State/UT Tax (SGST) (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-light fw-bold">
                            <td colspan="4">(A) ITC Available (whether in full or part)</td>
                        </tr>
                        <tr>
                            <td class="ps-4">(1) Inward supplies from registered persons (Purchase Bills)</td>
                            <td class="text-end text-success">₹{{ number_format($inwardIgst, 2) }}</td>
                            <td class="text-end text-success">₹{{ number_format($inwardCgst, 2) }}</td>
                            <td class="text-end text-success">₹{{ number_format($inwardSgst, 2) }}</td>
                        </tr>
                        <tr class="table-light fw-bold">
                            <td colspan="4">(B) ITC Reversed</td>
                        </tr>
                        <tr>
                            <td class="ps-4">(1) Returns to Vendor (Debit Notes / ITC Reversal)</td>
                            <td class="text-end text-danger">₹{{ number_format($revIgst, 2) }}</td>
                            <td class="text-end text-danger">₹{{ number_format($revCgst, 2) }}</td>
                            <td class="text-end text-danger">₹{{ number_format($revSgst, 2) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="table-success fw-bold">
                        <tr>
                            <td>(C) Net ITC Available (A) - (B):</td>
                            <td class="text-end">₹{{ number_format($netItcIgst, 2) }}</td>
                            <td class="text-end">₹{{ number_format($netItcCgst, 2) }}</td>
                            <td class="text-end">₹{{ number_format($netItcSgst, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- 5.1 Payment of Tax -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-coins me-2"></i> 5.1 Payment of Tax (Liability vs ITC Set-off)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th class="text-end">Output Liability (₹)</th>
                            <th class="text-end">Paid via ITC (₹)</th>
                            <th class="text-end">Tax Payable in Cash (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Integrated Tax (IGST)</td>
                            <td class="text-end">₹{{ number_format($outwardIgst, 2) }}</td>
                            <td class="text-end text-success">₹{{ number_format(min($outwardIgst, $netItcIgst), 2) }}</td>
                            <td class="text-end fw-bold text-primary">₹{{ number_format($cashPayableIgst, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Central Tax (CGST)</td>
                            <td class="text-end">₹{{ number_format($outwardCgst, 2) }}</td>
                            <td class="text-end text-success">₹{{ number_format(min($outwardCgst, $netItcCgst), 2) }}</td>
                            <td class="text-end fw-bold text-primary">₹{{ number_format($cashPayableCgst, 2) }}</td>
                        </tr>
                        <tr>
                            <td>State/UT Tax (SGST)</td>
                            <td class="text-end">₹{{ number_format($outwardSgst, 2) }}</td>
                            <td class="text-end text-success">₹{{ number_format(min($outwardSgst, $netItcSgst), 2) }}</td>
                            <td class="text-end fw-bold text-primary">₹{{ number_format($cashPayableSgst, 2) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="table-dark fw-bold">
                        <tr>
                            <td>Total Cash Tax Liability:</td>
                            <td class="text-end">₹{{ number_format($outwardIgst + $outwardCgst + $outwardSgst, 2) }}</td>
                            <td class="text-end text-success">₹{{ number_format(min($outwardIgst, $netItcIgst) + min($outwardCgst, $netItcCgst) + min($outwardSgst, $netItcSgst), 2) }}</td>
                            <td class="text-end text-warning fs-6">₹{{ number_format($totalCashPayable, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
