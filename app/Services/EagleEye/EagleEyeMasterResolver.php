<?php

namespace App\Services\EagleEye;

use App\Enums\DowntimeActionPlanStatus;
use App\Enums\DowntimeStatus;
use App\Enums\RecordStatus;
use App\Models\EagleEyeImportLog;
use App\Models\EagleEyeImportMap;
use App\Models\MheCategory;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Models\MheInventory;
use App\Models\MheType;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class EagleEyeMasterResolver
{
    /** @var array<string, array<int, string>> */
    protected array $legacyCodeMaps = [];

    /** @var array<string, int> */
    protected array $localIdCache = [];

    protected ?Site $fallbackSite = null;

    protected ?MheType $fallbackType = null;

    protected ?MheCategory $fallbackCategory = null;

    protected ?User $fallbackUser = null;

    public function __construct(
        protected EagleEyeImportLogger $logger,
    ) {}

    public function loadLegacyMaps(?string $seedPath = null): void
    {
        $seedPath ??= config('eagle_eye.seed_path');

        $this->legacyCodeMaps = [
            'site' => $this->readMapFile("{$seedPath}/ee_sites_by_id.json"),
            'mhe_type' => $this->readMapFile("{$seedPath}/ee_mhe_types_by_id.json"),
            'mhe_category' => $this->readMapFile("{$seedPath}/ee_mhe_categories_by_id.json"),
        ];
    }

    /**
     * @param  array<string, string>  $maps
     */
    public function setLegacyMaps(array $maps): void
    {
        $this->legacyCodeMaps = [
            'site' => $maps['site'] ?? [],
            'mhe_type' => $maps['mhe_type'] ?? [],
            'mhe_category' => $maps['mhe_category'] ?? [],
        ];
    }

    public function resolveSiteId(int $legacySiteId): int
    {
        return $this->resolveMappedId('site', $legacySiteId, function (?string $code) use ($legacySiteId) {
            if ($code === null) {
                return $this->fallbackSite()->id;
            }

            $site = Site::query()->where('site_code', $code)->first();

            if ($site) {
                return $site->id;
            }

            $this->logger->warning('site', $legacySiteId, "Site code {$code} not found in mhe-pms.");

            return $this->fallbackSite()->id;
        });
    }

    public function resolveMheTypeId(int $legacyTypeId): int
    {
        return $this->resolveMappedId('mhe_type', $legacyTypeId, function (?string $code) use ($legacyTypeId) {
            if ($code === null) {
                return $this->fallbackType()->id;
            }

            $type = MheType::query()->where('code', $code)->first();

            if ($type) {
                return $type->id;
            }

            $this->logger->warning('mhe_type', $legacyTypeId, "MHE type code {$code} not found in mhe-pms.");

            return $this->fallbackType()->id;
        });
    }

    public function resolveMheCategoryId(int $legacyCategoryId): int
    {
        return $this->resolveMappedId('mhe_category', $legacyCategoryId, function (?string $code) use ($legacyCategoryId) {
            if ($code === null) {
                return $this->fallbackCategory()->id;
            }

            $category = MheCategory::query()->where('code', $code)->first();

            if ($category) {
                return $category->id;
            }

            $this->logger->warning('mhe_category', $legacyCategoryId, "MHE category code {$code} not found in mhe-pms.");

            return $this->fallbackCategory()->id;
        });
    }

    public function resolveUserId(?int $legacyUserId): int
    {
        if ($legacyUserId === null) {
            return $this->fallbackUser()->id;
        }

        return $this->resolveMappedId('user', $legacyUserId, function () use ($legacyUserId) {
            $this->logger->warning('user', $legacyUserId, "Eagle Eye user {$legacyUserId} not mapped; using import user.");

            return $this->fallbackUser()->id;
        });
    }

    /**
     * @return array{inventory_id: ?int, supplier_id: ?int}
     */
    public function resolveInventory(int $siteId, ?string $refUnitNo): array
    {
        $unit = trim((string) $refUnitNo);

        if ($unit === '') {
            $unit = config('eagle_eye.defaults.unknown_unit');
            $this->logger->warning('mhe_inventory', 0, 'Missing ref_unit_no; using UNKNOWN unit.');
        }

        $inventory = MheInventory::query()
            ->where('site_id', $siteId)
            ->whereRaw('LOWER(unit_no) = ?', [strtolower($unit)])
            ->first();

        if ($inventory) {
            return [
                'inventory_id' => $inventory->id,
                'supplier_id' => $inventory->supplier_id,
            ];
        }

        $this->logger->warning('mhe_inventory', 0, "No inventory match for unit {$unit} at site {$siteId}.");

        return [
            'inventory_id' => null,
            'supplier_id' => null,
        ];
    }

    public function mapDowntimeStatus(?int $statusId): DowntimeStatus
    {
        $mapped = config('eagle_eye.status_map')[$statusId] ?? 'Draft';

        return DowntimeStatus::from($mapped);
    }

    public function mapActionPlanStatus(?int $statusId): DowntimeActionPlanStatus
    {
        $mapped = config('eagle_eye.action_plan_status_map')[$statusId] ?? 'Pending';

        return DowntimeActionPlanStatus::from($mapped);
    }

    /**
     * @param  callable(?string): int  $resolver
     */
    protected function resolveMappedId(string $entityType, int $legacyId, callable $resolver): int
    {
        $cacheKey = "{$entityType}:{$legacyId}";

        if (isset($this->localIdCache[$cacheKey])) {
            return $this->localIdCache[$cacheKey];
        }

        $existing = EagleEyeImportMap::query()
            ->where('entity_type', $entityType)
            ->where('legacy_id', $legacyId)
            ->first();

        if ($existing) {
            return $this->localIdCache[$cacheKey] = (int) $existing->local_id;
        }

        $legacyCode = $this->legacyCodeMaps[$entityType][$legacyId] ?? null;
        $localId = $resolver($legacyCode);

        EagleEyeImportMap::query()->updateOrCreate(
            [
                'entity_type' => $entityType,
                'legacy_id' => $legacyId,
            ],
            [
                'local_id' => $localId,
                'legacy_code' => $legacyCode,
            ],
        );

        return $this->localIdCache[$cacheKey] = $localId;
    }

    /**
     * @return array<string, string>
     */
    protected function readMapFile(string $path): array
    {
        if (! File::exists($path)) {
            return [];
        }

        /** @var array<string, string> $data */
        $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    protected function fallbackSite(): Site
    {
        return $this->fallbackSite ??= Site::query()
            ->where('site_code', config('eagle_eye.defaults.site_code'))
            ->firstOrFail();
    }

    protected function fallbackType(): MheType
    {
        return $this->fallbackType ??= MheType::query()
            ->where('code', config('eagle_eye.defaults.mhe_type_code'))
            ->firstOrFail();
    }

    protected function fallbackCategory(): MheCategory
    {
        return $this->fallbackCategory ??= MheCategory::query()
            ->where('code', config('eagle_eye.defaults.mhe_category_code'))
            ->firstOrFail();
    }

    protected function fallbackUser(): User
    {
        return $this->fallbackUser ??= User::query()
            ->where('email', config('eagle_eye.defaults.import_user_email'))
            ->firstOrFail();
    }
}
