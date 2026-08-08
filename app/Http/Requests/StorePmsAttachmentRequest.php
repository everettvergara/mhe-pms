<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesPhotoAttachments;
use Illuminate\Foundation\Http\FormRequest;

class StorePmsAttachmentRequest extends FormRequest
{
    use ValidatesPhotoAttachments;

    public const DEFAULT_MAX_KB = 25600;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->photoAttachmentRules('pms.attachments');
    }
}
