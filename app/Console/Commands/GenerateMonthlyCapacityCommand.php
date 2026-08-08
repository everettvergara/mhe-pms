<?php

namespace App\Console\Commands;

use App\Services\MheMonthlyCapacityService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyCapacityCommand extends Command
{
    protected $signature = 'mhe:generate-monthly-capacity
                            {yyyymm? : Year-month as YYYYMM; defaults to current month}
                            {--from= : Start YYYYMM for backfill (inclusive)}
                            {--to= : End YYYYMM for backfill (inclusive)}
                            {--force : Regenerate non-override rows for the month}';

    protected $description = 'Snapshot monthly available hours from active MHE inventory';

    public function handle(MheMonthlyCapacityService $service): int
    {
        $months = $this->monthsToGenerate();

        if ($months === null) {
            return self::FAILURE;
        }

        $totalInserted = 0;
        $totalSkipped = 0;

        foreach ($months as $yyyymm) {
            $result = $service->generateForMonth($yyyymm, (bool) $this->option('force'));

            $totalInserted += $result['inserted'];
            $totalSkipped += $result['skipped'];

            $this->line(sprintf(
                '%d: %d inserted, %d skipped.',
                $yyyymm,
                $result['inserted'],
                $result['skipped'],
            ));
        }

        if (count($months) > 1) {
            $this->info(sprintf(
                'Backfill complete (%d months): %d inserted, %d skipped.',
                count($months),
                $totalInserted,
                $totalSkipped,
            ));
        } else {
            $this->info(sprintf(
                'Monthly capacity for %d: %d inserted, %d skipped.',
                $months[0],
                $totalInserted,
                $totalSkipped,
            ));
        }

        return self::SUCCESS;
    }

    /**
     * @return list<int>|null
     */
    private function monthsToGenerate(): ?array
    {
        $from = $this->option('from');
        $to = $this->option('to');

        if ($from || $to) {
            if (! $from || ! $to) {
                $this->error('Both --from and --to are required for backfill.');

                return null;
            }

            $fromYyyymm = (int) $from;
            $toYyyymm = (int) $to;

            if (! $this->isValidYyyymm($fromYyyymm) || ! $this->isValidYyyymm($toYyyymm) || $fromYyyymm > $toYyyymm) {
                $this->error('Invalid --from/--to range. Use YYYYMM, e.g. --from=202304 --to=202608.');

                return null;
            }

            $months = [];
            $year = intdiv($fromYyyymm, 100);
            $month = $fromYyyymm % 100;

            while ($year * 100 + $month <= $toYyyymm) {
                $months[] = $year * 100 + $month;
                $month++;
                if ($month > 12) {
                    $month = 1;
                    $year++;
                }
            }

            return $months;
        }

        $yyyymm = $this->argument('yyyymm')
            ? (int) $this->argument('yyyymm')
            : (int) Carbon::now(config('mhe.scheduler_timezone', 'Asia/Manila'))->format('Ym');

        if (! $this->isValidYyyymm($yyyymm)) {
            $this->error('Invalid yyyymm. Use format YYYYMM, e.g. 202508.');

            return null;
        }

        return [$yyyymm];
    }

    private function isValidYyyymm(int $yyyymm): bool
    {
        return $yyyymm >= 190001 && $yyyymm % 100 >= 1 && $yyyymm % 100 <= 12;
    }
}
