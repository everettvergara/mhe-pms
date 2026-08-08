<?php

namespace App\Services\MheDowntimeImport\Contracts;

use App\Enums\MheDowntimeImportSource;
use App\Services\MheDowntimeImport\MheDowntimeImportPayload;

interface MheDowntimeSourceReader
{
    public function source(): MheDowntimeImportSource;

    /**
     * @param  array<string, mixed>  $config
     */
    public function read(array $config): MheDowntimeImportPayload;

    /**
     * @param  array<string, mixed>  $config
     */
    public function summarizeSource(array $config): string;
}
