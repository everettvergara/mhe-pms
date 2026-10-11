<?php

namespace App\Services\FscWebImport;

use App\Models\District;
use App\Models\EagleEyeImportMap;
use App\Models\MheCategory;
use App\Models\MheInventory;
use App\Models\MheType;
use App\Models\Site;
use App\Models\User;

class FscDowntimeMasterMap
{
    public const REASON_UNMAPPED_SITE = 'unmapped_site';

    public const REASON_DISTRICT_MISMATCH = 'district_mismatch';

    public const REASON_UNMAPPED_TYPE = 'unmapped_type';

    public const REASON_UNMAPPED_CATEGORY = 'unmapped_category';

    public const REASON_CREATOR = 'creator_not_migrated';

    public const REASON_INVALID_DATE = 'invalid_date';

    public const REASON_DELETED_MASTER = 'deleted_master';

    /** @var array<string, string> */
    public const REASON_LABELS = [
        self::REASON_UNMAPPED_SITE => 'Unmapped site',
        self::REASON_DISTRICT_MISMATCH => 'District mismatch',
        self::REASON_UNMAPPED_TYPE => 'Unmapped MHE type',
        self::REASON_UNMAPPED_CATEGORY => 'Unmapped category',
        self::REASON_CREATOR => 'Creator not migrated',
        self::REASON_INVALID_DATE => 'Invalid date',
        self::REASON_DELETED_MASTER => 'Inactive / deleted master',
    ];

    /** @var array<string, District> */
    protected array $districtsByKey = [];

    /** @var array<string, Site> */
    protected array $sitesByKey = [];

    /** @var array<string, MheType> */
    protected array $typesByKey = [];

    /** @var array<string, MheCategory> */
    protected array $categoriesByKey = [];

    /** @var array<int, User> */
    protected array $usersByLegacyId = [];

    /** @var array<string, User> */
    protected array $usersByUsername = [];

    /** @var array<string, MheInventory> */
    protected array $inventoryBySiteUnit = [];

    public function __construct()
    {
        $this->load();
    }

    public static function normalizeKey(?string $value): string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return mb_strtolower($value);
    }

    public function districtForCode(?string $code): ?District
    {
        $key = self::normalizeKey($code);

        if ($key === '') {
            return null;
        }

        return $this->districtsByKey[$key] ?? null;
    }

    /**
     * @return array{id: ?int, code: ?string, name: ?string, reason: ?string, message: ?string}
     */
    public function resolveSite(int $legacySiteId, ?string $legacyCode, string $selectedDistrictCode): array
    {
        $code = trim((string) $legacyCode);
        $site = $this->sitesByKey[self::normalizeKey($code.'-ee'.$legacySiteId)]
            ?? $this->sitesByKey[self::normalizeKey($code)]
            ?? null;

        if ($site === null) {
            return $this->blocked(
                self::REASON_UNMAPPED_SITE,
                $code === ''
                    ? "fsc_web site #{$legacySiteId} has no code in mhe-pms."
                    : "Site {$code} is not in mhe-pms.",
            );
        }

        if ($site->trashed()) {
            return $this->blocked(
                self::REASON_DELETED_MASTER,
                "Site {$site->site_code} is inactive / deleted in mhe-pms.",
                $site->id,
                $site->site_code,
                $site->site_name,
            );
        }

        $district = $this->districtForCode($selectedDistrictCode);

        if ($district === null || (int) $site->district_id !== (int) $district->id) {
            return $this->blocked(
                self::REASON_DISTRICT_MISMATCH,
                "Site {$site->site_code} is in a different mhe-pms district than {$selectedDistrictCode}.",
                $site->id,
                $site->site_code,
                $site->site_name,
            );
        }

        return [
            'id' => $site->id,
            'code' => $site->site_code,
            'name' => $site->site_name,
            'reason' => null,
            'message' => null,
        ];
    }

    /**
     * @return array{id: ?int, code: ?string, name: ?string, reason: ?string, message: ?string}
     */
    public function resolveType(?string $code): array
    {
        return $this->resolveCodedMaster($this->typesByKey, $code, 'MHE type', self::REASON_UNMAPPED_TYPE, 'code', 'description');
    }

    /**
     * @return array{id: ?int, code: ?string, name: ?string, reason: ?string, message: ?string}
     */
    public function resolveCategory(?string $code): array
    {
        return $this->resolveCodedMaster($this->categoriesByKey, $code, 'Category', self::REASON_UNMAPPED_CATEGORY, 'code', 'name');
    }

    /**
     * @return array{id: ?int, code: ?string, name: ?string, reason: ?string, message: ?string}
     */
    public function resolveUser(?int $legacyUserId, ?string $legacyCode): array
    {
        $code = trim((string) $legacyCode);

        if ($legacyUserId !== null && isset($this->usersByLegacyId[$legacyUserId])) {
            return $this->userResult($this->usersByLegacyId[$legacyUserId], $code);
        }

        $byName = $this->usersByUsername[self::normalizeKey($code)] ?? null;

        if ($byName !== null) {
            return $this->userResult($byName, $code);
        }

        $label = $code !== '' ? $code : ($legacyUserId ? '#'.$legacyUserId : 'unknown');

        return $this->blocked(
            self::REASON_CREATOR,
            "Creator {$label} is not in mhe-pms. Run the User Migration Tool first.",
        );
    }

    /**
     * @return array{inventory_id: ?int, supplier_id: ?int, matched: bool}
     */
    public function resolveInventory(?int $siteId, ?string $unitNo): array
    {
        $unit = trim((string) $unitNo);

        if ($siteId === null || $unit === '') {
            return ['inventory_id' => null, 'supplier_id' => null, 'matched' => false];
        }

        $inventory = $this->inventoryBySiteUnit[$siteId.'|'.mb_strtolower($unit)] ?? null;

        if ($inventory === null) {
            return ['inventory_id' => null, 'supplier_id' => null, 'matched' => false];
        }

        return [
            'inventory_id' => $inventory->id,
            'supplier_id' => $inventory->supplier_id,
            'matched' => true,
        ];
    }

    protected function load(): void
    {
        foreach (District::withTrashed()->get() as $district) {
            $this->districtsByKey[self::normalizeKey($district->district_code)] = $district;
        }

        foreach (Site::withTrashed()->get() as $site) {
            $this->sitesByKey[self::normalizeKey($site->site_code)] = $site;
        }

        foreach (MheType::withTrashed()->get() as $type) {
            $this->typesByKey[self::normalizeKey($type->code)] = $type;
        }

        foreach (MheCategory::withTrashed()->get() as $category) {
            $this->categoriesByKey[self::normalizeKey($category->code)] = $category;
        }

        $fallbackId = User::withTrashed()
            ->where('email', config('eagle_eye.defaults.import_user_email'))
            ->value('id');

        $mappedUserIds = [];

        foreach (EagleEyeImportMap::query()->where('entity_type', 'user')->get() as $map) {
            if ($fallbackId !== null && (int) $map->local_id === (int) $fallbackId) {
                continue;
            }

            $mappedUserIds[(int) $map->legacy_id] = (int) $map->local_id;
        }

        $users = User::withTrashed()->get();

        foreach ($users as $user) {
            $this->usersByUsername[self::normalizeKey($user->username)] = $user;
        }

        $usersById = $users->keyBy('id');

        foreach ($mappedUserIds as $legacyId => $localId) {
            $user = $usersById->get($localId);

            if ($user instanceof User) {
                $this->usersByLegacyId[$legacyId] = $user;
            }
        }

        foreach (MheInventory::query()->whereNotNull('site_id')->whereNotNull('unit_no')->get(['id', 'site_id', 'unit_no', 'supplier_id']) as $inventory) {
            $unit = trim((string) $inventory->unit_no);

            if ($unit === '') {
                continue;
            }

            $this->inventoryBySiteUnit[(int) $inventory->site_id.'|'.mb_strtolower($unit)] = $inventory;
        }
    }

    /**
     * @param  array<string, MheType|MheCategory>  $index
     * @return array{id: ?int, code: ?string, name: ?string, reason: ?string, message: ?string}
     */
    protected function resolveCodedMaster(array $index, ?string $code, string $label, string $missingReason, string $codeField, string $nameField): array
    {
        $trimmed = trim((string) $code);
        $master = $index[self::normalizeKey($trimmed)] ?? null;

        if ($master === null) {
            return $this->blocked(
                $missingReason,
                $trimmed === ''
                    ? "{$label} is missing in fsc_web."
                    : "{$label} {$trimmed} is not in mhe-pms.",
            );
        }

        if ($master->trashed()) {
            return $this->blocked(
                self::REASON_DELETED_MASTER,
                "{$label} {$master->{$codeField}} is inactive / deleted in mhe-pms.",
                $master->id,
                (string) $master->{$codeField},
                (string) $master->{$nameField},
            );
        }

        return [
            'id' => $master->id,
            'code' => (string) $master->{$codeField},
            'name' => (string) $master->{$nameField},
            'reason' => null,
            'message' => null,
        ];
    }

    /**
     * @return array{id: ?int, code: ?string, name: ?string, reason: ?string, message: ?string}
     */
    protected function userResult(User $user, string $legacyCode): array
    {
        if ($user->trashed()) {
            return $this->blocked(
                self::REASON_DELETED_MASTER,
                'Creator '.($legacyCode !== '' ? $legacyCode : $user->username).' is inactive / deleted in mhe-pms.',
                $user->id,
                $user->username,
                $user->name,
            );
        }

        return [
            'id' => $user->id,
            'code' => $user->username,
            'name' => $user->name,
            'reason' => null,
            'message' => null,
        ];
    }

    /**
     * @return array{id: ?int, code: ?string, name: ?string, reason: ?string, message: ?string}
     */
    protected function blocked(string $reason, string $message, ?int $id = null, ?string $code = null, ?string $name = null): array
    {
        return [
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'reason' => $reason,
            'message' => $message,
        ];
    }
}
