<?php

namespace Database\Seeders;

use App\Enums\RecordStatus;
use App\Models\ChecklistGroup;
use App\Models\ChecklistItem;
use Illuminate\Database\Seeder;

class ChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['group_name' => 'CHARGER', 'sequence' => 1, 'items' => [
                'Timer Function',
                'Looseness in Connecting Parts',
                'Function Voltage Measurement',
            ]],
            ['group_name' => 'STEERING MOTOR', 'sequence' => 2, 'items' => [
                'Rotation Sound',
                'Looseness in Connecting Parts',
                'Insulation Resistance',
                'Brush / Spring Wear',
            ]],
            ['group_name' => 'MAGNETIC CONTACTOR', 'sequence' => 3, 'items' => [
                'Operating Condition and Timing',
                'Looseness of Oil Mounting Parts',
                'Main Circuit Lead Wire Looseness',
            ]],
        ];

        foreach ($groups as $groupData) {
            $group = ChecklistGroup::query()->updateOrCreate(
                ['group_name' => $groupData['group_name']],
                [
                    'sequence' => $groupData['sequence'],
                    'status' => RecordStatus::Active->value,
                ],
            );

            foreach ($groupData['items'] as $index => $description) {
                ChecklistItem::query()->updateOrCreate(
                    [
                        'checklist_group_id' => $group->id,
                        'description' => $description,
                    ],
                    [
                        'sequence' => $index + 1,
                        'status' => RecordStatus::Active->value,
                    ],
                );
            }
        }
    }
}
