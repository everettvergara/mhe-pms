<?php

namespace App\Services\FscWebImport;

use App\Enums\FscWebImportPhase;
use App\Enums\FscWebImportStatus;
use App\Models\MheDowntimeImportBatch;

class FscWebImportProgressReporter
{
    /** @var list<FscWebImportPhase> */
    protected array $activePhases;

    /** @var array<string, array{start: int, weight: int}> */
    protected array $phaseOffsets = [];

    protected ?FscWebImportPhase $currentPhase = null;

    protected int $lastPersistAt = 0;

    protected int $ticksSincePersist = 0;

    /**
     * @param  list<FscWebImportPhase>  $activePhases
     */
    public function __construct(
        protected string $batchId,
        array $activePhases,
    ) {
        $this->activePhases = $activePhases;
        $this->buildPhaseOffsets();
    }

    public static function forOptions(string $batchId, bool $importUsers, bool $importDowntimes): self
    {
        $phases = [FscWebImportPhase::Purging];

        if ($importUsers) {
            $phases[] = FscWebImportPhase::ImportingUsers;
        }

        if ($importDowntimes) {
            $phases[] = FscWebImportPhase::ExportingDowntimes;
            $phases[] = FscWebImportPhase::ImportingDowntimes;
            $phases[] = FscWebImportPhase::ImportingActionPlans;
            $phases[] = FscWebImportPhase::ImportingAttachments;
        }

        $phases[] = FscWebImportPhase::Finalizing;

        return new self($batchId, $phases);
    }

    public function markRunning(): void
    {
        $this->updateBatch([
            'status' => FscWebImportStatus::Running->value,
            'progress_percent' => 0,
            'status_message' => 'Starting import…',
        ]);
    }

    public function startPhase(FscWebImportPhase $phase, int $total = 0, ?string $message = null): void
    {
        $this->currentPhase = $phase;
        $this->ticksSincePersist = 0;

        $this->persist(
            processed: 0,
            total: $total,
            message: $message ?? $phase->label(),
            force: true,
        );
    }

    public function tick(int $processed, ?string $message = null, ?int $total = null): void
    {
        $this->ticksSincePersist++;

        $this->persist(
            processed: $processed,
            total: $total,
            message: $message,
        );
    }

    /**
     * @param  array<string, int|string>  $result
     */
    public function complete(array $result): void
    {
        $this->updateBatch([
            'status' => FscWebImportStatus::Completed->value,
            'phase' => FscWebImportPhase::Finalizing->value,
            'progress_percent' => 100,
            'processed_count' => 1,
            'total_count' => 1,
            'status_message' => 'Import complete.',
            'result' => $result,
            'users' => $result['users'] ?? 0,
            'users_purged' => $result['users_purged'] ?? 0,
            'downtimes_purged' => $result['downtimes_purged'] ?? 0,
            'action_plans_purged' => $result['action_plans_purged'] ?? 0,
            'downtimes' => $result['downtimes'] ?? 0,
            'action_plans' => $result['action_plans'] ?? 0,
            'downtime_attachments' => $result['downtime_attachments'] ?? 0,
            'action_plan_attachments' => $result['action_plan_attachments'] ?? 0,
            'warnings' => $result['warnings'] ?? 0,
            'errors' => $result['errors'] ?? 0,
        ]);
    }

    public function fail(string $message): void
    {
        $this->updateBatch([
            'status' => FscWebImportStatus::Failed->value,
            'status_message' => 'Import failed.',
            'error_message' => $message,
        ], force: true);
    }

    protected function persist(int $processed, ?int $total, ?string $message, bool $force = false): void
    {
        $now = time();

        if (! $force && $this->ticksSincePersist < 50 && ($now - $this->lastPersistAt) < 1) {
            return;
        }

        $phase = $this->currentPhase;
        $percent = $phase ? $this->percentFor($phase, $processed, $total) : 0;

        $payload = [
            'phase' => $phase?->value,
            'progress_percent' => min(99, $percent),
            'processed_count' => $processed,
        ];

        if ($total !== null) {
            $payload['total_count'] = $total;
        }

        if ($message !== null) {
            $payload['status_message'] = $message;
        }

        $this->updateBatch($payload, force: $force || $this->lastPersistAt === 0);
        $this->lastPersistAt = $now;
        $this->ticksSincePersist = 0;
    }

    protected function percentFor(FscWebImportPhase $phase, int $processed, ?int $total): int
    {
        $offset = $this->phaseOffsets[$phase->value]['start'] ?? 0;
        $weight = $this->phaseOffsets[$phase->value]['weight'] ?? 0;

        if ($weight <= 0) {
            return $offset;
        }

        if ($total === null || $total <= 0) {
            return $offset;
        }

        $ratio = min(1, $processed / $total);

        return (int) round($offset + ($weight * $ratio));
    }

    protected function buildPhaseOffsets(): void
    {
        $totalWeight = array_sum(array_map(fn (FscWebImportPhase $phase) => $phase->weight(), $this->activePhases));

        if ($totalWeight <= 0) {
            return;
        }

        $cursor = 0;

        foreach ($this->activePhases as $phase) {
            $weight = (int) round(($phase->weight() / $totalWeight) * 100);

            $this->phaseOffsets[$phase->value] = [
                'start' => $cursor,
                'weight' => $weight,
            ];

            $cursor += $weight;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function updateBatch(array $attributes, bool $force = false): void
    {
        MheDowntimeImportBatch::query()
            ->where('batch_id', $this->batchId)
            ->update($attributes);
    }
}
