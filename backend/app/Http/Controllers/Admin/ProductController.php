<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Services\ImageUploadService;
use App\Services\InwardStockImportService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use ZipArchive;

use App\Services\InventoryService;
use App\Services\ProductNutritionService;

class ProductController extends Controller
{
    private const PRODUCT_DETAIL_IMAGE_MIN = 1;
    private const PRODUCT_DETAIL_IMAGE_MAX = 7;

    public function __construct(
        private ImageUploadService $uploader,
        private InventoryService $inventoryService,
        private ProductNutritionService $nutritionService,
        private InwardStockImportService $spreadsheetReader
    ) {}

    public function index(Request $request)
    {
        if ($request->has('reset')) {
            session()->forget('admin_products_filters');
            return redirect()->route('admin.products.index');
        }

        $filterKeys = ['search', 'category_id', 'status', 'low_stock', 'page'];
        $hasFilterParam = $request->hasAny(['search', 'category_id', 'status', 'low_stock']);

        if ($hasFilterParam) {
            $activeFilters = array_filter(
                $request->only($filterKeys),
                fn ($val) => $val !== null && $val !== ''
            );
            if (!empty($activeFilters)) {
                session(['admin_products_filters' => $activeFilters]);
            } else {
                session()->forget('admin_products_filters');
            }
        } elseif (!$request->has('page') && session()->has('admin_products_filters')) {
            $savedFilters = session('admin_products_filters');
            if (!empty($savedFilters)) {
                return redirect()->route('admin.products.index', $savedFilters);
            }
        }

        $lowStockOnly = $request->boolean('low_stock');
        $query = Product::with([
            'category',
            'brand',
            'variations' => fn ($variationQuery) => $variationQuery
                ->where('status', 'active')
                ->with(['batches' => fn ($batchQuery) => $batchQuery
                    ->where('current_qty', '>', 0)
                    ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, expiry_date ASC, id ASC')]),
            'activeBatches' => fn ($batchQuery) => $batchQuery
                ->where('current_qty', '>', 0)
                ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, expiry_date ASC, id ASC'),
        ])->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($lowStockOnly) {
            $matchingProducts = $query->get()->filter(fn (Product $product) => $product->isLowStock())->values();
            $perPage = 15;
            $page = LengthAwarePaginator::resolveCurrentPage();
            $products = new LengthAwarePaginator(
                $matchingProducts->forPage($page, $perPage)->values(),
                $matchingProducts->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $products = $query->paginate(15)->withQueryString();
        }
        $categories = Category::where('status', 'active')->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return view('admin.products.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);
        $packages = $this->normalizePackageRows($data['packages'] ?? []);
        unset($data['packages']);
        $data['product_information'] = $this->normalizeProductInformation($data['product_information'] ?? []);
        unset($data['clinical_report_image']);
        if ($request->hasFile('clinical_report_image')) {
            $data['product_information']['clinical_report_image'] = $this->uploader->upload($request->file('clinical_report_image'), 'product_reports');
        }

        $certificateReports = $this->handleCertificateReports($request);
        if (!empty($certificateReports)) {
            $data['product_information']['certificate_reports'] = $certificateReports;
        }

        $this->applyDefaultPackageToProductData($data, $packages);

        $data['featured'] = $request->boolean('featured');
        $initialStock = (int) ($data['stock_qty'] ?? 0);
        $data['stock_qty'] = 0;

        if (empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['name'], 'products', 'slug');
        }

        if (empty($data['sku'])) {
            $data['sku'] = $this->generateSku($data['name']);
        }

        if (empty($data['brand_id'])) {
            $data['brand_id'] = Brand::where('slug', 'prakruti-organic')->value('id') ?: Brand::where('status', 'active')->value('id');
        }

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploader->upload($request->file('image'), 'products');
        }

        $category = !empty($data['category_id']) ? Category::find($data['category_id']) : null;
        $categoryName = $category?->name;
        $ingredients = $data['product_information']['ingredient'] ?? null;
        $nutriRaw = $request->input('nutritional_info');
        $forcedSource = $request->input('nutrition_source');
        $packagingProof = null;
        $isOcr = false;

        if ($request->hasFile('nutrition_image')) {
            $packagingProof = $this->uploader->upload($request->file('nutrition_image'), 'nutrition_labels');
            $isOcr = true;
        }

        $data['nutritional_info'] = $this->nutritionService->buildStructuredPayload(
            $nutriRaw,
            $data['name'],
            $categoryName,
            $ingredients,
            $forcedSource,
            $isOcr,
            $packagingProof,
            $request->input('food_group') ?: null
        );

        $product = Product::create($data);
        $this->syncPackageVariants($product, $packages);
        $this->saveGallery($product, $request);

        if (empty($product->image) && $product->images()->exists()) {
            $product->update(['image' => $product->images()->first()->image]);
        }

        if ($initialStock > 0) {
            $this->inventoryService->adjustStock($product, $initialStock, 'opening_stock');
        }

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        $product->load([
            'category',
            'subCategory',
            'subSubCategory',
            'brand',
            'images',
            'variations' => fn ($variationQuery) => $variationQuery
                ->where('status', 'active')
                ->with(['batches' => fn ($batchQuery) => $batchQuery
                    ->where('current_qty', '>', 0)
                    ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, expiry_date ASC, id ASC')]),
            'inventoryLogs.user',
        ]);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $product->load([
            'images',
            'variations.attribute',
            'variations.multiAttributeValues.attribute',
            'variations.multiAttributeValues.attributeValue',
        ]);

        return view('admin.products.edit', array_merge(['product' => $product], $this->formData()));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request, $product->id);
        $packages = $this->normalizePackageRows($data['packages'] ?? []);
        $removedPackageIds = $data['removed_packages'] ?? [];
        unset($data['packages']);
        unset($data['removed_packages']);
        $data['product_information'] = $this->normalizeProductInformation($data['product_information'] ?? []);
        unset($data['clinical_report_image']);
        $existingClinicalReport = $product->product_information['clinical_report_image'] ?? null;
        if ($request->hasFile('clinical_report_image')) {
            $this->uploader->delete($existingClinicalReport);
            $data['product_information']['clinical_report_image'] = $this->uploader->upload($request->file('clinical_report_image'), 'product_reports');
        } elseif ($request->boolean('remove_clinical_report_image')) {
            $this->uploader->delete($existingClinicalReport);
        } elseif ($existingClinicalReport) {
            $data['product_information']['clinical_report_image'] = $existingClinicalReport;
        }

        $certificateReports = $this->handleCertificateReports($request, $product);
        if (!empty($certificateReports)) {
            $data['product_information']['certificate_reports'] = $certificateReports;
        } else {
            unset($data['product_information']['certificate_reports']);
        }

        // `unit` is not part of the compact edit form. Keep the saved unit
        // (for example kg or g) instead of replacing it with packet on edit.
        $data['unit'] = $data['unit'] ?? ($product->unit ?: 'packet');
        $this->applyDefaultPackageToProductData($data, $packages);

        $data['featured'] = $request->boolean('featured');
        // The edit form does not have a separate product stock field. When a
        // product has no package rows, keep its current stock instead of
        // accidentally resetting it to zero.
        $targetStock = (int) ($data['stock_qty'] ?? $product->stock_qty);
        unset($data['stock_qty']);

        if (empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['name'], 'products', 'slug', $product->id);
        }

        if (empty($data['sku'])) {
            $data['sku'] = $product->sku ?: $this->generateSku($data['name']);
        }

        if ($request->hasFile('image')) {
            $this->uploader->delete($product->image);
            $data['image'] = $this->uploader->upload($request->file('image'), 'products');
        } elseif ($request->filled('set_main_gallery_id')) {
            $mainGalleryImg = $product->images()->find($request->input('set_main_gallery_id'));
            if ($mainGalleryImg) {
                $data['image'] = $mainGalleryImg->image;
            }
        } elseif ($request->input('remove_main_image') == '1') {
            $data['image'] = null;
        }

        $category = !empty($data['category_id']) ? Category::find($data['category_id']) : null;
        $categoryName = $category?->name;
        $ingredients = $data['product_information']['ingredient'] ?? null;
        $nutriRaw = $request->input('nutritional_info');
        $forcedSource = $request->input('nutrition_source');
        $packagingProof = $product->nutritional_info['packaging_proof'] ?? null;
        $isOcr = false;

        if ($request->hasFile('nutrition_image')) {
            if ($packagingProof) {
                $this->uploader->delete($packagingProof);
            }
            $packagingProof = $this->uploader->upload($request->file('nutrition_image'), 'nutrition_labels');
            $isOcr = true;
        }

        $data['nutritional_info'] = $this->nutritionService->buildStructuredPayload(
            $nutriRaw,
            $data['name'],
            $categoryName,
            $ingredients,
            $forcedSource,
            $isOcr,
            $packagingProof,
            $request->input('food_group') ?: null
        );

        $product->update($data);
        $this->syncPackageVariants($product, $packages, $removedPackageIds);
        $this->removeGalleryImages($product, $request->input('remove_gallery', []));
        $this->reorderGalleryImages($product, $request->input('gallery_order', []));
        $this->saveGallery($product, $request);

        if (empty($product->fresh()->image) && $product->images()->exists()) {
            $product->update(['image' => $product->images()->first()->image]);
        }

        if ($targetStock !== (int) $product->stock_qty) {
            $this->inventoryService->adjustStock($product, $targetStock, 'admin_product_update');
        }

        $returnFilters = array_filter(
            $request->only(['search', 'category_id', 'status', 'low_stock', 'page']),
            fn ($val) => $val !== null && $val !== ''
        );
        if (empty($returnFilters)) {
            $returnFilters = session('admin_products_filters', []);
        }

        return redirect()->route('admin.products.index', $returnFilters)->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $returnFilters = array_filter(
            request()->only(['search', 'category_id', 'status', 'low_stock', 'page']),
            fn ($val) => $val !== null && $val !== ''
        );
        if (empty($returnFilters)) {
            $returnFilters = session('admin_products_filters', []);
        }

        try {
            $mainImage = $product->image;
            $galleryImages = $product->images()->pluck('image')->filter()->all();

            DB::transaction(function () use ($product) {
                $variationIds = $product->variations()->pluck('id');

                if (Schema::hasTable('order_items')) {
                    DB::table('order_items')
                        ->where('product_id', $product->id)
                        ->update(['product_id' => null]);

                    if ($variationIds->isNotEmpty() && Schema::hasColumn('order_items', 'var_id')) {
                        DB::table('order_items')
                            ->whereIn('var_id', $variationIds)
                            ->update(['var_id' => null]);
                    }
                }

                if (Schema::hasTable('return_items') && Schema::hasColumn('return_items', 'product_id')) {
                    DB::table('return_items')
                        ->where('product_id', $product->id)
                        ->update(['product_id' => null]);
                }

                if (Schema::hasTable('inquiries') && Schema::hasColumn('inquiries', 'product_id')) {
                    DB::table('inquiries')
                        ->where('product_id', $product->id)
                        ->update(['product_id' => null]);
                }

                $product->delete();
            });

            $this->uploader->delete($mainImage);
            foreach ($galleryImages as $image) {
                $this->uploader->delete($image);
            }
        } catch (QueryException) {
            return redirect()
                ->route('admin.products.index', $returnFilters)
                ->with('error', 'This product could not be deleted because it is linked to protected records. Please remove linked records and try again.');
        }

        return redirect()->route('admin.products.index', $returnFilters)->with('success', 'Product deleted successfully.');
    }

    public function bulkCreate()
    {
        return view('admin.products.bulk-create', $this->formData());
    }

    public function bulkStore(Request $request)
    {
        $request->validate(['products' => 'required|array|min:1']);

        $count = 0;
        DB::transaction(function () use ($request, &$count) {
            foreach ($request->products as $i => $row) {
                if (empty($row['name']) || empty($row['sku'])) {
                    continue;
                }

                $initialStock = max((int) ($row['stock_qty'] ?? 0), 0);
                $slug = $row['slug'] ?? Str::slug($row['name']);
                $data = [
                    'name' => $row['name'],
                    'slug' => $slug,
                    'sku' => $row['sku'],
                    'category_id' => $row['category_id'],
                    'price' => $row['price'] ?? 0,
                    'sale_price' => $row['sale_price'] ?? null,
                    'stock_qty' => 0,
                    'unit' => $row['unit'] ?? 'pcs',
                    'status' => $row['status'] ?? 'active',
                    'featured' => ! empty($row['featured']),
                ];

                $product = Product::create($data);
                if ($initialStock > 0) {
                    $this->inventoryService->adjustStock($product, $initialStock, 'opening_stock');
                }
                $count++;
            }
        });

        return redirect()->route('admin.products.index')->with('success', "{$count} products added successfully.");
    }

    public function importForm()
    {
        return view('admin.products.import');
    }

    public function downloadImportTemplate()
    {
        $path = $this->ensureProductImportTemplateExists();
        abort_unless(is_file($path), 404, 'Product import template is not available.');

        return response()->download($path, 'prakruti-products-import-template.xlsx');
    }

    public function importStore(Request $request)
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt,xlsx|max:10240']);

        try {
            $rows = $this->spreadsheetReader->readSpreadsheetRows($request->file('csv_file'));
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'The product spreadsheet could not be opened: ' . $e->getMessage());
        }

        if (empty($rows)) {
            return back()->withInput()->with('error', 'The product spreadsheet has no data rows.');
        }

        $header = array_keys($rows[0]['data']);

        $hasName = in_array('product_name', $header, true) || in_array('name', $header, true);
        $hasCategory = in_array('category', $header, true) || in_array('category_name', $header, true);

        if (! $hasName || ! $hasCategory) {
            return back()->withInput()->with('error', 'Spreadsheet is missing required columns: product_name and category.');
        }

        $productGroups = [];
        foreach ($rows as $rowRecord) {
            $data = $rowRecord['data'];
            $name = trim((string) ($data['product_name'] ?? $data['name'] ?? ''));
            $sku = trim((string) ($data['product_sku'] ?? $data['sku'] ?? ''));

            if ($name === '' && $sku === '') {
                continue;
            }

            $groupKey = $sku !== '' ? 'sku_' . Str::lower($sku) : 'name_' . Str::lower($name);
            $productGroups[$groupKey][] = $rowRecord;
        }

        if (empty($productGroups)) {
            return back()->withInput()->with('error', 'No valid product rows found in the spreadsheet.');
        }

        $count = 0;
        $skipped = [];

        DB::transaction(function () use ($productGroups, &$count, &$skipped) {
            foreach ($productGroups as $groupKey => $groupRows) {
                $firstRecord = $groupRows[0];
                $rowNumber = $firstRecord['line'];
                $firstData = $firstRecord['data'];

                $name = trim((string) ($firstData['product_name'] ?? $firstData['name'] ?? ''));
                if ($name === '') {
                    $skipped[] = "Row {$rowNumber}: product_name is required.";
                    continue;
                }

                $categoryInput = trim((string) ($firstData['category'] ?? $firstData['category_name'] ?? ''));
                $category = $this->resolveImportCategory($categoryInput);
                if (! $category) {
                    $skipped[] = "Row {$rowNumber}: category '{$categoryInput}' was not found.";
                    continue;
                }

                $sku = trim((string) ($firstData['product_sku'] ?? $firstData['sku'] ?? ''));
                $existing = $sku !== '' ? Product::where('sku', $sku)->first() : null;
                if (! $existing) {
                    $existing = Product::where('name', $name)->first();
                }

                if ($sku === '') {
                    $sku = $existing ? $existing->sku : $this->generateSku($name);
                }

                $packages = [];
                foreach ($groupRows as $gRow) {
                    $extracted = $this->packagesFromCsv($gRow['data']);
                    foreach ($extracted as $pkg) {
                        $packages[] = $pkg;
                    }
                }

                if (empty($packages)) {
                    $defaultPrice = $this->csvNumber($firstData['mrp'] ?? $firstData['regular_price'] ?? $firstData['price'] ?? '', 0);
                    $defaultSale = $this->csvNumber($firstData['sale_price'] ?? '', null);
                    $defaultStock = $this->csvInteger($firstData['stock'] ?? $firstData['stock_qty'] ?? '', 0);
                    $packages[] = [
                        'id' => null,
                        'label' => 'Standard',
                        'sku' => $sku . '-STD',
                        'price' => $defaultPrice,
                        'sale_price' => $defaultSale,
                        'stock_qty' => $defaultStock,
                        'status' => 'active',
                    ];
                }

                $targetStock = array_sum(array_column($packages, 'stock_qty'));

                $shortDesc = trim((string) ($firstData['short_description'] ?? $firstData['short_desc'] ?? '')) ?: null;
                $description = trim((string) ($firstData['full_description'] ?? $firstData['description'] ?? '')) ?: null;

                $infoFields = [
                    'product_type',
                    'shelf_life',
                    'ingredient',
                    'packaging_type',
                    'storage',
                    'what_is_it',
                    'how_it_works',
                    'key_ingredients',
                    'how_to_use',
                    'full_ingredients_list',
                    'faqs',
                ];

                $productInfo = $existing ? ($existing->product_information ?? []) : [];
                foreach ($infoFields as $f) {
                    $val = trim((string) ($firstData[$f] ?? ''));
                    if ($val !== '') {
                        $productInfo[$f] = $val;
                    }
                }

                if (empty($productInfo['product_type'])) {
                    $productInfo['product_type'] = $category->name;
                }
                if (empty($productInfo['shelf_life'])) {
                    $productInfo['shelf_life'] = '12 Months';
                }
                if (empty($productInfo['ingredient'])) {
                    $productInfo['ingredient'] = $name;
                }
                if (empty($productInfo['packaging_type'])) {
                    $productInfo['packaging_type'] = 'Food Grade Standing Pouch';
                }
                if (empty($productInfo['storage'])) {
                    $productInfo['storage'] = 'Store in dry, airtight container';
                }

                $nutriRaw = $firstData['nutritional_values'] ?? $firstData['nutritional_info'] ?? null;
                $nutritionStatus = trim((string) ($firstData['nutrition_status'] ?? $firstData['nutrition_source'] ?? 'label_unreviewed'));
                if (! in_array($nutritionStatus, ['label_unreviewed', 'packaging_verified', 'ifct_reference_estimate'], true)) {
                    $nutritionStatus = 'label_unreviewed';
                }
                $foodGroup = trim((string) ($firstData['food_group'] ?? '')) ?: null;

                $nutritionalPayload = $this->nutritionService->buildStructuredPayload(
                    $nutriRaw,
                    $name,
                    $category->name,
                    $productInfo['ingredient'] ?? null,
                    $nutritionStatus,
                    false,
                    $existing?->nutritional_info['packaging_proof'] ?? null,
                    $foodGroup
                );

                $status = in_array(Str::lower(trim((string) ($firstData['status'] ?? 'active'))), ['active', 'inactive'], true)
                    ? Str::lower(trim((string) ($firstData['status'] ?? 'active')))
                    : 'active';
                $featured = $this->csvBoolean($firstData['best_seller'] ?? $firstData['featured'] ?? false);

                $slug = $this->uniqueSlug($name, 'products', 'slug', $existing?->id);

                $firstActivePkg = collect($packages)->firstWhere('status', 'active') ?: ($packages[0] ?? null);
                $cardPrice = $firstActivePkg ? $firstActivePkg['price'] : 0;
                $cardSalePrice = $firstActivePkg ? $firstActivePkg['sale_price'] : null;

                $payload = [
                    'name' => $name,
                    'slug' => $slug,
                    'sku' => $sku,
                    'category_id' => $category->id,
                    'price' => $cardPrice,
                    'sale_price' => $cardSalePrice,
                    'stock_qty' => 0,
                    'low_stock_qty' => $existing ? $existing->low_stock_qty : 5,
                    'unit' => $existing ? ($existing->unit ?: 'packet') : 'packet',
                    'min_order_qty' => $existing ? $existing->min_order_qty : 1,
                    'short_desc' => $shortDesc ?: ($existing ? $existing->short_desc : null),
                    'description' => $description ?: ($existing ? $existing->description : null),
                    'nutritional_info' => $nutritionalPayload,
                    'product_information' => $productInfo,
                    'status' => $status,
                    'featured' => $featured,
                ];

                if ($existing) {
                    $existing->update($payload);
                    $product = $existing->fresh();
                } else {
                    $product = Product::create($payload);
                }

                $this->syncPackageVariants($product, $packages);
                $this->inventoryService->adjustStock($product, $targetStock, 'csv_import_stock');
                $count++;
            }
        });

        $message = "{$count} product(s) imported successfully.";
        if (! empty($skipped)) {
            $message .= ' ' . count($skipped) . ' item(s) skipped.';
        }

        return redirect()->route('admin.products.index')
            ->with('success', $message)
            ->with('import_errors', array_slice($skipped, 0, 10));
    }

    private function resolveImportCategory(string $value): ?Category
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return is_numeric($value)
            ? Category::find((int) $value)
            : Category::whereRaw('LOWER(name) = ?', [Str::lower($value)])
                ->orWhere('slug', Str::slug($value))
                ->first();
    }

    private function resolveImportBrand(string $value): ?Brand
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return Brand::whereRaw('LOWER(name) = ?', [Str::lower($value)])
            ->orWhere('slug', Str::slug($value))
            ->first();
    }

    private function csvNumber($value, $default = null)
    {
        $value = trim((string) $value);
        return $value === '' ? $default : (is_numeric($value) ? (float) $value : $default);
    }

    private function csvInteger($value, int $default = 0): int
    {
        $value = trim((string) $value);
        return $value === '' || ! is_numeric($value) ? $default : max((int) $value, 0);
    }

    private function csvBoolean($value): bool
    {
        return in_array(Str::lower(trim((string) $value)), ['1', 'true', 'yes', 'y'], true);
    }

    private function packagesFromCsv(array $data, $defaultPrice = 0, $defaultSalePrice = null, int $defaultStock = 0): array
    {
        $packages = [];

        $label = trim((string) ($data['package_size'] ?? $data['package_label'] ?? $data['packaging'] ?? ''));
        if ($label !== '') {
            $packages[] = [
                'id' => null,
                'label' => $label,
                'sku' => trim((string) ($data['package_sku'] ?? '')) ?: null,
                'price' => $this->csvNumber($data['regular_price'] ?? $data['mrp'] ?? $data['package_price'] ?? $data['price'] ?? '', $defaultPrice),
                'sale_price' => $this->csvNumber($data['sale_price'] ?? $data['package_sale_price'] ?? '', $defaultSalePrice),
                'stock_qty' => $this->csvInteger($data['stock'] ?? $data['stock_qty'] ?? $data['package_stock'] ?? '', $defaultStock),
                'status' => in_array(Str::lower(trim((string) ($data['package_status'] ?? $data['status'] ?? 'active'))), ['active', 'inactive'], true)
                    ? Str::lower(trim((string) ($data['package_status'] ?? $data['status'] ?? 'active')))
                    : 'active',
            ];
        }

        for ($index = 1; $index <= 10; $index++) {
            $pkgLabel = trim((string) ($data["package_{$index}_size"] ?? $data["package_{$index}_label"] ?? ''));
            if ($pkgLabel === '') {
                continue;
            }
            if ($index === 1 && $label !== '' && $pkgLabel === $label) {
                continue;
            }

            $packages[] = [
                'id' => null,
                'label' => $pkgLabel,
                'sku' => trim((string) ($data["package_{$index}_sku"] ?? '')) ?: null,
                'price' => $this->csvNumber($data["package_{$index}_regular_price"] ?? $data["package_{$index}_mrp"] ?? $data["package_{$index}_price"] ?? '', $defaultPrice),
                'sale_price' => $this->csvNumber($data["package_{$index}_sale_price"] ?? '', $defaultSalePrice),
                'stock_qty' => $this->csvInteger($data["package_{$index}_stock"] ?? $data["package_{$index}_stock_qty"] ?? '', $defaultStock),
                'status' => in_array(Str::lower(trim((string) ($data["package_{$index}_status"] ?? 'active'))), ['active', 'inactive'], true)
                    ? Str::lower(trim((string) ($data["package_{$index}_status"] ?? 'active')))
                    : 'active',
            ];
        }

        return $packages;
    }

    public function ensureProductImportTemplateExists(): string
    {
        $path = storage_path('app/templates/products-import-template.xlsx');
        if (! is_file($path)) {
            $this->generateProductImportTemplateFile($path);
        }
        return $path;
    }

    public function generateProductImportTemplateFile(?string $targetPath = null): string
    {
        $targetPath = $targetPath ?: storage_path('app/templates/products-import-template.xlsx');
        $dir = dirname($targetPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $headers = [
            'product_name',
            'product_sku',
            'category',
            'package_size',
            'package_sku',
            'regular_price',
            'sale_price',
            'stock',
            'package_status',
            'short_description',
            'full_description',
            'nutrition_status',
            'food_group',
            'nutritional_values',
            'product_type',
            'shelf_life',
            'ingredient',
            'packaging_type',
            'storage',
            'what_is_it',
            'how_it_works',
            'key_ingredients',
            'how_to_use',
            'full_ingredients_list',
            'faqs',
            'status',
            'best_seller',
        ];

        $sampleCat1 = Category::first();
        $catName1 = $sampleCat1 ? $sampleCat1->name : 'Oils & Ghee';

        $sampleCat2 = Category::skip(1)->first() ?: $sampleCat1;
        $catName2 = $sampleCat2 ? $sampleCat2->name : 'Healthy Seeds';

        $sampleRows = [
            [
                'product_name' => 'A2 Gir Cow Ghee Bilona',
                'product_sku' => 'PRK-GHEE-001',
                'category' => $catName1,
                'package_size' => '500g',
                'package_sku' => 'PRK-GHEE-500',
                'regular_price' => '750.00',
                'sale_price' => '675.00',
                'stock' => '25',
                'package_status' => 'active',
                'short_description' => 'Pure traditional bilona churned A2 cow ghee.',
                'full_description' => 'Prepared using traditional bilona method. Rich in aroma, nutrition, and healthy fats.',
                'nutrition_status' => 'packaging_verified',
                'food_group' => 'oils',
                'nutritional_values' => "Energy: 897 kcal\nTotal Fat: 99.7g\nProtein: 0g\nCarbohydrates: 0g\nServing Size: 10g",
                'product_type' => 'Desi Ghee',
                'shelf_life' => '12 Months',
                'ingredient' => 'Gir Cow Milk Fat',
                'packaging_type' => 'Glass Jar',
                'storage' => 'Store in cool, dry place away from direct sunlight',
                'what_is_it' => 'Pure traditional bilona churned A2 cow ghee.',
                'how_it_works' => 'Aids digestion, boosts vitality, and provides essential fatty acids.',
                'key_ingredients' => 'Pure Gir Cow Whole Milk',
                'how_to_use' => 'Add to warm dal, rotis, rice, or consume with warm milk.',
                'full_ingredients_list' => '100% Pure A2 Gir Cow Ghee.',
                'faqs' => 'Q: Is this made from curd? A: Yes, traditional bilona process.',
                'status' => 'active',
                'best_seller' => 'yes',
            ],
            [
                'product_name' => 'A2 Gir Cow Ghee Bilona',
                'product_sku' => 'PRK-GHEE-001',
                'category' => $catName1,
                'package_size' => '1kg',
                'package_sku' => 'PRK-GHEE-1KG',
                'regular_price' => '1450.00',
                'sale_price' => '1300.00',
                'stock' => '15',
                'package_status' => 'active',
                'short_description' => 'Pure traditional bilona churned A2 cow ghee.',
                'full_description' => 'Prepared using traditional bilona method. Rich in aroma, nutrition, and healthy fats.',
                'nutrition_status' => 'packaging_verified',
                'food_group' => 'oils',
                'nutritional_values' => "Energy: 897 kcal\nTotal Fat: 99.7g\nProtein: 0g\nCarbohydrates: 0g\nServing Size: 10g",
                'product_type' => 'Desi Ghee',
                'shelf_life' => '12 Months',
                'ingredient' => 'Gir Cow Milk Fat',
                'packaging_type' => 'Glass Jar',
                'storage' => 'Store in cool, dry place away from direct sunlight',
                'what_is_it' => 'Pure traditional bilona churned A2 cow ghee.',
                'how_it_works' => 'Aids digestion, boosts vitality, and provides essential fatty acids.',
                'key_ingredients' => 'Pure Gir Cow Whole Milk',
                'how_to_use' => 'Add to warm dal, rotis, rice, or consume with warm milk.',
                'full_ingredients_list' => '100% Pure A2 Gir Cow Ghee.',
                'faqs' => 'Q: Is this made from curd? A: Yes, traditional bilona process.',
                'status' => 'active',
                'best_seller' => 'yes',
            ],
            [
                'product_name' => 'Organic Chia Seeds',
                'product_sku' => 'PRK-CHIA-001',
                'category' => $catName2,
                'package_size' => '250g',
                'package_sku' => 'PRK-CHIA-250',
                'regular_price' => '220.00',
                'sale_price' => '195.00',
                'stock' => '40',
                'package_status' => 'active',
                'short_description' => 'Raw premium organic chia seeds high in Omega-3.',
                'full_description' => 'Raw organic chia seeds rich in fiber, protein, and Omega-3.',
                'nutrition_status' => 'packaging_verified',
                'food_group' => 'snacks',
                'nutritional_values' => "Energy: 486 kcal\nProtein: 16.5g\nCarbohydrates: 42.1g\nTotal Fat: 30.7g\nDietary Fibre: 34.4g\nServing Size: 30g",
                'product_type' => 'Superfood Seeds',
                'shelf_life' => '12 Months',
                'ingredient' => 'Raw Organic Chia Seeds',
                'packaging_type' => 'Food Grade Standing Pouch',
                'storage' => 'Store in cool, dry place in airtight container',
                'what_is_it' => 'Raw unprocessed black chia seeds.',
                'how_it_works' => 'Promotes gut wellness, energy, and balanced nutrition.',
                'key_ingredients' => '100% Organic Raw Chia Seeds',
                'how_to_use' => 'Soak 1 tbsp in water or smoothies for 15 minutes before use.',
                'full_ingredients_list' => 'Pure Organic Chia Seeds.',
                'faqs' => 'Q: Can I eat them raw? A: Yes, or pre-soaked in water.',
                'status' => 'active',
                'best_seller' => 'yes',
            ],
        ];

        $cols = count($headers);
        $getCol = function ($n) {
            $name = '';
            while ($n > 0) {
                $rem = ($n - 1) % 26;
                $name = chr(65 + $rem) . $name;
                $n = (int)(($n - $rem) / 26);
            }
            return $name;
        };

        $lastColLetter = $getCol($cols);

        $tableXml = '<?xml version="1.0" encoding="utf-8"?><x:table id="1" name="ProductImportTemplate" displayName="ProductImportTemplate" ref="A1:' . $lastColLetter . (count($sampleRows) + 1) . '" headerRowCount="1" totalsRowCount="0" totalsRowShown="0" xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><x:tableColumns count="' . $cols . '">';
        foreach ($headers as $idx => $h) {
            $tableXml .= '<x:tableColumn id="' . ($idx + 1) . '" name="' . htmlspecialchars($h, ENT_QUOTES | ENT_XML1) . '" />';
        }
        $tableXml .= '</x:tableColumns><x:tableStyleInfo name="TableStyleMedium2" showFirstColumn="0" showLastColumn="0" showRowStripes="1" showColumnStripes="0" /></x:table>';

        $sheetXml = '<?xml version="1.0" encoding="utf-8"?><x:worksheet xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $sheetXml .= '<x:sheetViews><x:sheetView showGridLines="1" workbookViewId="0"><x:pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen" /></x:sheetView></x:sheetViews>';
        $sheetXml .= '<x:sheetFormatPr defaultRowHeight="18" />';
        $sheetXml .= '<x:cols>';
        for ($i = 1; $i <= $cols; $i++) {
            $sheetXml .= '<x:col min="' . $i . '" max="' . $i . '" width="24" hidden="0" customWidth="1" />';
        }
        $sheetXml .= '</x:cols>';
        $sheetXml .= '<x:sheetData>';

        $sheetXml .= '<x:row r="1" ht="34" customHeight="1">';
        foreach ($headers as $idx => $h) {
            $colLetter = $getCol($idx + 1);
            $sheetXml .= '<x:c r="' . $colLetter . '1" s="6" t="str"><x:v>' . htmlspecialchars($h, ENT_QUOTES | ENT_XML1) . '</x:v></x:c>';
        }
        $sheetXml .= '</x:row>';

        foreach ($sampleRows as $rowIdx => $row) {
            $rNum = $rowIdx + 2;
            $sheetXml .= '<x:row r="' . $rNum . '" ht="24" customHeight="1">';
            foreach ($headers as $colIdx => $h) {
                $colLetter = $getCol($colIdx + 1);
                $val = $row[$h] ?? '';
                $sheetXml .= '<x:c r="' . $colLetter . $rNum . '" s="9" t="str"><x:v>' . htmlspecialchars($val, ENT_QUOTES | ENT_XML1) . '</x:v></x:c>';
            }
            $sheetXml .= '</x:row>';
        }
        $sheetXml .= '</x:sheetData>';
        $sheetXml .= '<x:pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3" />';
        $sheetXml .= '<x:tableParts count="1"><x:tablePart r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" /></x:tableParts>';
        $sheetXml .= '</x:worksheet>';

        $sheetRelsXml = '<?xml version="1.0" encoding="utf-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/table" Target="../tables/table1.xml" /></Relationships>';
        $workbookXml = '<?xml version="1.0" encoding="utf-8"?><x:workbook xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><x:sheets><x:sheet name="Products" sheetId="1" state="visible" r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" /></x:sheets></x:workbook>';
        $workbookRelsXml = '<?xml version="1.0" encoding="utf-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml" /><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml" /><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml" /></Relationships>';
        $contentTypesXml = '<?xml version="1.0" encoding="utf-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml" /><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml" /><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml" /><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml" /><Override PartName="/xl/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml" /><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml" /><Override PartName="/xl/tables/table1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.table+xml" /></Types>';
        $rootRelsXml = '<?xml version="1.0" encoding="utf-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml" /></Relationships>';

        $stylesXml = '<?xml version="1.0" encoding="utf-8"?><x:styleSheet xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><x:fonts count="3"><x:font><x:sz val="11" /><x:name val="Arial" /></x:font><x:font><x:b /><x:sz val="10" /><x:color rgb="FFFFFFFF" /><x:name val="Arial" /></x:font><x:font><x:sz val="10" /><x:color rgb="FF1F2937" /><x:name val="Arial" /></x:font></x:fonts><x:fills count="3"><x:fill><x:patternFill patternType="none" /></x:fill><x:fill><x:patternFill patternType="gray125" /></x:fill><x:fill><x:patternFill patternType="solid"><x:fgColor rgb="FF1F5D36" /></x:patternFill></x:fill></x:fills><x:borders count="3"><x:border><x:left /><x:right /><x:top /><x:bottom /><x:diagonal /></x:border><x:border><x:left style="thin"><x:color rgb="FFFFFFFF" /></x:left><x:right style="thin"><x:color rgb="FFFFFFFF" /></x:right><x:top style="thin"><x:color rgb="FFFFFFFF" /></x:top><x:bottom style="thin"><x:color rgb="FFFFFFFF" /></x:bottom><x:diagonal /></x:border><x:border><x:left style="thin"><x:color rgb="FFD9E4DA" /></x:left><x:right style="thin"><x:color rgb="FFD9E4DA" /></x:right><x:top style="thin"><x:color rgb="FFD9E4DA" /></x:top><x:bottom style="thin"><x:color rgb="FFD9E4DA" /></x:bottom><x:diagonal /></x:border></x:borders><x:cellStyleXfs count="1"><x:xf numFmtId="0" fontId="0" fillId="0" borderId="0" /></x:cellStyleXfs><x:cellXfs count="10"><x:xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" /><x:xf numFmtId="0" fontId="0" fillId="2" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" /><x:xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" /><x:xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" /><x:xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><x:alignment wrapText="1" /></x:xf><x:xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><x:alignment horizontal="center" wrapText="1" /></x:xf><x:xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><x:alignment horizontal="center" vertical="center" wrapText="1" /></x:xf><x:xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" /><x:xf numFmtId="0" fontId="2" fillId="0" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" /><x:xf numFmtId="0" fontId="2" fillId="0" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><x:alignment vertical="center" /></x:xf></x:cellXfs><x:cellStyles count="1"><x:cellStyle name="Normal" xfId="0" /></x:cellStyles></x:styleSheet>';
        $themeXml = '<?xml version="1.0" encoding="utf-8"?><a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Office Theme"><a:themeElements><a:clrScheme name="Office"><a:dk1><a:sysClr val="windowText" lastClr="000000" /></a:dk1><a:lt1><a:sysClr val="window" lastClr="FFFFFF" /></a:lt1><a:dk2><a:srgbClr val="1F497D" /></a:dk2><a:lt2><a:srgbClr val="EEECE1" /></a:lt2><a:accent1><a:srgbClr val="1F5D36" /></a:accent1><a:accent2><a:srgbClr val="4BACC6" /></a:accent2><a:accent3><a:srgbClr val="F79646" /></a:accent3><a:accent4><a:srgbClr val="8064A2" /></a:accent4><a:accent5><a:srgbClr val="4BACC6" /></a:accent5><a:accent6><a:srgbClr val="F79646" /></a:accent6><a:hlink><a:srgbClr val="0000FF" /></a:hlink><a:folHlink><a:srgbClr val="800080" /></a:folHlink></a:clrScheme><a:fontScheme name="Office"><a:majorFont><a:latin typeface="Arial" /></a:majorFont><a:minorFont><a:latin typeface="Arial" /></a:minorFont></a:fontScheme><a:fmtScheme name="Office"><a:fillStyleLst><a:solidFill><a:schemeClr val="phClr" /></a:solidFill></a:fillStyleLst><a:lnStyleLst><a:ln w="9525" cap="flat" cmpd="s" algn="ctr"><a:solidFill><a:schemeClr val="phClr" /></a:solidFill></a:ln></a:lnStyleLst><a:effectStyleLst><a:effectStyle><a:effectLst /></a:effectStyle></a:effectStyleLst><a:bgFillStyleLst><a:solidFill><a:schemeClr val="phClr" /></a:solidFill></a:bgFillStyleLst></a:fmtScheme></a:themeElements></a:theme>';

        @unlink($targetPath);

        $zip = new ZipArchive();
        if ($zip->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('[Content_Types].xml', $contentTypesXml);
            $zip->addFromString('_rels/.rels', $rootRelsXml);
            $zip->addFromString('xl/workbook.xml', $workbookXml);
            $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);
            $zip->addFromString('xl/styles.xml', $stylesXml);
            $zip->addFromString('xl/theme/theme1.xml', $themeXml);
            $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
            $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $sheetRelsXml);
            $zip->addFromString('xl/tables/table1.xml', $tableXml);
            $zip->close();
        }

        return $targetPath;
    }

    public function toggleStatus(Product $product)
    {
        $product->update(['status' => $product->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', 'Product status updated.');
    }

    public function toggleFeatured(Product $product)
    {
        $product->update(['featured' => ! $product->featured]);

        return back()->with('success', 'Best seller status updated.');
    }

    private function validateProduct(Request $request, ?int $id = null): array
    {
        $skuRule = 'nullable|string|max:100|unique:products,sku' . ($id ? ",{$id}" : '');
        $slugRule = 'nullable|string|max:255|unique:products,slug' . ($id ? ",{$id}" : '');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => $slugRule,
            'sku' => $skuRule,
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'hsn_code' => 'nullable|string|max:50',
            'stock_qty' => 'nullable|integer|min:0',
            'low_stock_qty' => 'nullable|integer|min:0',
            'unit' => 'nullable|string|max:20',
            'min_order_qty' => 'nullable|integer|min:1',
            'weight' => 'nullable|numeric|min:0',
            // Product creation needs one package. During an edit, package
            // rows are optional so custom/multi-attribute variations can be
            // preserved even when this screen does not manage them.
            'packages' => $id ? 'nullable|array' : 'required|array|min:1',
            'packages.*.id' => 'nullable|integer|exists:product_variations,id',
            'packages.*.sku' => 'nullable|string|max:100',
            'packages.*.label' => 'required|string|max:100|distinct',
            'packages.*.price' => 'required|numeric|min:0',
            'packages.*.sale_price' => 'nullable|numeric|min:0',
            'packages.*.stock_qty' => 'required|integer|min:0',
            'packages.*.status' => 'nullable|in:active,inactive',
            'removed_packages' => 'nullable|array',
            'removed_packages.*' => 'integer|exists:product_variations,id',
            'short_desc' => 'nullable|string',
            'description' => 'nullable|string',
            'nutritional_info' => 'nullable',
            'nutrition_source' => 'nullable|string|in:packaging_verified,label_unreviewed,ifct_reference_estimate,needs_review,unverified',
            'food_group' => 'nullable|string|in:pulses_legumes,grains_cereals,nuts_seeds,cooking_oils,ghee,sweeteners,spices,salt,other',
            'nutrition_image' => 'nullable|image|max:10240',
            'clinical_report_image' => 'nullable|image|max:12288',
            'remove_clinical_report_image' => 'nullable|boolean',
            'product_information' => 'nullable|array',
            'product_information.product_type' => 'nullable|string|max:255',
            'product_information.shelf_life' => 'nullable|string|max:255',
            'product_information.ingredient' => 'nullable|string|max:255',
            'product_information.packaging_type' => 'nullable|string|max:255',
            'product_information.storage' => 'nullable|string|max:255',
            'product_information.what_is_it' => 'nullable|string|max:10000',
            'product_information.how_it_works' => 'nullable|string|max:10000',
            'product_information.key_ingredients' => 'nullable|string|max:5000',
            'product_information.how_to_use' => 'nullable|string|max:5000',
            'product_information.full_ingredients_list' => 'nullable|string|max:10000',
            'product_information.clinically_tested' => 'nullable|string|max:5000',
            'product_information.faqs' => 'nullable|string|max:10000',
            'certificate_reports' => 'nullable|array',
            'certificate_reports.*.title' => 'nullable|string|max:255',
            'certificate_reports.*.short_desc' => 'nullable|string|max:255',
            'certificate_reports.*.image' => 'nullable|string|max:500',
            'certificate_reports.*.remove' => 'nullable',
            'certificate_reports_file' => 'nullable|array',
            'certificate_reports_file.*' => 'nullable|image|max:12288',
            'seo_title' => 'nullable|string|max:255',
            'seo_desc' => 'nullable|string',
            'seo_keywords' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'featured' => 'nullable|boolean',
            'image' => 'nullable|image',
            'remove_main_image' => 'nullable|in:0,1',
            'set_main_gallery_id' => 'nullable|integer',
            'gallery_order' => 'nullable',
            'gallery' => 'nullable|array|max:' . self::PRODUCT_DETAIL_IMAGE_MAX,
            'gallery.*' => 'nullable|image',
            'remove_gallery' => 'nullable|array',
            'remove_gallery.*' => 'nullable|integer|exists:product_images,id',
        ]);

        $validator->after(function ($validator) use ($request, $id) {
            if ($id) {
                $productVariationIds = ProductVariation::where('product_id', $id)->pluck('id');
                $submittedVariationIds = collect($request->input('packages', []))
                    ->pluck('id')
                    ->filter()
                    ->map(fn ($value) => (int) $value);
                $removedVariationIds = collect($request->input('removed_packages', []))
                    ->filter()
                    ->map(fn ($value) => (int) $value);

                foreach ($submittedVariationIds->merge($removedVariationIds)->unique() as $variationId) {
                    if (! $productVariationIds->contains($variationId)) {
                        $validator->errors()->add('packages', 'One or more selected package options do not belong to this product. Please reload and try again.');
                        break;
                    }
                }
            }

            $newGalleryCount = collect($request->file('gallery', []))->filter()->count();
            $removeGalleryIds = collect($request->input('remove_gallery', []))
                ->filter()
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values();

            $existingGalleryCount = $id ? ProductImage::where('product_id', $id)->count() : 0;
            $validRemoveCount = ($id && $removeGalleryIds->isNotEmpty())
                ? ProductImage::where('product_id', $id)->whereIn('id', $removeGalleryIds)->count()
                : 0;

            $totalGalleryCount = $existingGalleryCount - $validRemoveCount + $newGalleryCount;

            if ($totalGalleryCount < self::PRODUCT_DETAIL_IMAGE_MIN) {
                $validator->errors()->add('gallery', 'Please add at least 1 product detail photo.');
            }

            if ($totalGalleryCount > self::PRODUCT_DETAIL_IMAGE_MAX) {
                $validator->errors()->add('gallery', 'You can keep a maximum of 7 product detail photos. Please remove extra photos before saving.');
            }
        });

        return $validator->validate();
    }

    public function reorderGallery(Request $request, Product $product)
    {
        $order = $request->input('order', []);
        $this->reorderGalleryImages($product, $order);

        return response()->json([
            'success' => true,
            'message' => 'Gallery photo order saved successfully.',
        ]);
    }

    public function setMainImage(Request $request, Product $product)
    {
        $imageId = (int) $request->input('image_id');
        $galleryImage = $product->images()->whereKey($imageId)->first();

        if (! $galleryImage) {
            return response()->json(['success' => false, 'message' => 'Gallery image not found.'], 404);
        }

        $product->update(['image' => $galleryImage->image]);

        return response()->json([
            'success' => true,
            'message' => 'Main card photo updated successfully.',
            'image_url' => $galleryImage->image_url,
        ]);
    }

    public function deleteGalleryImage(Product $product, ProductImage $image)
    {
        if ($image->product_id !== $product->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $this->uploader->delete($image->image);
        $image->delete();

        if ($product->image === $image->image) {
            $firstRemaining = $product->images()->first();
            $product->update(['image' => $firstRemaining?->image]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Gallery photo deleted successfully.',
            'remaining_count' => $product->images()->count(),
            'main_image_url' => $product->fresh()->image_url,
        ]);
    }

    private function reorderGalleryImages(Product $product, mixed $order): void
    {
        if (is_string($order)) {
            $order = json_decode($order, true) ?: [];
        }

        if (empty($order) || ! is_array($order)) {
            return;
        }

        if (array_is_list($order)) {
            foreach ($order as $index => $id) {
                if (is_numeric($id)) {
                    $product->images()->where('id', (int) $id)->update(['sort_order' => $index + 1]);
                }
            }
        } else {
            foreach ($order as $id => $sort) {
                if (is_numeric($id) && is_numeric($sort)) {
                    $product->images()->where('id', (int) $id)->update(['sort_order' => (int) $sort]);
                }
            }
        }
    }

    private function saveGallery(Product $product, Request $request): void
    {
        if (! $request->hasFile('gallery')) {
            return;
        }

        $remainingSlots = max(0, self::PRODUCT_DETAIL_IMAGE_MAX - $product->images()->count());
        $files = array_slice($request->file('gallery'), 0, $remainingSlots);

        if (! $files) {
            return;
        }

        $sort = $product->images()->max('sort_order') ?? 0;
        foreach ($this->uploader->uploadMany($files, 'products/gallery') as $path) {
            $product->images()->create(['image' => $path, 'sort_order' => ++$sort]);
        }
    }

    private function removeGalleryImages(Product $product, array $ids): void
    {
        foreach ($product->images()->whereIn('id', $ids)->get() as $img) {
            $this->uploader->delete($img->image);
            $img->delete();
        }
    }

    private function formData(): array
    {
        return [
            'categories' => Category::where('status', 'active')->orderBy('name')->get(),
            'brands' => Brand::where('status', 'active')->orderBy('name')->get(),
            'units' => ['pcs', 'kg', 'g', 'box', 'packet', 'litre', 'ml'],
        ];
    }

    private function normalizePackageRows(array $rows): array
    {
        $packages = [];

        foreach ($rows as $row) {
            $label = trim((string) ($row['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $packages[] = [
                'id' => $row['id'] ?? null,
                'label' => $label,
                'price' => (float) ($row['price'] ?? 0),
                'sale_price' => isset($row['sale_price']) && $row['sale_price'] !== '' ? (float) $row['sale_price'] : null,
                'stock_qty' => max((int) ($row['stock_qty'] ?? 0), 0),
                'status' => $row['status'] ?? 'active',
            ];
        }

        return $packages;
    }

    private function normalizeProductInformation(array $info): array
    {
        $normalized = collect([
            'product_type' => $info['product_type'] ?? null,
            'shelf_life' => $info['shelf_life'] ?? null,
            'ingredient' => $info['ingredient'] ?? null,
            'packaging_type' => $info['packaging_type'] ?? null,
            'storage' => $info['storage'] ?? null,
            'what_is_it' => $info['what_is_it'] ?? null,
            'how_it_works' => $info['how_it_works'] ?? null,
            'key_ingredients' => $info['key_ingredients'] ?? null,
            'how_to_use' => $info['how_to_use'] ?? null,
            'full_ingredients_list' => $info['full_ingredients_list'] ?? null,
            'clinically_tested' => $info['clinically_tested'] ?? null,
            'faqs' => $info['faqs'] ?? null,
        ])->map(fn ($value) => filled($value) ? trim((string) $value) : null)
            ->filter()
            ->all();

        if (!empty($info['certificate_reports']) && is_array($info['certificate_reports'])) {
            $normalized['certificate_reports'] = $info['certificate_reports'];
        }

        if (!empty($info['clinical_report_image'])) {
            $normalized['clinical_report_image'] = $info['clinical_report_image'];
        }

        return $normalized;
    }

    private function handleCertificateReports(Request $request, ?Product $product = null): array
    {
        $reports = [];
        $submitted = $request->input('certificate_reports', []);
        $files = $request->file('certificate_reports_file', []);
        $existingImagesKept = [];

        if (is_array($submitted)) {
            foreach ($submitted as $key => $item) {
                $remove = !empty($item['remove']);
                $existingImage = $item['image'] ?? null;

                if ($remove) {
                    if ($existingImage) {
                        $this->uploader->delete($existingImage);
                    }
                    continue;
                }

                $imagePath = $existingImage;
                if (isset($files[$key]) && $files[$key]->isValid()) {
                    if ($existingImage) {
                        $this->uploader->delete($existingImage);
                    }
                    $imagePath = $this->uploader->upload($files[$key], 'product_reports');
                }

                if ($imagePath) {
                    $existingImagesKept[] = $imagePath;
                }

                $title = trim((string)($item['title'] ?? ''));
                $shortDesc = trim((string)($item['short_desc'] ?? ''));

                if ($title !== '' || $imagePath !== null || $shortDesc !== '') {
                    $reports[] = [
                        'title' => $title ?: 'Lab Certificate Report',
                        'short_desc' => $shortDesc ?: 'Certified',
                        'image' => $imagePath,
                    ];
                }
            }
        }

        if ($product) {
            $prevReports = $product->product_information['certificate_reports'] ?? [];
            if (is_array($prevReports)) {
                foreach ($prevReports as $prevRep) {
                    $prevImg = $prevRep['image'] ?? null;
                    if ($prevImg && !in_array($prevImg, $existingImagesKept, true)) {
                        $this->uploader->delete($prevImg);
                    }
                }
            }
        }

        return $reports;
    }

    private function applyDefaultPackageToProductData(array &$data, array $packages): void
    {
        $defaultPackage = collect($packages)->firstWhere('status', 'active') ?? ($packages[0] ?? null);

        if (! $defaultPackage) {
            return;
        }

        $data['price'] = $defaultPackage['price'];
        $data['sale_price'] = $defaultPackage['sale_price'];
        $data['stock_qty'] = collect($packages)->sum('stock_qty');
        $data['unit'] = $data['unit'] ?? 'packet';
    }

    private function syncPackageVariants(Product $product, array $packages, array $removedVariationIds = []): void
    {
        if (empty($packages) && empty($removedVariationIds)) {
            return;
        }

        $weightAttribute = ! empty($packages)
            ? ProductAttribute::firstOrCreate(
                ['name' => 'Weight'],
                ['status' => 'active']
            )
            : null;

        $keptVariationIds = [];

        foreach ($packages as $package) {
            $variation = ! empty($package['id'])
                ? $product->variations()->whereKey($package['id'])->first()
                : null;

            if (! $variation) {
                $variation = $product->variations()
                    ->where('attr_id', $weightAttribute->id)
                    ->where('attr_val', $package['label'])
                    ->first();
            }

            $targetStock = $package['stock_qty'];
            $packageSku = !empty($package['sku']) ? trim($package['sku']) : $this->generateVariationSku($product, $package['label'], $variation?->id);
            $payload = [
                'product_id' => $product->id,
                // Keep the variation's original attribute. The product edit
                // form manages package labels, but must not turn existing
                // Color/Size/custom variations into Weight variations.
                'attr_id' => $variation ? $variation->attr_id : $weightAttribute->id,
                'attr_val' => $package['label'],
                'sku' => $packageSku,
                'price' => $package['price'],
                'sale_price' => $package['sale_price'],
                'status' => $package['status'],
            ];

            if ($variation) {
                $variation->update($payload);
            } else {
                $payload['stock_qty'] = 0;
                $variation = ProductVariation::create($payload);
            }

            if ($targetStock !== (int) $variation->stock_qty) {
                $this->inventoryService->adjustStock($variation, $targetStock, 'admin_package_update');
            }

            $keptVariationIds[] = $variation->id;
        }

        // Variations not shown on this form can be multi-attribute records or
        // records created by the dedicated variation manager. Never delete
        // those implicitly during a normal product edit. Deletion is only
        // performed for rows the admin explicitly removed in the UI.
        $removedVariationIds = collect($removedVariationIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => in_array($id, $keptVariationIds, true))
            ->values();

        if ($removedVariationIds->isNotEmpty()) {
            $product->variations()->whereIn('id', $removedVariationIds)->delete();
        }
    }

    private function generateVariationSku(Product $product, string $label, ?int $ignoreId = null): string
    {
        $base = trim($product->sku . '-' . strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $label)), '-');
        $base = $base ?: 'PRK-PACKAGE';
        $sku = $base;
        $counter = 2;

        while (
            ProductVariation::where('sku', $sku)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $sku = $base . '-' . $counter++;
        }

        return $sku;
    }

    private function uniqueSlug(string $value, string $table, string $column, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'product';
        $slug = $base;
        $counter = 2;

        while (
            DB::table($table)
                ->where($column, $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }

    private function generateSku(string $name): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3)) ?: 'PRK';

        do {
            $sku = 'PRK-' . $prefix . '-' . random_int(1000, 9999);
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }
}
