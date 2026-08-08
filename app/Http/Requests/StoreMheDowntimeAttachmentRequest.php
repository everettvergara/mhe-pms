<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesPhotoAttachments;
use Illuminate\Foundation\Http\FormRequest;

class StoreMheDowntimeAttachmentRequest extends FormRequest
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
        return $this->photoAttachmentRules('mhe_downtime.attachments');
    }
}
