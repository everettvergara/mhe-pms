<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesPhotoAttachments;
use App\Services\MheDowntimeService;
use App\Services\UserDataScopeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMheDowntimeRequest extends FormRequest
{
    use ValidatesPhotoAttachments;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->baseRules();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->has('site_id')) {
                return;
            }

            $siteId = (int) $this->input('site_id');

            if ($siteId === 0) {
                $validator->errors()->add('site_id', 'Please select a valid site from the list.');

                return;
            }

            if (! app(UserDataScopeService::class)->canAccessSite($this->user(), $siteId)) {
                $validator->errors()->add('site_id', 'The selected site is not assigned to you.');

                return;
            }

            $supplierId = $this->input('supplier_id');

            if (filled($supplierId)) {
                $assignedSupplierIds = $this->user()->assignedSupplierIds();

                if ($assignedSupplierIds !== [] && ! in_array((int) $supplierId, $assignedSupplierIds, true)) {
                    $validator->errors()->add('supplier_id', 'The selected supplier is not assigned to you.');
                }
            }

            $refUnitNo = trim((string) $this->input('ref_unit_no', ''));

            if ($refUnitNo === '') {
                if (blank($this->input('supplier_id'))) {
                    $validator->errors()->add(
                        'supplier_id',
                        'Supplier is required when ref unit no is not provided.',
                    );
                }

                return;
            }

            $inventory = MheDowntimeService::findInventoryForUnit($siteId, $refUnitNo);

            if ($inventory === null && blank($this->input('supplier_id'))) {
                $validator->errors()->add(
                    'supplier_id',
                    'Supplier is required when the unit number is not in MHE inventory.',
                );
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function baseRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'site_id' => ['required', 'exists:sites,id'],
            'mhe_type_id' => ['required', 'exists:mhe_types,id'],
            'mhe_category_id' => ['required', 'exists:mhe_categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'ref_unit_no' => ['nullable', 'string', 'max:100'],
            'date_of_incident' => ['required', 'date'],
            'uptime' => ['nullable', 'date', 'after_or_equal:date_of_incident'],
            'root_cause' => ['nullable', 'string', 'max:4000'],
            'description' => ['nullable', 'string', 'max:4000'],
            'w_spare_unit' => ['sometimes', 'boolean'],
            ...$this->photoAttachmentRules('mhe_downtime.attachments', required: false),
        ];
    }
}
