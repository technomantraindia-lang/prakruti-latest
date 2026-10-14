@extends('admin.layouts.app')

@section('title', 'Purchase Return: ' . $purchaseReturn->return_number)

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Purchase Return: {{ $purchaseReturn->return_number }}</h1>
            <p class="text-muted small mb-0">Issued on {{ $purchaseReturn->created_at->format('d M Y, h:i A') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Returns
            </a>
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="fas fa-print me-1"></i> Print Return Memo
            </button>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-primary">Vendor Details</h6>
                </div>
                <div class="card-body">
                    <h5>{{ $purchaseReturn->vendor->name ?? 'N/A' }}</h5>
                    <p class="mb-1 text-muted">{{ $purchaseReturn->vendor->company_name ?? '' }}</p>
                    <p class="mb-1"><strong>GSTIN:</strong> {{ $purchaseReturn->vendor->gstin ?? 'Not Available' }}</p>
                    <p class="mb-1"><strong>Phone:</strong> {{ $purchaseReturn->vendor->phone ?? '-' }}</p>
                    <p class="mb-0"><strong>Address:</strong> {{ $purchaseReturn->vendor->address ?? '-' }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow h-100 border-left-danger">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-danger">Associated Vendor Debit Note</h6>
                </div>
                <div class="card-body">
                    @if($purchaseReturn->debitNote)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fs-5 fw-bold font-monospace text-danger">{{ $purchaseReturn->debitNote->debit_note_number }}</span>
                            <span class="badge bg-success">ITC Reversed</span>
                        </div>
                        <p class="mb-1"><strong>Total Debit Note Amount:</strong> <span class="fs-5 fw-bold text-danger">₹{{ number_format($purchaseReturn->debitNote->total_amount, 2) }}</span></p>
                        <p class="mb-1"><strong>Reason:</strong> {{ $purchaseReturn->reason }}</p>
                        <p class="mb-0 text-muted small">This debit note has adjusted Accounts Payable and reversed Input Tax Credit in the General Ledger.</p>
                    @else
                        <p class="text-muted mb-0">No Debit Note linked.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 fw-bold text-primary">Returned Items & Batches</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Product Name</th>
                            <th>Batch Number</th>
                            <th class="text-center">Returned Qty</th>
                            <th class="text-end">Unit Cost (₹)</th>
                            <th class="text-end">Taxable (₹)</th>
                            <th class="text-end">Tax Rate</th>
                            <th class="text-end">Tax Amount (₹)</th>
                            <th class="text-end">Total Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseReturn->items as $idx => $item)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td><strong>{{ $item->product->name ?? 'Product #' . $item->product_id }}</strong></td>
                                <td class="font-monospace text-center">{{ $item->batch_number ?: 'N/A' }}</td>
                                <td class="text-center fw-bold text-danger">{{ $item->return_qty }}</td>
                                <td class="text-end">{{ number_format($item->unit_cost, 2) }}</td>
                                <td class="text-end">{{ number_format($item->return_qty * $item->unit_cost, 2) }}</td>
                                <td class="text-end">{{ $item->tax_rate }}%</td>
                                <td class="text-end">{{ number_format($item->tax_amount, 2) }}</td>
                                <td class="text-end fw-bold text-danger">{{ number_format($item->total_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="5" class="text-end fw-bold">Subtotal (Taxable):</td>
                            <td class="text-end fw-bold">₹{{ number_format($purchaseReturn->total_taxable, 2) }}</td>
                            <td colspan="2" class="text-end fw-bold">Taxes (CGST+SGST/IGST):</td>
                            <td class="text-end fw-bold">₹{{ number_format($purchaseReturn->cgst + $purchaseReturn->sgst + $purchaseReturn->igst, 2) }}</td>
                        </tr>
                        <tr class="table-warning">
                            <td colspan="8" class="text-end fw-bold fs-6">Grand Total Debited:</td>
                            <td class="text-end fw-bold fs-6 text-danger">₹{{ number_format($purchaseReturn->total_amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
