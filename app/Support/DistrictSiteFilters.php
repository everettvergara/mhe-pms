<?php

namespace App\Support;

use App\Models\Site;

class DistrictSiteFilters
{
    public static function scopedSiteId(mixed $districtId, mixed $siteId): mixed
    {
        if (blank($districtId) || blank($siteId)) {
            return null;
        }

        $belongsToDistrict = Site::query()
            ->whereKey($siteId)
            ->where('district_id', $districtId)
            ->exists();

        return $belongsToDistrict ? $siteId : null;
    }
}
