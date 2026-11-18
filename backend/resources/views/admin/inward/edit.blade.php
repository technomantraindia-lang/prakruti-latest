@extends('admin.layouts.app')

@section('title', 'Edit Inward Stock')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <a href="{{ route('admin.inward.show', $grn) }}" class="btn btn-outline-secondary btn-sm mb-2">
            <i class="fas fa-arrow-left me-1"></i> Back to GRN
        </a>
        <h2 class="mb-1"><i class="fas fa-edit me-2 text-warning"></i>Edit Inward Entry</h2>
        <p class="text-muted mb-0">Update GRN details, batch number, manufacturing date, expiry date, and notes.</p>
    </div>
    <span class="badge bg-success fs-6">{{ $grn->grn_number }}</span>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <strong>Please correct the following:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.inward.update', $grn) }}">
    @csrf
    @method('PUT')

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold">GRN Details</div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Vendor</label>
                    <input type="text" class="form-control" value="{{ $grn->vendor?->display_name }}" readonly>
                    <small class="text-muted">Vendor and package assignments are locked after inward.</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Vendor Invoice No.</label>
                    <input type="text" name="vendor_invoice_no" class="form-control" value="{{ old('vendor_invoice_no', $grn->vendor_invoice_no) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Invoice Date</label>
                    <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', optional($grn->invoice_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Received Date</label>
                    <input type="date" name="received_date" class="form-control" required value="{{ old('received_date', optional($grn->received_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Payment Status</label>
                    <select name="payment_status" class="form-select" required>
                        <option value="unpaid" @selected(old('payment_status', $grn->payment_status) === 'unpaid')>Not Paid</option>
                        <option value="paid" @selected(old('payment_status', $grn->payment_status) === 'paid')>Paid</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold">Notes</label>
                    <textarea name="notes" rows="3" class="form-control">{{ old('notes', $grn->notes) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <div class="fw-bold">Products, Packaging &amp; Batch Details</div>
            <small class="text-muted">Example: Besan 500 g — SKU and weight are shown from the selected package. Stock quantity and package selection stay locked to protect inventory.</small>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product / Package</th>
                        <th>SKU</th>
                        <th>Weight</th>
                        <th>Accepted</th>
                        <th>Batch Number</th>
                        <th>Mfg Date</th>
                        <th>Expiry Date</th>
                        <th>Rejection Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grn->items as $index => $item)
                        @php
                            $weight = $item->variation?->net_qty !== null
                                ? rtrim(rtrim(number_format((float) $item->variation->net_qty, 3, '.', ''), '0'), '.') . ' ' . ($item->variation->net_unit ?: 'g')
                                : ($item->variation?->attr_val ?: '-');
                        @endphp
                        <tr>
                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                            <td>
                                <strong>{{ $item->product?->name }}</strong>
                                <div class="small text-muted">Pack size: {{ $item->variation?->attr_val ?: 'Default product' }}</div>
                            </td>
                            <td class="font-monospace small">{{ $item->variation?->sku ?: ($item->product?->sku ?: '-') }}</td>
                            <td class="fw-bold">{{ $weight }}</td>
                            <td>
                                <span class="badge bg-success">{{ $item->accepted_qty }} units</span>
                                <div class="small text-muted">Received: {{ $item->received_qty }}</div>
                            </td>
                            <td>
                                <input type="text" name="items[{{ $index }}][batch_number]" class="form-control form-control-sm font-monospace" required value="{{ old("items.$index.batch_number", $item->batch_number) }}">
                            </td>
                            <td>
                                <input type="date" name="items[{{ $index }}][mfg_date]" class="form-control form-control-sm" value="{{ old("items.$index.mfg_date", optional($item->mfg_date)->format('Y-m-d')) }}">
                            </td>
                            <td>
                                <input type="date" name="items[{{ $index }}][expiry_date]" class="form-control form-control-sm" value="{{ old("items.$index.expiry_date", optional($item->expiry_date)->format('Y-m-d')) }}">
                            </td>
                            <td>
                                <input type="text" name="items[{{ $index }}][rejection_reason]" class="form-control form-control-sm" value="{{ old("items.$index.rejection_reason", $item->rejection_reason) }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="{{ route('admin.inward.show', $grn) }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Save Inward Changes</button>
    </div>
</form>
@endsection
