<?php

namespace App\Http\Requests\Concerns;

use App\Services\MheInventoryLookupService;
use App\Services\UserDataScopeService;
use Illuminate\Validation\Validator;

trait ValidatesPmsInventoryUnit
{
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if ($user === null) {
                return;
            }

            $siteId = (int) $this->input('site_id', 0);

            if ($siteId === 0) {
                return;
            }

            $scopeService = app(UserDataScopeService::class);

            if (! $scopeService->canAccessSite($user, $siteId)) {
                $validator->errors()->add('site_id', 'The selected site is not assigned to your account.');

                return;
            }

            $unitNumber = trim((string) $this->input('unit_number', ''));

            if ($unitNumber === '') {
                return;
            }

            $lookupService = app(MheInventoryLookupService::class);
            $inventory = $lookupService->findScopedInventory($user, $siteId, $unitNumber, activeOnly: true);

            if ($inventory === null) {
                $validator->errors()->add('unit_number', 'The selected unit is not available in your assigned inventory.');
            }
        });
    }
}
