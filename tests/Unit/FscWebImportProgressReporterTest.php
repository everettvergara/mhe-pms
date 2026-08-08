<?php

namespace Tests\Unit;

use App\Enums\FscWebImportPhase;
use App\Models\MheDowntimeImportBatch;
use App\Services\FscWebImport\FscWebImportProgressReporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FscWebImportProgressReporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_percent_advances_within_active_phases(): void
    {
        $batch = MheDowntimeImportBatch::query()->create([
            'batch_id' => 'test-batch-1234',
            'source' => 'eagle_eye_mysql',
            'source_summary' => 'test',
            'dry_run' => true,
            'status' => 'pending',
        ]);

        $reporter = FscWebImportProgressReporter::forOptions('test-batch-1234', false, true);
        $reporter->markRunning();
        $reporter->startPhase(FscWebImportPhase::ImportingDowntimes, 100, 'Importing downtimes…');
        $reporter->tick(50, 'Halfway', 100);

        $batch->refresh();

        $this->assertSame('running', $batch->status->value);
        $this->assertSame('importing_downtimes', $batch->phase);
        $this->assertGreaterThan(0, $batch->progress_percent);
        $this->assertLessThan(100, $batch->progress_percent);
        $this->assertSame(100, $batch->total_count);
    }
}
