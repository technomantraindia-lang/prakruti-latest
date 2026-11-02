@extends('admin.layouts.app')
@section('title', 'Settings')
@section('content')
@php
    $discountRules = json_decode($settings['bill_discount_rules'] ?? '[]', true);
    if (!is_array($discountRules) || empty($discountRules)) {
        $discountRules = [[
            'min_amount' => $settings['bill_discount_min_amount'] ?? '',
            'percent' => $settings['bill_discount_percent'] ?? '',
        ]];
    }
@endphp
<h2 class="mb-4">Store Settings</h2>
<div class="card"><div class="card-body">
    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">@csrf @method('PUT')
        <h5 class="mb-3">Business Contact</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Business Email</label>
                <input type="email" name="company_email" class="form-control" value="{{ $settings['company_email'] ?? '' }}" placeholder="support@prakruticorp.org">
                <small class="text-muted">This email is shown in the website footer.</small>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Business Phone Number</label>
                <input type="text" name="company_phone" class="form-control" value="{{ $settings['company_phone'] ?? '' }}" placeholder="Example: 9999999999">
                <small class="text-muted">This phone is shown in the footer and used to verify admin forgot password.</small>
            </div>
            <div class="col-md-12 mb-3"><label class="form-label">Address</label><textarea name="company_address" class="form-control" rows="2">{{ $settings['company_address'] ?? '' }}</textarea></div>
            <div class="col-md-4 mb-3"><label class="form-label">GST Number</label><input type="text" name="gst_number" class="form-control" value="{{ $settings['gst_number'] ?? '' }}"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Currency</label><input type="text" name="currency" class="form-control" value="{{ $settings['currency'] ?? 'INR' }}"></div>
            <div class="col-md-4 mb-3"><label class="form-label">Order Prefix</label><input type="text" name="order_prefix" class="form-control" value="{{ $settings['order_prefix'] ?? 'PRK' }}"></div>
        </div>

        <hr><h5 class="mb-3">Shipping & Billing</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Standard Shipping Charge (₹)</label>
                <input type="number" name="standard_shipping_charge" class="form-control" min="0" step="0.01" value="{{ $settings['standard_shipping_charge'] ?? '50' }}" placeholder="Example: 50">
                <small class="text-muted">This charge applies when the bill is below the free-shipping amount.</small>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Free Shipping Above Bill Amount (₹)</label>
                <input type="number" name="free_shipping_min_amount" class="form-control" min="0" step="0.01" value="{{ $settings['free_shipping_min_amount'] ?? '500' }}" placeholder="Example: 500">
                <small class="text-muted">Example: enter 500 to make shipping free for bills of ₹500 and above.</small>
            </div>
        </div>

        <hr><h5 class="mb-3">Automatic Bill Discount</h5>
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="form-check form-switch">
                    <input type="checkbox" name="bill_discount_enabled" value="1" class="form-check-input" id="billDiscountEnabled" @checked(($settings['bill_discount_enabled'] ?? '0') == '1')>
                    <label class="form-check-label" for="billDiscountEnabled">Enable bill discount</label>
                </div>
            </div>
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="discountRulesTable">
                        <thead>
                            <tr>
                                <th style="width:45%;">Minimum Bill Amount</th>
                                <th style="width:35%;">Discount Percentage</th>
                                <th style="width:20%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($discountRules as $rule)
                                <tr class="discount-rule-row">
                                    <td><input type="number" name="bill_discount_min_amount[]" class="form-control" min="0" step="0.01" value="{{ $rule['min_amount'] ?? '' }}" placeholder="Example: 2000"></td>
                                    <td><input type="number" name="bill_discount_percent[]" class="form-control" min="0" max="100" step="0.01" value="{{ $rule['percent'] ?? '' }}" placeholder="Example: 3"></td>
                                    <td><button type="button" class="btn btn-outline-danger btn-sm remove-discount-rule">Remove</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addDiscountRule">Add More Discount</button>
                <p class="text-muted small mb-0 mt-2">Example: add 2000 = 3%, 5000 = 5%, 10000 = 8%. Checkout will use the highest matching slab.</p>
            </div>
        </div>

        <hr><h5 class="mb-3">Instagram Feed</h5>
        <input type="hidden" name="instagram_posts_submitted" value="1">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Instagram Profile URL</label>
                <input type="url" name="instagram_profile_url" class="form-control" value="{{ $settings['instagram_profile_url'] ?? 'https://www.instagram.com/prakrutiorganics_/' }}" placeholder="https://www.instagram.com/prakrutiorganics_/">
                <small class="text-muted">Link where customers are redirected when clicking Instagram boxes or Follow Us.</small>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Instagram Handle</label>
                <input type="text" name="instagram_handle" class="form-control" value="{{ $settings['instagram_handle'] ?? '@prakrutiorganics_' }}" placeholder="@prakrutiorganics_">
                <small class="text-muted">Handle shown in the Follow Us box.</small>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Custom Instagram Posts &amp; Reels (Optional)</label>
                <p class="text-muted small">Add individual post/reel URLs and image/video links. If empty, the storefront displays default curated posts linked to your Instagram account.</p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="instagramPostsTable">
                        <thead>
                            <tr>
                                <th style="width:35%;">Instagram Post / Reel URL</th>
                                <th style="width:18%;">Type</th>
                                <th style="width:27%;">Media URL (Image / Video)</th>
                                <th style="width:15%;">Caption</th>
                                <th style="width:5%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $instaPosts = json_decode($settings['instagram_posts'] ?? '[]', true) ?: [];
                            @endphp
                            @foreach($instaPosts as $ipost)
                                <tr class="insta-post-row">
                                    <td><input type="url" name="instagram_post_url[]" class="form-control form-control-sm" value="{{ $ipost['url'] ?? '' }}" placeholder="https://www.instagram.com/reel/..."></td>
                                    <td>
                                        <select name="instagram_post_type[]" class="form-select form-select-sm">
                                            <option value="image" @selected(($ipost['type'] ?? '') == 'image')>Image</option>
                                            <option value="video" @selected(($ipost['type'] ?? '') == 'video')>Video / Reel</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="instagram_post_media[]" class="form-control form-control-sm" value="{{ $ipost['media_url'] ?? '' }}" placeholder="Direct image/video URL"></td>
                                    <td><input type="text" name="instagram_post_caption[]" class="form-control form-control-sm" value="{{ $ipost['caption'] ?? '' }}" placeholder="Caption"></td>
                                    <td><button type="button" class="btn btn-outline-danger btn-sm remove-insta-post">✕</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addInstaPost">+ Add Post / Reel</button>
            </div>
        </div>

        <hr><h5 class="mb-3">Admin Account</h5>
        <p class="text-muted small mb-3">Use this section only when you want to change the logged-in admin email or password.</p>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Old Admin Email</label>
                <input type="email" name="admin_current_email" class="form-control @error('admin_current_email') is-invalid @enderror" value="{{ old('admin_current_email') }}" placeholder="{{ auth()->user()?->email }}">
                @error('admin_current_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">New Admin Email</label>
                <input type="email" name="admin_new_email" class="form-control @error('admin_new_email') is-invalid @enderror" value="{{ old('admin_new_email') }}" placeholder="new-admin@example.com">
                @error('admin_new_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label">Old Password</label>
                <div class="input-group">
                    <input type="password" name="admin_current_password" class="form-control @error('admin_current_password') is-invalid @enderror password-eye-input" autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary password-eye-toggle" aria-label="Show password">👁</button>
                    @error('admin_current_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">New Password</label>
                <div class="input-group">
                    <input type="password" name="admin_new_password" class="form-control @error('admin_new_password') is-invalid @enderror password-eye-input" autocomplete="new-password">
                    <button type="button" class="btn btn-outline-secondary password-eye-toggle" aria-label="Show password">👁</button>
                    @error('admin_new_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Confirm New Password</label>
                <div class="input-group">
                    <input type="password" name="admin_new_password_confirmation" class="form-control password-eye-input" autocomplete="new-password">
                    <button type="button" class="btn btn-outline-secondary password-eye-toggle" aria-label="Show password">👁</button>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.querySelector('#discountRulesTable tbody');
    const addButton = document.getElementById('addDiscountRule');

    function bindRemoveButtons() {
        document.querySelectorAll('.remove-discount-rule').forEach(function (button) {
            button.onclick = function () {
                const rows = tableBody.querySelectorAll('.discount-rule-row');
                if (rows.length > 1) {
                    button.closest('tr').remove();
                } else {
                    button.closest('tr').querySelectorAll('input').forEach(function (input) {
                        input.value = '';
                    });
                }
            };
        });
    }

    addButton.addEventListener('click', function () {
        const row = document.createElement('tr');
        row.className = 'discount-rule-row';
        row.innerHTML = `
            <td><input type="number" name="bill_discount_min_amount[]" class="form-control" min="0" step="0.01" placeholder="Example: 5000"></td>
            <td><input type="number" name="bill_discount_percent[]" class="form-control" min="0" max="100" step="0.01" placeholder="Example: 5"></td>
            <td><button type="button" class="btn btn-outline-danger btn-sm remove-discount-rule">Remove</button></td>
        `;
        tableBody.appendChild(row);
        bindRemoveButtons();
    });

    bindRemoveButtons();

    const instaTableBody = document.querySelector('#instagramPostsTable tbody');
    const addInstaButton = document.getElementById('addInstaPost');

    function bindInstaRemoveButtons() {
        document.querySelectorAll('.remove-insta-post').forEach(function (button) {
            button.onclick = function () {
                button.closest('tr').remove();
            };
        });
    }

    if (addInstaButton && instaTableBody) {
        addInstaButton.addEventListener('click', function () {
            const row = document.createElement('tr');
            row.className = 'insta-post-row';
            row.innerHTML = `
                <td><input type="url" name="instagram_post_url[]" class="form-control form-control-sm" placeholder="https://www.instagram.com/reel/..."></td>
                <td>
                    <select name="instagram_post_type[]" class="form-select form-select-sm">
                        <option value="image">Image</option>
                        <option value="video">Video / Reel</option>
                    </select>
                </td>
                <td><input type="text" name="instagram_post_media[]" class="form-control form-control-sm" placeholder="Direct image/video URL"></td>
                <td><input type="text" name="instagram_post_caption[]" class="form-control form-control-sm" placeholder="Caption"></td>
                <td><button type="button" class="btn btn-outline-danger btn-sm remove-insta-post">✕</button></td>
            `;
            instaTableBody.appendChild(row);
            bindInstaRemoveButtons();
        });
        bindInstaRemoveButtons();
    }

    document.querySelectorAll('.password-eye-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = button.parentElement.querySelector('.password-eye-input');
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.textContent = isPassword ? '🙈' : '👁';
            button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    });
});
</script>
@endsection
