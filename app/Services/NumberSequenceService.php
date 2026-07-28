<?php

namespace App\Services;

use App\Models\NumberSequence;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class NumberSequenceService
{
    /**
     * @param  'pms'|'action_plan'  $type
     */
    public function nextNumber(string $type): string
    {
        $prefix = match ($type) {
            'pms' => 'PMS',
            'action_plan' => 'AP',
            default => throw new InvalidArgumentException("Invalid sequence type: {$type}"),
        };

        return DB::transaction(function () use ($type, $prefix) {
            $year = (int) now()->format('Y');

            $sequence = NumberSequence::query()
                ->where('type', $type)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                $sequence = NumberSequence::query()->create([
                    'type' => $type,
                    'year' => $year,
                    'last_number' => 0,
                ]);

                $sequence = NumberSequence::query()
                    ->whereKey($sequence->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $sequence->increment('last_number');
            $sequence->refresh();

            return sprintf('%s-%d-%05d', $prefix, $year, $sequence->last_number);
        });
    }
}
