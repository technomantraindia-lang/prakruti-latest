@extends('admin.layouts.app')
@section('title', 'Add New Vendor')
@section('content')
<div class="mb-4">
    <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-secondary btn-sm mb-2"><i class="fas fa-arrow-left me-1"></i> Back to Vendors</a>
    <h2>Add New Vendor / Supplier</h2>
</div>

<form action="{{ route('admin.vendors.store') }}" method="POST">
    @csrf
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
                            <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror" value="{{ old('company_name') }}" placeholder="e.g. Prakruti Herbs & Agro Pvt Ltd">
                            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Contact Person Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Rajesh Patel" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="vendor@example.com">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone Number</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="+91 98765 43210">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                            <input type="text" name="gstin" maxlength="15" class="form-control text-uppercase font-monospace @error('gstin') is-invalid @enderror" value="{{ old('gstin') }}" placeholder="24AAACP1234A1Z5">
                            <small class="text-muted">First 2 digits auto-identify State Code for CGST+SGST vs IGST.</small>
                            @error('gstin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">PAN Number (10 Digits)</label>
                            <input type="text" name="pan" maxlength="10" class="form-control text-uppercase font-monospace @error('pan') is-invalid @enderror" value="{{ old('pan') }}" placeholder="AAACP1234A">
                            @error('pan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Registered Office / Warehouse Address</label>
                            <textarea name="address" rows="2" class="form-control" placeholder="Street, Plot No, Industrial Area">{{ old('address') }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">City</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city') }}" placeholder="Vadodara">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">State</label>
                            <input type="text" name="state" class="form-control" value="{{ old('state', 'Gujarat') }}" placeholder="Gujarat">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Pincode</label>
                            <input type="text" name="pincode" class="form-control" value="{{ old('pincode') }}" placeholder="390019">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0"><i class="fas fa-university me-2 text-warning"></i> Bank Details for Settlements</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}" placeholder="HDFC Bank">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Account Number</label>
                            <input type="text" name="bank_account_no" class="form-control font-monospace" value="{{ old('bank_account_no') }}" placeholder="50200012345678">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">IFSC Code</label>
                            <input type="text" name="bank_ifsc" class="form-control text-uppercase font-monospace" value="{{ old('bank_ifsc') }}" placeholder="HDFC0000123">
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
                            <input type="number" name="payment_terms_days" class="form-control" value="{{ old('payment_terms_days', 30) }}" min="0">
                            <span class="input-group-text">Days</span>
                        </div>
                        <small class="text-muted">Used for automatic Purchase Bill due date calculation.</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="isActiveSwitch">Active Vendor</label>
                        </div>
                        <small class="text-muted">Inactive vendors cannot be selected for new Inwards or POs.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Internal Notes</label>
                        <textarea name="notes" rows="3" class="form-control" placeholder="Any delivery agreements, certifications, or notes">{{ old('notes') }}</textarea>
                    </div>

                    <hr>

                    <button type="submit" class="btn btn-primary w-100 py-2"><i class="fas fa-save me-1"></i> Save Vendor</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
