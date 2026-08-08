<?php

namespace App\Services\EagleEye;

use App\Models\EagleEyeImportLog;

class EagleEyeImportLogger
{
    protected int $warnings = 0;

    protected int $errors = 0;

    public function __construct(
        protected string $batchId,
        protected bool $persist = true,
    ) {}

    public function batchId(): string
    {
        return $this->batchId;
    }

    public function warnings(): int
    {
        return $this->warnings;
    }

    public function errors(): int
    {
        return $this->errors;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function info(string $message, ?string $entityType = null, ?int $legacyId = null, array $context = []): void
    {
        $this->write('info', $message, $entityType, $legacyId, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function warning(string $entityType, int $legacyId, string $message, array $context = []): void
    {
        $this->warnings++;
        $this->write('warning', $message, $entityType, $legacyId, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function error(string $entityType, int $legacyId, string $message, array $context = []): void
    {
        $this->errors++;
        $this->write('error', $message, $entityType, $legacyId, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function write(string $level, string $message, ?string $entityType, ?int $legacyId, array $context): void
    {
        if (! $this->persist) {
            return;
        }

        EagleEyeImportLog::query()->create([
            'batch_id' => $this->batchId,
            'level' => $level,
            'entity_type' => $entityType,
            'legacy_id' => $legacyId,
            'message' => $message,
            'context' => $context === [] ? null : $context,
        ]);
    }
}
