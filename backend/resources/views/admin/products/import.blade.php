@extends('admin.layouts.app')

@section('title', 'Import Products')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h2 class="mb-1">Import Products (Excel)</h2>
        <p class="text-muted mb-0">Upload the Excel template containing only the fields from the Admin Add / Edit Product page.</p>
    </div>
    <a href="{{ route('admin.products.importTemplate') }}" class="btn btn-outline-primary">
        <i class="fas fa-download"></i> Download Excel Template (.xlsx)
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Upload Excel Spreadsheet</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Primary Fields:</strong> <code>product_name</code> and <code>category</code> are required. If <code>product_sku</code> is left empty, a unique SKU is automatically generated.
                </div>

                <form action="{{ route('admin.products.importStore') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Excel / CSV File *</label>
                        <input type="file" name="csv_file" class="form-control @error('csv_file') is-invalid @enderror" accept=".xlsx,.csv,.txt" required>
                        @error('csv_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Supported formats: .xlsx (Excel) or .csv. Keep header row intact. Max size: 10 MB.</div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-file-import"></i> Import Products</button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-secondary ms-2">Cancel</a>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-info-circle text-primary"></i> Multi-Package & Guidelines</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0 text-muted small" style="line-height: 1.8;">
                    <li><strong>Multiple Package Sizes:</strong> For products with multiple sizes (e.g., 250g, 500g, 1kg), create one row per size with the same <code>product_name</code> or <code>product_sku</code>. All sizes will be grouped under one product.</li>
                    <li><strong>Auto-Generated SKUs:</strong> Leave <code>product_sku</code> and <code>package_sku</code> empty to have the system auto-generate standard SKUs.</li>
                    <li><strong>Category Matching:</strong> Enter category by name (e.g. <em>Healthy Seeds</em>), slug, or numeric ID.</li>
                    <li><strong>Default Fallbacks:</strong> Shelf life defaults to <em>12 Months</em>, packaging type to <em>Food Grade Standing Pouch</em>, and storage to <em>Store in dry, airtight container</em> if left empty.</li>
                    <li><strong>Product Photos & Certificates:</strong> Image and report files can be uploaded directly from the Admin Edit Product page after importing.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-table text-success"></i> Excel Columns (27 Fields from Add Product)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 520px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Excel Column Name</th>
                                <th>Add Product Field Equivalent</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <!-- Basic Details -->
                            <tr class="table-secondary"><td colspan="4" class="fw-bold">1. Basic Product Details</td></tr>
                            <tr><td>1</td><td><code>product_name</code></td><td>Product Name *</td><td><span class="badge bg-danger">Required</span></td></tr>
                            <tr><td>2</td><td><code>product_sku</code></td><td>Product SKU (Code)</td><td><span class="badge bg-secondary">Auto if empty</span></td></tr>
                            <tr><td>3</td><td><code>category</code></td><td>Category *</td><td><span class="badge bg-danger">Required</span></td></tr>

                            <!-- Package Size & Regular Price -->
                            <tr class="table-secondary"><td colspan="4" class="fw-bold">2. Package Size & Regular Price</td></tr>
                            <tr><td>4</td><td><code>package_size</code></td><td>Package Size * (e.g. 500g, 1kg)</td><td><span class="badge bg-primary">Recommended</span></td></tr>
                            <tr><td>5</td><td><code>package_sku</code></td><td>Package SKU</td><td><span class="badge bg-secondary">Auto if empty</span></td></tr>
                            <tr><td>6</td><td><code>regular_price</code></td><td>Regular Price *</td><td><span class="badge bg-primary">Recommended</span></td></tr>
                            <tr><td>7</td><td><code>sale_price</code></td><td>Sale Price</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>
                            <tr><td>8</td><td><code>stock</code></td><td>Stock Quantity *</td><td><span class="badge bg-primary">Recommended</span></td></tr>
                            <tr><td>9</td><td><code>package_status</code></td><td>Package Status (active / inactive)</td><td><span class="badge bg-light text-dark">Default: active</span></td></tr>

                            <!-- Descriptions -->
                            <tr class="table-secondary"><td colspan="4" class="fw-bold">3. Descriptions</td></tr>
                            <tr><td>10</td><td><code>short_description</code></td><td>Short Description</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>
                            <tr><td>11</td><td><code>full_description</code></td><td>Full Description</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>

                            <!-- Nutrition -->
                            <tr class="table-secondary"><td colspan="4" class="fw-bold">4. Nutritional Facts & Verification</td></tr>
                            <tr><td>12</td><td><code>nutrition_status</code></td><td>Status of these values (packaging_verified / label_unreviewed / ifct_reference_estimate)</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>
                            <tr><td>13</td><td><code>food_group</code></td><td>Food group for Family Pack</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>
                            <tr><td>14</td><td><code>nutritional_values</code></td><td>Nutritional Values (Text, table lines, or JSON)</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>

                            <!-- Product Info -->
                            <tr class="table-secondary"><td colspan="4" class="fw-bold">5. Product Information</td></tr>
                            <tr><td>15</td><td><code>product_type</code></td><td>Product Type (defaults to category)</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>
                            <tr><td>16</td><td><code>shelf_life</code></td><td>Shelf Life (defaults to 12 Months)</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>
                            <tr><td>17</td><td><code>ingredient</code></td><td>Ingredient (defaults to product name)</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>
                            <tr><td>18</td><td><code>packaging_type</code></td><td>Packaging Type (defaults to Food Grade Standing Pouch)</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>
                            <tr><td>19</td><td><code>storage</code></td><td>Storage (defaults to Store in dry, airtight container)</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>

                            <!-- Storefront Accordions -->
                            <tr class="table-secondary"><td colspan="4" class="fw-bold">6. Storefront Detail Accordions</td></tr>
                            <tr><td>20</td><td><code>what_is_it</code></td><td>1. PRODUCT INFO: What is it? (Description / Overview)</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>
                            <tr><td>21</td><td><code>how_it_works</code></td><td>1. PRODUCT INFO: How it does? (How it works / Key Benefits)</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>
                            <tr><td>22</td><td><code>key_ingredients</code></td><td>2. KEY INGREDIENTS</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>
                            <tr><td>23</td><td><code>how_to_use</code></td><td>3. HOW TO USE</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>
                            <tr><td>24</td><td><code>full_ingredients_list</code></td><td>4. FULL INGREDIENTS LIST</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>
                            <tr><td>25</td><td><code>faqs</code></td><td>6. FAQS (OPTIONAL)</td><td><span class="badge bg-light text-dark">Optional</span></td></tr>

                            <!-- Visibility -->
                            <tr class="table-secondary"><td colspan="4" class="fw-bold">7. Visibility</td></tr>
                            <tr><td>26</td><td><code>status</code></td><td>Status (active / inactive)</td><td><span class="badge bg-light text-dark">Default: active</span></td></tr>
                            <tr><td>27</td><td><code>best_seller</code></td><td>Show in Best Sellers (yes / no)</td><td><span class="badge bg-light text-dark">Default: no</span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
