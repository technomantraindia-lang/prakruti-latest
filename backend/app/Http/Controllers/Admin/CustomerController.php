<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use ZipArchive;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customerRoleId = Role::where('name', 'Customer')->value('id');

        $query = User::where('role_id', $customerRoleId)->withCount('orders')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('profession', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(15)->withQueryString();
        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer)
    {
        $customer->load(['orders' => fn ($q) => $q->latest()->limit(10), 'addresses']);
        return view('admin.customers.show', compact('customer'));
    }

    public function export(Request $request)
    {
        $customerRoleId = Role::where('name', 'Customer')->value('id');

        $query = User::where('role_id', $customerRoleId)
            ->withCount('orders')
            ->withSum(['orders as total_spent' => function ($q) {
                $q->where('pay_status', 'paid');
            }], 'total')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('profession', 'like', "%{$search}%");
            });
        }

        $customers = $query->get();

        $headers = [
            'Customer ID',
            'Full Name',
            'Email Address',
            'Mobile Number',
            'Profession',
            'Gender',
            'Total Orders',
            'Total Spent (Rs)',
            'Account Status',
            'Registered Date',
            'Policy Agreed Date',
        ];

        $rows = [];
        foreach ($customers as $cust) {
            $regDate = $cust->created_at ? (\is_string($cust->created_at) ? \Carbon\Carbon::parse($cust->created_at)->format('d-M-Y H:i') : $cust->created_at->format('d-M-Y H:i')) : '-';

            $termsDate = '-';
            if ($cust->terms_accepted_at) {
                $termsDate = \is_string($cust->terms_accepted_at) ? \Carbon\Carbon::parse($cust->terms_accepted_at)->format('d-M-Y H:i') : $cust->terms_accepted_at->format('d-M-Y H:i');
            } elseif ($cust->created_at) {
                $termsDate = $regDate;
            }

            $rows[] = [
                'Customer ID' => 'CUST-' . str_pad($cust->id, 5, '0', STR_PAD_LEFT),
                'Full Name' => $cust->name,
                'Email Address' => $cust->email,
                'Mobile Number' => $cust->phone ?: '-',
                'Profession' => $cust->profession ?: '-',
                'Gender' => $cust->gender ? ucfirst($cust->gender) : '-',
                'Total Orders' => (string) ($cust->orders_count ?? 0),
                'Total Spent (Rs)' => number_format((float) ($cust->total_spent ?? 0), 2, '.', ''),
                'Account Status' => ucfirst($cust->status ?? 'active'),
                'Registered Date' => $regDate,
                'Policy Agreed Date' => $termsDate,
            ];
        }

        $dateStr = now()->format('Y-m-d');
        $fileName = "prakruti_customers_export_{$dateStr}.xlsx";
        $tempPath = storage_path("app/templates/temp_customers_export_" . uniqid() . ".xlsx");

        $dir = dirname($tempPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->generateXlsxFile($headers, $rows, $tempPath);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function generateXlsxFile(array $headers, array $rows, string $targetPath): string
    {
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
        $rowCount = count($rows);

        $tableXml = '<?xml version="1.0" encoding="utf-8"?><x:table id="1" name="CustomersExport" displayName="CustomersExport" ref="A1:' . $lastColLetter . ($rowCount + 1) . '" headerRowCount="1" totalsRowCount="0" totalsRowShown="0" xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><x:tableColumns count="' . $cols . '">';
        foreach ($headers as $idx => $h) {
            $tableXml .= '<x:tableColumn id="' . ($idx + 1) . '" name="' . htmlspecialchars($h, ENT_QUOTES | ENT_XML1) . '" />';
        }
        $tableXml .= '</x:tableColumns><x:tableStyleInfo name="TableStyleMedium2" showFirstColumn="0" showLastColumn="0" showRowStripes="1" showColumnStripes="0" /></x:table>';

        $sheetXml = '<?xml version="1.0" encoding="utf-8"?><x:worksheet xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $sheetXml .= '<x:sheetViews><x:sheetView showGridLines="1" workbookViewId="0"><x:pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen" /></x:sheetView></x:sheetViews>';
        $sheetXml .= '<x:sheetFormatPr defaultRowHeight="20" />';
        $sheetXml .= '<x:cols>';
        for ($i = 1; $i <= $cols; $i++) {
            $sheetXml .= '<x:col min="' . $i . '" max="' . $i . '" width="22" hidden="0" customWidth="1" />';
        }
        $sheetXml .= '</x:cols>';
        $sheetXml .= '<x:sheetData>';

        $sheetXml .= '<x:row r="1" ht="32" customHeight="1">';
        foreach ($headers as $idx => $h) {
            $colLetter = $getCol($idx + 1);
            $sheetXml .= '<x:c r="' . $colLetter . '1" s="6" t="str"><x:v>' . htmlspecialchars($h, ENT_QUOTES | ENT_XML1) . '</x:v></x:c>';
        }
        $sheetXml .= '</x:row>';

        foreach ($rows as $rowIdx => $row) {
            $rNum = $rowIdx + 2;
            $sheetXml .= '<x:row r="' . $rNum . '" ht="22" customHeight="1">';
            foreach ($headers as $colIdx => $h) {
                $colLetter = $getCol($colIdx + 1);
                $val = (string) ($row[$h] ?? '');
                $sheetXml .= '<x:c r="' . $colLetter . $rNum . '" s="9" t="str"><x:v>' . htmlspecialchars($val, ENT_QUOTES | ENT_XML1) . '</x:v></x:c>';
            }
            $sheetXml .= '</x:row>';
        }
        $sheetXml .= '</x:sheetData>';
        $sheetXml .= '<x:pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3" />';
        $sheetXml .= '<x:tableParts count="1"><x:tablePart r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" /></x:tableParts>';
        $sheetXml .= '</x:worksheet>';

        $sheetRelsXml = '<?xml version="1.0" encoding="utf-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/table" Target="../tables/table1.xml" /></Relationships>';
        $workbookXml = '<?xml version="1.0" encoding="utf-8"?><x:workbook xmlns:x="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><x:sheets><x:sheet name="Customers" sheetId="1" state="visible" r:id="rId1" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" /></x:sheets></x:workbook>';
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
}
