<?php

namespace App\Services;

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
     * Read the first worksheet using the same CSV/XLSX parser used by inward
     * stock. Each row contains a normalized header map and source line number.
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

        foreach ($rows as $row) {
            $data = $row['data'];
            $group = trim((string) ($data['grn_group'] ?? ''));
            if ($group === '' || str_starts_with(strtoupper($group), 'EXAMPLE')) {
                continue;
            }
            $groups[$group][] = $row;
        }

        if (empty($groups)) {
            throw ValidationException::withMessages(['file' => 'No import rows found. Add at least one product row below the template header.']);
        }

        $prepared = [];
        foreach ($groups as $group => $groupRows) {
            $first = $groupRows[0]['data'];
            $vendor = $this->resolveVendor($first['vendor_id'] ?? '');
            if (! $vendor) {
                $errors[] = "GRN group {$group}: vendor_id must be an existing vendor ID or exact vendor/company name.";
                continue;
            }

            $items = [];
            foreach ($groupRows as $row) {
                $data = $row['data'];
                $line = $row['line'];
                $productSku = trim((string) ($data['product_sku'] ?? ''));
                $product = Product::where('sku', $productSku)->first();
                if (! $product) {
                    $errors[] = "Excel row {$line}: product_sku '{$productSku}' was not found.";
                    continue;
                }

                $variation = $this->resolveVariation($product, $data['variation_sku'] ?? '');
                if ($variation === false) {
                    $errors[] = "Excel row {$line}: variation_sku is required because '{$product->name}' has multiple active packages.";
                    continue;
                }

                $receivedQty = $this->integer($data['received_qty'] ?? null);
                $acceptedQty = $this->integer($data['accepted_qty'] ?? $receivedQty);
                if ($receivedQty < 1 || $acceptedQty < 0 || $acceptedQty > $receivedQty) {
                    $errors[] = "Excel row {$line}: accepted_qty must be between 0 and received_qty.";
                    continue;
                }

                $batchNumber = trim((string) ($data['batch_number'] ?? ''));
                if ($batchNumber === '') {
                    $errors[] = "Excel row {$line}: batch_number is required.";
                    continue;
                }

                if (! is_numeric($data['unit_purchase_price'] ?? null) || ! is_numeric($data['mrp'] ?? null)) {
                    $errors[] = "Excel row {$line}: unit_purchase_price and mrp must be numeric.";
                    continue;
                }

                $items[] = [
                    'product_id' => $product->id,
                    'product_variation_id' => $variation?->id,
                    'received_qty' => $receivedQty,
                    'accepted_qty' => $acceptedQty,
                    'rejected_qty' => max($receivedQty - $acceptedQty, 0),
                    'batch_number' => $batchNumber,
                    'mfg_date' => $this->date($data['mfg_date'] ?? null),
                    'expiry_date' => $this->date($data['expiry_date'] ?? null),
                    'unit_purchase_price' => (float) $data['unit_purchase_price'],
                    'mrp' => (float) $data['mrp'],
                    'tax_rate' => is_numeric($data['tax_rate'] ?? null) ? (float) $data['tax_rate'] : 0,
                    'rejection_reason' => trim((string) ($data['rejection_reason'] ?? '')) ?: null,
                ];
            }

            if ($items) {
                $prepared[] = [
                    'vendor_id' => $vendor->id,
                    'vendor_invoice_no' => trim((string) ($first['vendor_invoice_no'] ?? '')) ?: null,
                    'invoice_date' => $this->date($first['invoice_date'] ?? null),
                    'received_date' => $this->date($first['received_date'] ?? null) ?: now()->toDateString(),
                    'inward_freight_charge' => is_numeric($first['inward_freight_charge'] ?? null) ? (float) $first['inward_freight_charge'] : 0,
                    'inward_handling_charge' => is_numeric($first['inward_handling_charge'] ?? null) ? (float) $first['inward_handling_charge'] : 0,
                    'payment_status' => in_array(strtolower(trim((string) ($first['payment_status'] ?? 'unpaid'))), ['paid', 'unpaid'], true) ? strtolower(trim((string) $first['payment_status'])) : 'unpaid',
                    'notes' => trim((string) ($first['notes'] ?? '')) ?: "Imported from Excel group {$group}",
                    'items' => $items,
                ];
            }
        }

        if ($errors) {
            throw ValidationException::withMessages(['file' => implode(' ', $errors)]);
        }

        DB::transaction(function () use ($prepared, $user) {
            foreach ($prepared as $grnData) {
                $this->procurementService->processGoodsReceivedNote($grnData, $user);
            }
        });

        return count($prepared);
    }

    private function resolveVendor($value): ?Vendor
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return is_numeric($value)
            ? Vendor::whereKey((int) $value)->where('is_active', true)->first()
            : Vendor::where('is_active', true)
                ->where(fn ($query) => $query->whereRaw('LOWER(name) = ?', [strtolower($value)])->orWhereRaw('LOWER(company_name) = ?', [strtolower($value)]))
                ->first();
    }

    private function resolveVariation(Product $product, $sku): ProductVariation|false|null
    {
        $sku = trim((string) $sku);
        if ($sku !== '') {
            return $product->variations()->where('sku', $sku)->where('status', 'active')->first() ?: false;
        }

        $active = $product->variations()->where('status', 'active')->get();
        return $active->count() > 1 ? false : $active->first();
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

        throw ValidationException::withMessages(['file' => 'Upload the provided .xlsx template or a UTF-8 .csv file. Legacy .xls files are not supported.']);
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

        // XLSX is a ZIP container. This small reader keeps imports working on
        // minimal PHP installations that do not have ext-zip enabled.
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
