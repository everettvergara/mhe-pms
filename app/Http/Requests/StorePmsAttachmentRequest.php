<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StorePmsAttachmentRequest extends FormRequest
{
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
        $mimes = config('pms.attachments.allowed_mimes', ['jpg', 'jpeg', 'png', 'webp']);
        $maxPerRecord = (int) config('pms.attachments.max_per_record', 10);

        return [
            'files' => ['required', 'array', 'min:1', 'max:'.$maxPerRecord],
            'files.*' => [
                'required',
                File::types($mimes)
                    ->image()
                    ->max($this->maxFileSizeKb()),
            ],
        ];
    }

    protected function maxFileSizeKb(): int
    {
        $configured = config('pms.attachments.max_file_size_kb');

        if ($configured === null || $configured === '') {
            return self::DEFAULT_MAX_KB;
        }

        return max(1, (int) $configured);
    }
}
