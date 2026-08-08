<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\MheInventory;
use App\Models\MheType;
use App\Models\Site;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MheInventorySeeder extends Seeder
{
    /** @var array<string, string> */
    private array $providerAliases = [
        'TOTOTA' => 'Toyota',
        'TOTOYA' => 'Toyota',
        'TOYOTA' => 'Toyota',
        'BOIENG' => 'Boeing',
        'BOEING' => 'Boeing',
        'GLOBAL' => 'Global',
        'ASIA GLOBAL (GEMHI)' => 'GEMHI',
        'GEMHI' => 'GEMHI',
        'JEBSEN AND JESSEN' => 'Jebsen and Jessen',
        'JEBSEN' => 'Jebsen and Jessen',
        'JUNG HEINRICH' => 'Jungheinrich',
        'JUNGHENRICH' => 'Jungheinrich',
        'JUNGHIENRICH' => 'Jungheinrich',
        'JUNGHIENRIECH' => 'Jungheinrich',
        'JHUNGHEINRICH' => 'Jungheinrich',
        'LINDE' => 'Linde',
        'CAPITAL INDUSTRIES' => 'Capital Industries',
        'CII' => 'CII',
        'CROWN' => 'Crown',
        'HII' => 'HII',
        'MITSUBISHI' => 'Mitsubishi',
        'MULTICO' => 'Multico',
        'OWNED' => 'Owned',
        'N/A' => 'N/A',
        'SKOBE' => 'Skobe',
        'TCM' => 'TCM',
        'YALE' => 'Yale',
    ];

    public function run(): void
    {
        $this->ensureDefaultSite();

        // Fresh load from Excel fixture — avoid duplicate rows when site matching changes
        MheInventory::withTrashed()->forceDelete();

        $rows = json_decode(File::get(database_path('data/mhe_inventories.json')), true, 512, JSON_THROW_ON_ERROR);

        $sites = Site::query()->get(['id', 'site_code', 'site_name']);
        $defaultSite = Site::query()->where('site_code', 'default')->firstOrFail();
        $suppliers = Supplier::withTrashed()->get(['id', 'supplier_code', 'supplier_name', 'deleted_at']);
        $types = MheType::withTrashed()->get(['id', 'code', 'description', 'deleted_at']);

        foreach ($rows as $row) {
            $equipmentType = $this->cleanNullable($row['equipment_type'] ?? null);
            $unitNo = $this->cleanNullable($row['unit_no'] ?? null);

            // Heuristic: DISTRICT 1 leftover unit-as-type (MPC####) — infer type from prefix
            if ($equipmentType === null && $unitNo !== null && preg_match('/^(MPC|RM|PC|PPT|CB|RT|LT|ST|JL)/i', $unitNo, $m)) {
                $equipmentType = strtoupper($m[1]);
            }

            $mheType = $this->resolveType($equipmentType, $types);
            $siteId = $this->resolveSiteId($row['site'] ?? '', $sites, $defaultSite->id);
            $provider = $this->normalizeProvider($row['provider'] ?? null);
            $supplier = $this->resolveSupplier($provider, $suppliers);

            $payload = [
                'district' => $this->cleanNullable($row['district'] ?? null),
                'site' => $this->cleanNullable($row['site'] ?? null),
                'site_id' => $siteId,
                'provider' => $provider,
                'supplier_id' => $supplier?->id,
                'brand' => $this->cleanNullable($row['brand'] ?? null),
                'model' => null,
                'equipment_type' => $equipmentType,
                'mhe_type_id' => $mheType?->id,
                'unit_role' => 'Primary',
                'equipment_status' => $this->normalizeStatus($row['equipment_status_raw'] ?? null),
                'client_fsc' => $this->cleanNullable($row['client_fsc'] ?? null),
                'years_in_service' => $this->cleanNullable($row['years_in_service'] ?? null),
                'total_kl_run' => $this->cleanNullable($row['total_kl_run'] ?? null),
                'total_down_hours' => $this->cleanNullable($row['total_down_hours'] ?? null),
                'battery_unit_no' => $this->cleanNullable($row['battery_unit_no'] ?? null),
                'battery_years' => $this->cleanNullable($row['battery_years'] ?? null),
                'battery_man_count' => $this->cleanNullable($row['battery_man_count'] ?? null),
                'technicians_on_site' => $this->cleanNullable($row['technicians_on_site'] ?? null),
                'branch_location' => $this->cleanNullable($row['branch_location'] ?? null),
                'total_technicians' => $this->cleanNullable($row['total_technicians'] ?? null),
                'remarks' => $this->cleanNullable($row['remarks'] ?? null),
            ];

            if ($unitNo !== null) {
                MheInventory::query()->updateOrCreate(
                    [
                        'site_id' => $siteId,
                        'unit_no' => $unitNo,
                    ],
                    $payload,
                );
            } else {
                MheInventory::query()->updateOrCreate(
                    [
                        'site_id' => $siteId,
                        'unit_no' => null,
                        'equipment_type' => $equipmentType,
                        'brand' => $payload['brand'],
                        'provider' => $provider,
                    ],
                    array_merge($payload, ['unit_no' => null]),
                );
            }
        }

        // Soft-delete ad-hoc types no longer referenced (e.g. bad compact codes)
        $usedTypeIds = MheInventory::query()->whereNotNull('mhe_type_id')->distinct()->pluck('mhe_type_id');
        MheType::query()
            ->whereNotIn('code', ['CB', 'RT', 'LT', 'ST', 'JL'])
            ->whereNotIn('id', $usedTypeIds)
            ->delete();
    }

    private function ensureDefaultSite(): void
    {
        $district = District::query()->orderBy('id')->first();

        if (! $district) {
            throw new \RuntimeException('Cannot seed default site: no districts found. Run DistrictSeeder first.');
        }

        Site::query()->updateOrCreate(
            ['site_code' => 'default'],
            [
                'site_name' => 'default',
                'district_id' => $district->id,
                'region_id' => null,
                'description' => 'Fallback site for unmatched MHE inventory Excel rows',
                'status' => RecordStatus::Active->value,
            ],
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, MheType>  $types
     */
    private function resolveType(?string $equipmentType, $types): ?MheType
    {
        if ($equipmentType === null || strcasecmp($equipmentType, 'N/A') === 0) {
            return null;
        }

        $mappedCode = $this->mapKnownTypeCode($equipmentType);

        if ($mappedCode !== null) {
            $existing = $types->first(fn (MheType $t) => strcasecmp($t->code, $mappedCode) === 0);
            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }

                return $existing;
            }
        }

        $code = $mappedCode ?? $this->makeTypeCode($equipmentType);
        $description = $this->makeTypeDescription($equipmentType, $code);

        $type = MheType::withTrashed()->updateOrCreate(
            ['code' => $code],
            [
                'description' => $description,
                'status' => RecordStatus::Active->value,
            ],
        );

        if ($type->trashed()) {
            $type->restore();
        }

        $types->push($type);

        return $type;
    }

    private function mapKnownTypeCode(string $raw): ?string
    {
        $n = Str::upper(preg_replace('/\s+/', ' ', trim($raw)) ?? '');
        $compact = preg_replace('/[^A-Z0-9]/', '', $n) ?? '';

        if (preg_match('/(JACKLIFT|JACK LIFT|ELECTRIC JACKLIFT|MOTORIZED JACKLIFT)/', $n) || $compact === 'JL') {
            return 'JL';
        }
        if (
            str_contains($n, 'SHORT TRANSPORTER')
            || str_contains($n, 'TRANSPORTER (SHORT)')
            || str_contains($n, 'JT SHORT')
            || $compact === 'ST'
            || $compact === 'TRANSPORTERSHORT'
        ) {
            return 'ST';
        }
        if (
            str_contains($n, 'LONG TRANSPORTER')
            || str_contains($n, 'LONG TRANSPORT')
            || str_contains($n, 'TRANSPORTER (LONG)')
            || str_contains($n, 'JT LONG')
            || $compact === 'LT'
            || $compact === 'JTL'
            || $compact === 'TRANSPORTERLONG'
        ) {
            return 'LT';
        }
        if (str_contains($n, 'REACH') || str_contains($n, 'REACHTRUCK') || $compact === 'RT' || preg_match('/\bRT\b/', $n)) {
            return 'RT';
        }
        if (
            str_contains($n, 'COUNTER')
            || str_contains($n, 'PUSH PULL')
            || str_contains($n, 'PUSHPULL')
            || $compact === 'CB'
            || $compact === 'PP'
            || preg_match('/\bCB\b/', $n)
            || preg_match('/\bPP\b/', $n)
        ) {
            return 'CB';
        }

        // Bare transporter → create as TR (unmapped new type), not forced to LT/ST
        return null;
    }

    private function makeTypeCode(string $raw): string
    {
        $n = Str::upper(preg_replace('/[^A-Za-z0-9]+/', ' ', $raw) ?? '');
        $n = trim(preg_replace('/\s+/', ' ', $n) ?? '');

        if ($n === 'TRANSPORTER') {
            return 'TR';
        }
        if (str_contains($n, 'ORDER PICKER') || str_contains($n, 'PANTO')) {
            return 'OP';
        }
        if (str_contains($n, 'TOWING')) {
            return 'TT';
        }
        if (str_contains($n, 'PALLET MOVER') || $n === 'MOVER') {
            return 'MV';
        }
        if (str_starts_with($n, 'MPC')) {
            return 'MPC';
        }
        if (str_starts_with($n, 'PPT')) {
            return 'PPT';
        }

        $compact = str_replace(' ', '', $n);

        return Str::limit($compact !== '' ? $compact : 'UNK', 20, '');
    }

    private function makeTypeDescription(string $raw, string $code): string
    {
        return match ($code) {
            'TR' => 'Transporter',
            'OP' => 'Order Picker / Panto',
            'TT' => 'Towing Truck',
            'MV' => 'Mover',
            'MPC' => 'MPC',
            'PPT' => 'PPT',
            default => Str::title(strtolower(trim($raw))),
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Site>  $sites
     */
    private function resolveSiteId(string $excelSite, $sites, int $defaultSiteId): int
    {
        $needle = $this->normalizeKey($excelSite);
        if ($needle === '') {
            return $defaultSiteId;
        }

        $needles = array_values(array_unique(array_filter([
            $needle,
            preg_replace('/^FSC/', '', $needle) ?: null,
            preg_replace('/DC$/', '', $needle) ?: null,
        ])));

        $bestId = null;
        $bestScore = 0.0;

        foreach ($sites as $site) {
            foreach ([$site->site_code, $site->site_name] as $candidate) {
                $hay = $this->normalizeKey((string) $candidate);
                if ($hay === '' || $hay === 'DEFAULT') {
                    continue;
                }

                foreach ($needles as $n) {
                    if ($hay === $n) {
                        return (int) $site->id;
                    }

                    // Containment boost for partial labels (avoid tiny needles like D7)
                    if (strlen($n) >= 4 && (str_contains($hay, $n) || str_contains($n, $hay))) {
                        $lenRatio = min(strlen($n), strlen($hay)) / max(strlen($n), strlen($hay));
                        $percent = 70.0 + (30.0 * $lenRatio);
                    } else {
                        similar_text($n, $hay, $percent);
                    }

                    if ($percent > $bestScore) {
                        $bestScore = $percent;
                        $bestId = (int) $site->id;
                    }
                }
            }
        }

        if ($bestId !== null && $bestScore >= 75.0) {
            return $bestId;
        }

        return $defaultSiteId;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Supplier>  $suppliers
     */
    private function resolveSupplier(?string $provider, $suppliers): ?Supplier
    {
        if ($provider === null || $provider === '') {
            return null;
        }

        $needle = $this->normalizeKey($provider);

        $best = null;
        $bestScore = 0.0;

        foreach ($suppliers as $supplier) {
            foreach ([$supplier->supplier_code, $supplier->supplier_name] as $candidate) {
                $hay = $this->normalizeKey((string) $candidate);
                if ($hay === '') {
                    continue;
                }
                if ($hay === $needle) {
                    if ($supplier->trashed()) {
                        $supplier->restore();
                    }

                    return $supplier;
                }

                similar_text($needle, $hay, $percent);
                if ($percent > $bestScore) {
                    $bestScore = $percent;
                    $best = $supplier;
                }
            }
        }

        if ($best !== null && $bestScore >= 85.0) {
            if ($best->trashed()) {
                $best->restore();
            }

            return $best;
        }

        $code = Str::upper(Str::limit(preg_replace('/[^A-Za-z0-9]/', '', $provider) ?? 'SUP', 10, ''));
        if ($code === '') {
            $code = 'SUP'.substr(md5($provider), 0, 6);
        }

        // Ensure unique supplier_code
        $base = $code;
        $i = 1;
        while ($suppliers->contains(fn (Supplier $s) => strcasecmp($s->supplier_code, $code) === 0)) {
            $code = Str::limit($base.$i, 10, '');
            $i++;
        }

        $supplier = Supplier::withTrashed()->updateOrCreate(
            ['supplier_code' => $code],
            [
                'supplier_name' => $provider,
                'status' => RecordStatus::Active->value,
            ],
        );

        if ($supplier->trashed()) {
            $supplier->restore();
        }

        $suppliers->push($supplier);

        return $supplier;
    }

    private function normalizeProvider(?string $provider): ?string
    {
        $provider = $this->cleanNullable($provider);
        if ($provider === null) {
            return null;
        }

        $key = Str::upper(preg_replace('/\s+/', ' ', $provider) ?? '');

        return $this->providerAliases[$key] ?? $provider;
    }

    private function normalizeStatus(?string $raw): string
    {
        if ($raw === null || trim($raw) === '') {
            return RecordStatus::Active->value;
        }

        $n = Str::upper(trim($raw));

        if (in_array($n, ['DOWN', 'FULL OUT', 'FULLOUT', 'INACTIVE'], true)) {
            return RecordStatus::Inactive->value;
        }

        return RecordStatus::Active->value;
    }

    private function normalizeKey(string $value): string
    {
        $value = Str::upper($value);
        $value = preg_replace('/[^A-Z0-9]+/', '', $value) ?? '';

        return $value;
    }

    private function cleanNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $value === '' ? null : $value;
    }
}
