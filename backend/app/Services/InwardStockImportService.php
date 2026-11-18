<?php

namespace App\Services;

use App\Models\GoodsReceivedNote;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class InwardStockImportService
{
    public function __construct(private ProcurementService $procurementService) {}

    /**
     * Read the first worksheet using the CSV/XLSX parser.
     */
    public function readSpreadsheetRows(UploadedFile $file): array
    {
        return $this->readRows($file);
    }

    public function import(UploadedFile $file, ?User $user = null): int
    {
        $rows = $this->readRows($file);
        $groups = [];
        $errors = [];

        $currentGroup = null;
        $lastVendor = null;
        $groupSeq = 0;

        foreach ($rows as $row) {
            $data = $row['data'];
            $line = $row['line'];

            // Skip example/header rows
            $grnGroupCol = trim((string) ($data['grn_group'] ?? ''));
            if (str_starts_with(strtoupper($grnGroupCol), 'EXAMPLE')) {
                continue;
            }

            $vendorVal = trim((string) ($data['vendor_name'] ?? $data['vendor'] ?? $data['supplier'] ?? $data['vendor_id'] ?? ''));
            $invoiceVal = trim((string) ($data['vendor_invoice_no'] ?? $data['vendor_invoice_bill_no'] ?? $data['invoice_no'] ?? $data['bill_no'] ?? ''));
            $productVal = trim((string) ($data['product'] ?? $data['product_name'] ?? $data['product_sku'] ?? $data['item'] ?? ''));

            // Ignore blank empty rows
            if ($productVal === '' && $vendorVal === '' && $invoiceVal === '') {
                continue;
            }

            // Grouping logic:
            // 1. If explicit grn_group is specified, group by it
            if ($grnGroupCol !== '') {
                $groupKey = 'grp_' . $grnGroupCol;
            } elseif ($invoiceVal !== '') {
                // 2. If vendor_invoice_no is specified, group rows sharing that invoice
                $groupKey = 'inv_' . ($vendorVal !== '' ? $vendorVal . '_' : '') . $invoiceVal;
            } else {
                // 3. If invoice is not specified, consecutive rows of same vendor share a GRN group
                if ($vendorVal !== '' && $vendorVal !== $lastVendor) {
                    $groupSeq++;
                    $groupKey = 'auto_' . $groupSeq;
                } elseif ($currentGroup !== null) {
                    $groupKey = $currentGroup;
                } else {
                    $groupSeq++;
                    $groupKey = 'auto_' . $groupSeq;
                }
            }

            $currentGroup = $groupKey;
            if ($vendorVal !== '') {
                $lastVendor = $vendorVal;
            }

            $groups[$groupKey][] = $row;
        }

        if (empty($groups)) {
            throw ValidationException::withMessages([
                'file' => 'No inward stock rows found. Please fill in the product and vendor details in the template.',
            ]);
        }

        $prepared = [];
        foreach ($groups as $groupKey => $groupRows) {
            // Find vendor for this group
            $vendorVal = '';
            foreach ($groupRows as $gr) {
                $v = trim((string) ($gr['data']['vendor_name'] ?? $gr['data']['vendor'] ?? $gr['data']['supplier'] ?? $gr['data']['vendor_id'] ?? ''));
                if ($v !== '') {
                    $vendorVal = $v;
                    break;
                }
            }

            $vendor = $this->resolveVendor($vendorVal);
            if (! $vendor) {
                $firstLine = $groupRows[0]['line'];
                $errors[] = "Row {$firstLine}: Vendor '{$vendorVal}' was not found. Please provide an active vendor/supplier name.";
                continue;
            }

            // Find or auto-generate vendor_invoice_no
            $invoiceNo = '';
            foreach ($groupRows as $gr) {
                $inv = trim((string) ($gr['data']['vendor_invoice_no'] ?? $gr['data']['vendor_invoice_bill_no'] ?? $gr['data']['invoice_no'] ?? $gr['data']['bill_no'] ?? ''));
                if ($inv !== '') {
                    $invoiceNo = $inv;
                    break;
                }
            }
            if ($invoiceNo === '') {
                $invoiceNo = GoodsReceivedNote::generateVendorInvoiceNumber();
            }

            $firstData = $groupRows[0]['data'];
            $invoiceDate = $this->date($firstData['invoice_date'] ?? null) ?: date('Y-m-d');
            $receivedDate = $this->date($firstData['received_date'] ?? $firstData['stock_received_date'] ?? null) ?: date('Y-m-d');
            $freight = is_numeric($firstData['inward_freight_charge'] ?? $firstData['inward_freight_transport'] ?? null)
                ? (float) ($firstData['inward_freight_charge'] ?? $firstData['inward_freight_transport']) : 0;
            $handling = is_numeric($firstData['inward_handling_charge'] ?? $firstData['handling_unloading_charge'] ?? null)
                ? (float) ($firstData['inward_handling_charge'] ?? $firstData['handling_unloading_charge']) : 0;
            $paymentStatus = in_array(strtolower(trim((string) ($firstData['payment_status'] ?? 'unpaid'))), ['paid', 'unpaid'], true)
                ? strtolower(trim((string) $firstData['payment_status'])) : 'unpaid';
            $notes = trim((string) ($firstData['notes'] ?? $firstData['notes_delivery_remarks'] ?? '')) ?: "Imported inward stock ({$invoiceNo})";

            $items = [];
            foreach ($groupRows as $row) {
                $data = $row['data'];
                $line = $row['line'];

                $productVal = trim((string) ($data['product'] ?? $data['product_name'] ?? $data['product_sku'] ?? $data['item'] ?? ''));
                if ($productVal === '') {
                    $errors[] = "Row {$line}: Product name or SKU is required.";
                    continue;
                }

                $product = $this->resolveProduct($productVal);
                if (! $product) {
                    $errors[] = "Row {$line}: Product '{$productVal}' was not found. Please provide an active product name or SKU.";
                    continue;
                }

                $pkgVal = trim((string) ($data['packaging'] ?? $data['packaging_size'] ?? $data['package'] ?? $data['size'] ?? $data['variation'] ?? $data['variation_sku'] ?? ''));
                $variation = $this->resolveVariation($product, $pkgVal);
                if ($variation === false) {
                    $activePackages = $product->variations()->where('status', 'active')->pluck('attr_val')->filter()->implode(', ');
                    $pkgList = $activePackages ? " (active packages: {$activePackages})" : "";
                    $errors[] = "Row {$line}: Packaging / Size is required for '{$product->name}'{$pkgList}.";
                    continue;
                }

                $receivedQty = $this->integer($data['received_qty'] ?? $data['recv_qty'] ?? $data['quantity'] ?? $data['qty'] ?? null);
                $acceptedQtyRaw = $data['accepted_qty'] ?? $data['accept_qty'] ?? null;
                $acceptedQty = ($acceptedQtyRaw !== null && $acceptedQtyRaw !== '') ? $this->integer($acceptedQtyRaw) : $receivedQty;

                if ($receivedQty < 1 || $acceptedQty < 0 || $acceptedQty > $receivedQty) {
                    $errors[] = "Row {$line}: Accepted Qty ({$acceptedQty}) must be between 0 and Received Qty ({$receivedQty}).";
                    continue;
                }

                $batchNumber = trim((string) ($data['batch_number'] ?? $data['batch_no'] ?? $data['batch'] ?? ''));
                if ($batchNumber === '') {
                    $batchNumber = 'B-' . date('ymd') . '-' . str_pad((string) $line, 2, '0', STR_PAD_LEFT);
                }

                $rateRaw = $data['purchase_rate'] ?? $data['unit_purchase_price'] ?? $data['purchase_price'] ?? $data['rate'] ?? null;
                if (! is_numeric($rateRaw) || (float) $rateRaw < 0) {
                    $errors[] = "Row {$line}: Purchase Rate must be a valid positive number.";
                    continue;
                }
                $purchaseRate = (float) $rateRaw;

                $mrpRaw = $data['mrp'] ?? $data['retail_price'] ?? null;
                if ($mrpRaw !== null && is_numeric($mrpRaw) && (float) $mrpRaw > 0) {
                    $mrp = (float) $mrpRaw;
                } else {
                    $mrp = $variation ? (float) $variation->price : (float) $product->price;
                }

                $taxRateRaw = $data['gst_rate'] ?? $data['tax_rate'] ?? $data['gst'] ?? $data['tax'] ?? 0;
                $taxRate = is_numeric($taxRateRaw) ? (float) $taxRateRaw : 0;

                $mfgDate = $this->date($data['mfg_date'] ?? $data['manufacturing_date'] ?? null) ?: date('Y-m-d');
                $expiryDate = $this->date($data['expiry_date'] ?? $data['exp_date'] ?? null) ?: date('Y-m-d', strtotime('+1 year', strtotime($mfgDate)));
                $rejectionReason = trim((string) ($data['rejection_reason'] ?? '')) ?: null;

                $items[] = [
                    'product_id' => $product->id,
                    'product_variation_id' => $variation?->id,
                    'received_qty' => $receivedQty,
                    'accepted_qty' => $acceptedQty,
                    'rejected_qty' => max($receivedQty - $acceptedQty, 0),
                    'batch_number' => $batchNumber,
                    'mfg_date' => $mfgDate,
                    'expiry_date' => $expiryDate,
                    'unit_purchase_price' => $purchaseRate,
                    'mrp' => $mrp,
                    'tax_rate' => $taxRate,
                    'rejection_reason' => $rejectionReason,
                ];
            }

            if (! empty($items)) {
                $prepared[] = [
                    'vendor_id' => $vendor->id,
                    'vendor_invoice_no' => $invoiceNo,
                    'invoice_date' => $invoiceDate,
                    'received_date' => $receivedDate,
                    'inward_freight_charge' => $freight,
                    'inward_handling_charge' => $handling,
                    'payment_status' => $paymentStatus,
                    'notes' => $notes,
                    'items' => $items,
                ];
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages(['file' => implode(' ', $errors)]);
        }

        DB::transaction(function () use ($prepared, $user) {
            foreach ($prepared as $grnData) {
                $this->procurementService->processGoodsReceivedNote($grnData, $user);
            }
        });

        return count($prepared);
    }

    public function resolveVendor($value): ?Vendor
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $byId = Vendor::whereKey((int) $value)->where('is_active', true)->first();
            if ($byId) {
                return $byId;
            }
        }

        return Vendor::where('is_active', true)
            ->where(function ($query) use ($value) {
                $query->whereRaw('LOWER(name) = ?', [strtolower($value)])
                      ->orWhereRaw('LOWER(company_name) = ?', [strtolower($value)]);
            })
            ->first();
    }

    public function resolveProduct($value): ?Product
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return Product::where('sku', $value)
            ->orWhere('name', $value)
            ->orWhereRaw('LOWER(name) = ?', [strtolower($value)])
            ->orWhereRaw('LOWER(sku) = ?', [strtolower($value)])
            ->first();
    }

    public function resolveVariation(Product $product, $value): ProductVariation|false|null
    {
        $value = trim((string) $value);
        if ($value !== '') {
            $matched = $product->variations()
                ->where('status', 'active')
                ->where(function ($query) use ($value) {
                    $query->where('sku', $value)
                          ->orWhere('attr_val', $value)
                          ->orWhereRaw('LOWER(attr_val) = ?', [strtolower($value)])
                          ->orWhereRaw('LOWER(sku) = ?', [strtolower($value)]);
                })
                ->first();

            return $matched ?: false;
        }

        $active = $product->variations()->where('status', 'active')->get();
        return $active->count() > 1 ? false : $active->first();
    }

    public function ensureTemplateExists(): string
    {
        $path = storage_path('app/templates/inward-stock-import-template.xlsx');
        if (! is_file($path)) {
            $this->generateTemplateFile($path);
        }
        return $path;
    }

    public function generateTemplateFile(?string $targetPath = null): string
    {
        $targetPath = $targetPath ?: storage_path('app/templates/inward-stock-import-template.xlsx');
        $dir = dirname($targetPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $headers = [
            'vendor_name',
            'vendor_invoice_no',
            'invoice_date',
            'received_date',
            'inward_freight_charge',
            'inward_handling_charge',
            'payment_status',
            'notes',
            'product',
            'packaging',
            'batch_number',
            'mfg_date',
            'expiry_date',
            'received_qty',
            'accepted_qty',
            'purchase_rate',
            'mrp',
            'gst_rate',
        ];

        // Sample rows with live or fallback values
        $sampleVendor = Vendor::where('is_active', true)->first();
        $vendorName = $sampleVendor ? $sampleVendor->name : 'Organic Farms';

        $p1 = Product::where('status', 'active')->with('variations')->first();
        $p1Name = $p1 ? $p1->name : 'Basmati Rice';
        $p1Var = $p1 && $p1->variations->isNotEmpty() ? $p1->variations->first()->attr_val : '500';
        $p1Mrp = $p1 && $p1->variations->isNotEmpty() ? (float) $p1->variations->first()->price : 150.00;

        $p2 = Product::where('status', 'active')->with('variations')->skip(1)->first() ?: $p1;
        $p2Name = $p2 ? $p2->name : 'Tuar Dal';
        $p2Var = $p2 && $p2->variations->isNotEmpty() ? $p2->variations->first()->attr_val : '500';
        $p2Mrp = $p2 && $p2->variations->isNotEmpty() ? (float) $p2->variations->first()->price : 180.00;

        $invNo = GoodsReceivedNote::generateVendorInvoiceNumber();
        $today = date('Y-m-d');
        $nextYear = date('Y-m-d', strtotime('+1 year'));

        $sampleRows = [
            [
                'vendor_name' => $vendorName,
                'vendor_invoice_no' => $invNo,
                'invoice_date' => $today,
                'received_date' => $today,
                'inward_freight_charge' => '0.00',
                'inward_handling_charge' => '0.00',
                'payment_status' => 'unpaid',
                'notes' => 'Received in good condition',
                'product' => $p1Name,
                'packaging' => $p1Var,
                'batch_number' => 'B-' . date('ymd') . '-01',
                'mfg_date' => $today,
                'expiry_date' => $nextYear,
                'received_qty' => '10',
                'accepted_qty' => '10',
                'purchase_rate' => '100.00',
                'mrp' => number_format($p1Mrp, 2, '.', ''),
                'gst_rate' => '5',
            ],
            [
                'vendor_name' => $vendorName,
                'vendor_invoice_no' => $invNo,
                'invoice_date' => $today,
                'received_date' => $today,
                'inward_freight_charge' => '0.00',
                'inward_handling_charge' => '0.00',
                'payment_status' => 'unpaid',
                'notes' => 'Received in good condition',
                'product' => $p2Name,
                'packaging' => $p2Var,
                'batch_number' => 'B-' . date('ymd') . '-02',
                'mfg_date' => $today,
                'expiry_date' => $nextYear,
                'received_qty' => '15',
                'accepted_qty' => '15',
                'purchase_rate' => '120.00',
                'mrp' => number_format($p2Mrp, 2, '.', ''),
                'gst_rate' => '5',
            ],
        ];

        $cols = count($headers);
        $lastColLetter = chr(65 + $cols - 1);

        $tableXml = '<?xml version="1.0" encoding="utf-8"?><x:table id="1" name="InwardStockTemplate" displayName="InwardStockTemplate" ref="A1:' . $lastColLetter . (count($sampleRows) + 1) . '" headerRowCount="1" totalsRowCount="0" totalsRowShown="0" xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><x:tableColumns count="' . $cols . '">';
        foreach ($headers as $idx => $h) {
            $tableXml .= '<x:tableColumn id="' . ($idx + 1) . '" name="' . htmlspecialchars($h, ENT_QUOTES | ENT_XML1) . '" />';
        }
        $tableXml .= '</x:tableColumns><x:tableStyleInfo name="TableStyleMedium2" showFirstColumn="0" showLastColumn="0" showRowStripes="1" showColumnStripes="0" /></x:table>';

        $sheetXml = '<?xml version="1.0" encoding="utf-8"?><x:worksheet xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $sheetXml .= '<x:sheetViews><x:sheetView showGridLines="1" workbookViewId="0"><x:pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen" /></x:sheetView></x:sheetViews>';
        $sheetXml .= '<x:sheetFormatPr defaultRowHeight="18" />';
        $sheetXml .= '<x:cols>';
        for ($i = 1; $i <= $cols; $i++) {
            $sheetXml .= '<x:col min="' . $i . '" max="' . $i . '" width="22" hidden="0" customWidth="1" />';
        }
        $sheetXml .= '</x:cols>';
        $sheetXml .= '<x:sheetData>';

        // Header row
        $sheetXml .= '<x:row r="1" ht="34" customHeight="1">';
        foreach ($headers as $idx => $h) {
            $colLetter = chr(65 + $idx);
            $sheetXml .= '<x:c r="' . $colLetter . '1" s="6" t="str"><x:v>' . htmlspecialchars($h, ENT_QUOTES | ENT_XML1) . '</x:v></x:c>';
        }
        $sheetXml .= '</x:row>';

        // Sample rows
        foreach ($sampleRows as $rowIdx => $row) {
            $rNum = $rowIdx + 2;
            $sheetXml .= '<x:row r="' . $rNum . '" ht="22" customHeight="1">';
            foreach ($headers as $colIdx => $h) {
                $colLetter = chr(65 + $colIdx);
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
        $workbookXml = '<?xml version="1.0" encoding="utf-8"?><x:workbook xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><x:sheets><x:sheet name="Inward Stock" sheetId="1" state="visible" r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" /></x:sheets></x:workbook>';
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

    private function readRows(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === 'csv' || $extension === 'txt') {
            return $this->readCsv($file->getRealPath());
        }
        if ($extension === 'xlsx') {
            return $this->readXlsx($file->getRealPath());
        }

        throw ValidationException::withMessages([
            'file' => 'Upload the provided .xlsx template or a UTF-8 .csv file. Legacy .xls files are not supported.',
        ]);
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        $headers = fgetcsv($handle);
        if (! $headers) {
            return [];
        }
        $headers = array_map(fn ($header) => $this->header($header), $headers);
        $rows = [];
        $line = 1;
        while (($values = fgetcsv($handle)) !== false) {
            $line++;
            $rows[] = ['line' => $line, 'data' => $this->rowData($headers, $values)];
        }
        fclose($handle);
        return $rows;
    }

    private function readXlsx(string $path): array
    {
        $entries = $this->readZipEntries($path);
        $sharedStrings = [];
        if (isset($entries['xl/sharedStrings.xml'])) {
            preg_match_all('/<(?:[A-Za-z0-9_]+:)?si\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?si>/s', $entries['xl/sharedStrings.xml'], $matches);
            foreach ($matches[1] ?? [] as $item) {
                preg_match_all('/<(?:[A-Za-z0-9_]+:)?t\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?t>/s', $item, $textMatches);
                $sharedStrings[] = html_entity_decode(implode('', $textMatches[1] ?? []), ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }

        $sheetXml = $entries['xl/worksheets/sheet1.xml'] ?? null;
        if ($sheetXml === null) {
            throw ValidationException::withMessages(['file' => 'The uploaded XLSX does not contain a first worksheet.']);
        }

        $rows = [];
        $headers = [];
        preg_match_all('/<(?:[A-Za-z0-9_]+:)?row\b[^>]*r="(\d+)"[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?row>/s', $sheetXml, $rowMatches, PREG_SET_ORDER);
        foreach ($rowMatches as $rowMatch) {
            $rowNumber = (int) $rowMatch[1];
            $rowXml = $rowMatch[2];
            $values = [];
            preg_match_all('/<(?:[A-Za-z0-9_]+:)?c\b([^>]*?)(?:\/>|>(.*?)<\/(?:[A-Za-z0-9_]+:)?c>)/s', $rowXml, $cellMatches, PREG_SET_ORDER);
            foreach ($cellMatches as $cellMatch) {
                $attributes = $cellMatch[1] ?? '';
                $content = $cellMatch[2] ?? '';
                preg_match('/\br="([A-Z]+\d+)"/', $attributes, $refMatch);
                preg_match('/\bt="([^"]+)"/', $attributes, $typeMatch);
                preg_match('/<(?:[A-Za-z0-9_]+:)?v\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?v>/s', $content, $valueMatch);
                preg_match_all('/<(?:[A-Za-z0-9_]+:)?t\b[^>]*>(.*?)<\/(?:[A-Za-z0-9_]+:)?t>/s', $content, $inlineMatches);
                $column = $this->columnNumber(preg_replace('/\d+/', '', $refMatch[1] ?? 'A'));
                $type = $typeMatch[1] ?? '';
                $value = $type === 'inlineStr'
                    ? html_entity_decode(implode('', $inlineMatches[1] ?? []), ENT_QUOTES | ENT_XML1, 'UTF-8')
                    : html_entity_decode($valueMatch[1] ?? '', ENT_QUOTES | ENT_XML1, 'UTF-8');
                if ($type === 's' && isset($sharedStrings[(int) $value])) {
                    $value = $sharedStrings[(int) $value];
                }
                $values[$column] = $value;
            }

            if (! $headers) {
                $lastHeaderColumn = empty($values) ? 0 : max(array_keys($values));
                $headers = array_map(fn ($header) => $this->header($header), $this->orderedValues($values, $lastHeaderColumn));
                continue;
            }
            $rows[] = ['line' => $rowNumber, 'data' => $this->rowData($headers, $this->orderedValues($values, count($headers) - 1))];
        }
        return $rows;
    }

    private function readZipEntries(string $path): array
    {
        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            if ($zip->open($path) !== true) {
                throw ValidationException::withMessages(['file' => 'The uploaded XLSX file could not be opened.']);
            }
            $entries = [];
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);
                if ($name) {
                    $entries[$name] = $zip->getFromIndex($index);
                }
            }
            $zip->close();
            return $entries;
        }

        $data = file_get_contents($path);
        $end = $data === false ? false : strrpos($data, "\x50\x4b\x05\x06");
        if ($data === false || $end === false) {
            throw ValidationException::withMessages(['file' => 'The uploaded XLSX file could not be opened.']);
        }
        $endHeader = unpack('vdisk/vstart/ventriesDisk/ventries/Vsize/Voffset/vcomment', substr($data, $end + 4, 18));
        $entries = [];
        $offset = $endHeader['offset'];
        for ($index = 0; $index < $endHeader['entries']; $index++) {
            if (substr($data, $offset, 4) !== "\x50\x4b\x01\x02") {
                break;
            }
            $header = unpack('vmade/vneeded/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vuncompressed/vname/vextra/vcomment/vdisk/vinternal/Vexternal/Vlocal', substr($data, $offset + 4, 42));
            $name = substr($data, $offset + 46, $header['name']);
            $local = $header['local'];
            $localHeader = unpack('vneeded/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vuncompressed/vname/vextra', substr($data, $local + 4, 26));
            $payload = substr($data, $local + 30 + $localHeader['name'] + $localHeader['extra'], $header['compressed']);
            $entries[$name] = $header['method'] === 0 ? $payload : @gzinflate($payload);
            $offset += 46 + $header['name'] + $header['extra'] + $header['comment'];
        }
        return $entries;
    }

    private function orderedValues(array $values, int $lastColumn): array
    {
        $ordered = [];
        for ($index = 0; $index <= $lastColumn; $index++) {
            $ordered[] = $values[$index] ?? '';
        }
        return $ordered;
    }

    private function rowData(array $headers, array $values): array
    {
        $data = [];
        foreach ($headers as $index => $header) {
            $data[$header] = trim((string) ($values[$index] ?? ''));
        }
        return $data;
    }

    private function header($value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
        return trim((string) preg_replace('/[^a-z0-9]+/i', '_', strtolower($value)), '_');
    }

    private function columnNumber(string $letters): int
    {
        $number = 0;
        foreach (str_split($letters) as $letter) {
            $number = ($number * 26) + (ord($letter) - 64);
        }
        return max($number - 1, 0);
    }

    private function integer($value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function date($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (is_numeric($value) && (float) $value > 20000) {
            return date('Y-m-d', strtotime('1899-12-30 +' . (int) $value . ' days'));
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }
}
