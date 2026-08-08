<?php

/**
 * One-shot: extract MHE PROFILE UPDATE.xlsx profile sheets → mhe_inventories.json
 *
 * Usage: php database/scripts/extract_mhe_inventories.php
 */

declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\IOFactory;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$excelPath = 'C:\\Users\\everg\\Downloads\\MHE PROFILE UPDATE.xlsx';
$outPath = dirname(__DIR__).'/data/mhe_inventories.json';

if (! is_file($excelPath)) {
    fwrite(STDERR, "Excel not found: {$excelPath}\n");
    exit(1);
}

/** @var array<string, array<string, int>> $sheetMaps */
$sheetMaps = [
    'DISTRICT 1' => [
        'district' => 'District 1',
        'site' => 1,
        'client_fsc' => 2,
        'provider' => 3,
        'brand' => 4,
        'type' => 5,
        'unit' => 6,
        'years_in_service' => 7,
        'total_kl_run' => 8,
        'total_down_hours' => 9,
        'battery_unit_no' => 11,
        'battery_years' => 12,
        'equipment_status' => 14,
        'battery_man_count' => 15,
        'technicians_on_site' => 16,
        'branch_location' => 18,
        'total_technicians' => 19,
        'combined_type_unit' => true,
    ],
    'DIST 2' => [
        'district' => 'District 2',
        'site' => 1,
        'client_fsc' => 2,
        'provider' => 3,
        'brand' => 4,
        'type' => 6,
        'unit' => 7,
        'years_in_service' => 8,
        'total_kl_run' => 9,
        'total_down_hours' => 10,
        'battery_unit_no' => 12,
        'battery_years' => 13,
        'battery_man_count' => 15,
        'technicians_on_site' => 16,
        'branch_location' => 18,
        'total_technicians' => 19,
    ],
    'DIST 3' => [
        'district' => 'District 3',
        'site' => 1,
        'client_fsc' => 2,
        'provider' => 3,
        'brand' => 4,
        'type' => 6,
        'unit' => 7,
        'years_in_service' => 8,
        'equipment_status' => 9,
        'total_kl_run' => 10,
        'total_down_hours' => 11,
        'battery_unit_no' => 13,
        'battery_years' => 14,
        'battery_man_count' => 16,
        'technicians_on_site' => 17,
        'branch_location' => 19,
        'total_technicians' => 20,
    ],
    'DIST 4' => [
        'district' => 'District 4',
        'site' => 1,
        'client_fsc' => 2,
        'provider' => 3,
        'brand' => 4,
        'type' => 6,
        'unit' => 7,
        'years_in_service' => 8,
        'total_kl_run' => 9,
        'total_down_hours' => 10,
        'remarks' => 11,
        'battery_unit_no' => 13,
        'battery_years' => 14,
        'battery_man_count' => 16,
        'branch_location' => 19,
        'total_technicians' => 20,
    ],
    'DIST 5' => [
        'district' => 'District 5',
        'site' => 1,
        'client_fsc' => 2,
        'provider' => 3,
        'brand' => 4,
        'type' => 6,
        'unit' => 7,
        'years_in_service' => 8,
        'total_kl_run' => 9,
        'total_down_hours' => 10,
        'battery_unit_no' => 12,
        'battery_years' => 13,
        'equipment_status' => 14,
        'battery_man_count' => 16,
        'technicians_on_site' => 17,
        'branch_location' => 19,
        'total_technicians' => 20,
    ],
    'DIST 6' => [
        'district' => 'District 6',
        'site' => 1,
        'client_fsc' => 2,
        'provider' => 3,
        'brand' => 4,
        'type' => 6,
        'unit' => 7,
        'years_in_service' => 8,
        'total_kl_run' => 9,
        'total_down_hours' => 10,
        'battery_unit_no' => 12,
        'battery_years' => 13,
        'battery_man_count' => 15,
        'technicians_on_site' => 16,
        'branch_location' => 18,
        'total_technicians' => 19,
        'remarks' => 20,
    ],
    'D7' => [
        'district' => 'District 7',
        'site' => 1,
        'client_fsc' => 2,
        'provider' => 3,
        'brand' => 4,
        'type' => 6,
        'unit' => 7,
        'years_in_service' => 8,
        'total_kl_run' => 9,
        'total_down_hours' => 10,
        'battery_unit_no' => 12,
        'battery_years' => 13,
        'battery_man_count' => 15,
        'technicians_on_site' => 16,
        'branch_location' => 18,
        'total_technicians' => 19,
    ],
    'FTMC' => [
        'district' => 'FTM',
        'site' => 1,
        'client_fsc' => 2,
        'provider' => 3,
        'brand' => 4,
        'type' => 6,
        'unit' => 7,
        'years_in_service' => 8,
        'total_kl_run' => 9,
        'total_down_hours' => 10,
        'battery_unit_no' => 12,
        'battery_years' => 13,
        'technicians_on_site' => 15,
        'branch_location' => 17,
        'total_technicians' => 18,
    ],
    'FCCSI2' => [
        'district' => 'FCCSI',
        'site' => 1,
        'client_fsc' => 2,
        'provider' => 3,
        'brand' => 4,
        'type' => 6,
        'unit' => 7,
        'years_in_service' => 8,
        'total_kl_run' => 9,
        'total_down_hours' => 10,
        'battery_unit_no' => 12,
        'battery_years' => 13,
        'equipment_status' => 14,
        'battery_man_count' => 16,
        'technicians_on_site' => 17,
        'branch_location' => 19,
        'total_technicians' => 20,
    ],
];

function cell(object $sheet, int $row, int $col): string
{
    $value = $sheet->getCell([$col, $row])->getFormattedValue();

    return trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
}

function nullIfEmpty(string $value): ?string
{
    return $value === '' ? null : $value;
}

$spreadsheet = IOFactory::load($excelPath);
$rows = [];

foreach ($sheetMaps as $sheetName => $map) {
    $sheet = $spreadsheet->getSheetByName($sheetName);
    if (! $sheet) {
        fwrite(STDERR, "Missing sheet: {$sheetName}\n");
        continue;
    }

    $highestRow = $sheet->getHighestDataRow();
    $combined = ! empty($map['combined_type_unit']);

    for ($r = 3; $r <= $highestRow; $r++) {
        $site = cell($sheet, $r, $map['site']);
        if ($site === '' || strcasecmp($site, 'SITE') === 0) {
            continue;
        }

        $typeRaw = cell($sheet, $r, $map['type']);
        $unitRaw = cell($sheet, $r, $map['unit']);

        if ($combined && $unitRaw === '' && $typeRaw !== '') {
            if (preg_match('/^(.*?)\s*\((.+?)\)\s*$/u', $typeRaw, $m)) {
                $unitRaw = trim($m[1]);
                $typeRaw = trim($m[2]);
            } else {
                // e.g. MPC0010 — treat whole cell as unit, leave type null for seeder heuristics
                $unitRaw = $typeRaw;
                $typeRaw = '';
            }
        }

        if ($typeRaw === '' && $unitRaw === '') {
            continue;
        }

        $row = [
            'district' => $map['district'],
            'site' => $site,
            'client_fsc' => nullIfEmpty(cell($sheet, $r, $map['client_fsc'])),
            'provider' => nullIfEmpty(cell($sheet, $r, $map['provider'])),
            'brand' => nullIfEmpty(cell($sheet, $r, $map['brand'])),
            'equipment_type' => nullIfEmpty($typeRaw),
            'unit_no' => nullIfEmpty($unitRaw),
            'years_in_service' => nullIfEmpty(cell($sheet, $r, $map['years_in_service'])),
            'total_kl_run' => nullIfEmpty(cell($sheet, $r, $map['total_kl_run'])),
            'total_down_hours' => nullIfEmpty(cell($sheet, $r, $map['total_down_hours'])),
            'battery_unit_no' => isset($map['battery_unit_no']) ? nullIfEmpty(cell($sheet, $r, $map['battery_unit_no'])) : null,
            'battery_years' => isset($map['battery_years']) ? nullIfEmpty(cell($sheet, $r, $map['battery_years'])) : null,
            'equipment_status_raw' => isset($map['equipment_status']) ? nullIfEmpty(cell($sheet, $r, $map['equipment_status'])) : null,
            'battery_man_count' => isset($map['battery_man_count']) ? nullIfEmpty(cell($sheet, $r, $map['battery_man_count'])) : null,
            'technicians_on_site' => isset($map['technicians_on_site']) ? nullIfEmpty(cell($sheet, $r, $map['technicians_on_site'])) : null,
            'branch_location' => isset($map['branch_location']) ? nullIfEmpty(cell($sheet, $r, $map['branch_location'])) : null,
            'total_technicians' => isset($map['total_technicians']) ? nullIfEmpty(cell($sheet, $r, $map['total_technicians'])) : null,
            'remarks' => isset($map['remarks']) ? nullIfEmpty(cell($sheet, $r, $map['remarks'])) : null,
            'source_sheet' => $sheetName,
        ];

        $rows[] = $row;
    }

    fwrite(STDOUT, sprintf("%s: extracted through row %d\n", $sheetName, $highestRow));
}

file_put_contents(
    $outPath,
    json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n"
);

fwrite(STDOUT, sprintf("Wrote %d rows to %s\n", count($rows), $outPath));
