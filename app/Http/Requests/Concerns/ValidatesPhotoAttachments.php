<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rules\File;

trait ValidatesPhotoAttachments
{
    /**
     * @return array<string, mixed>
     */
    protected function photoAttachmentRules(string $configKey = 'pms.attachments', bool $required = true): array
    {
        $mimes = config("{$configKey}.allowed_mimes", ['jpg', 'jpeg', 'png', 'webp']);
        $maxPerRecord = (int) config("{$configKey}.max_per_record", 10);
        $maxFileSizeKb = max(1, (int) config("{$configKey}.max_file_size_kb", 25600));

        $filesRule = $required
            ? ['required', 'array', 'min:1', 'max:'.$maxPerRecord]
            : ['sometimes', 'array', 'max:'.$maxPerRecord];

        return [
            'files' => $filesRule,
            'files.*' => [
                'required',
                File::types($mimes)
                    ->image()
                    ->max($maxFileSizeKb),
            ],
        ];
    }
}
