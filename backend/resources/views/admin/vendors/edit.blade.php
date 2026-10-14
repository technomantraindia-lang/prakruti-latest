@extends('admin.layouts.app')
@section('title', 'Edit Vendor')
@section('content')
<div class="mb-4">
    <a href="{{ route('admin.vendors.show', $vendor) }}" class="btn btn-outline-secondary btn-sm mb-2"><i class="fas fa-arrow-left me-1"></i> Back to Vendor Details</a>
    <h2>Edit Vendor: {{ $vendor->display_name }}</h2>
</div>

<form action="{{ route('admin.vendors.update', $vendor) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-building me-2 text-primary"></i> Business & Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Company / Trade Name</label>
                            <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $vendor->company_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Contact Person Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $vendor->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $vendor->email) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $vendor->phone) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-file-invoice me-2 text-success"></i> GST, Taxation & Address</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">GSTIN (15 Digits)</label>
                            <input type="text" name="gstin" maxlength="15" class="form-control text-uppercase font-monospace" value="{{ old('gstin', $vendor->gstin) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">PAN Number (10 Digits)</label>
                            <input type="text" name="pan" maxlength="10" class="form-control text-uppercase font-monospace" value="{{ old('pan', $vendor->pan) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Registered Office / Warehouse Address</label>
                            <textarea name="address" rows="2" class="form-control">{{ old('address', $vendor->address) }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">City</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city', $vendor->city) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">State</label>
                            <input type="text" name="state" class="form-control" value="{{ old('state', $vendor->state) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Pincode</label>
                            <input type="text" name="pincode" class="form-control" value="{{ old('pincode', $vendor->pincode) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-university me-2 text-warning"></i> Bank Details</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $vendor->bank_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Account Number</label>
                            <input type="text" name="bank_account_no" class="form-control font-monospace" value="{{ old('bank_account_no', $vendor->bank_account_no) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">IFSC Code</label>
                            <input type="text" name="bank_ifsc" class="form-control text-uppercase font-monospace" value="{{ old('bank_ifsc', $vendor->bank_ifsc) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-clock me-2 text-info"></i> Terms & Status</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Terms (Credit Days)</label>
                        <div class="input-group">
                            <input type="number" name="payment_terms_days" class="form-control" value="{{ old('payment_terms_days', $vendor->payment_terms_days) }}" min="0">
                            <span class="input-group-text">Days</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', $vendor->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="isActiveSwitch">Active Vendor</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Internal Notes</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $vendor->notes) }}</textarea>
                    </div>

                    <hr>

                    <button type="submit" class="btn btn-primary w-100 py-2"><i class="fas fa-save me-1"></i> Update Vendor</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
