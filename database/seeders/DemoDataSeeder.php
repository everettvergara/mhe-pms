<?php

namespace Database\Seeders;

use App\Enums\ActionPlanStatus;
use App\Enums\ChecklistAnswer;
use App\Enums\PmsActionPlanStatus;
use App\Enums\PmsStatus;
use App\Enums\ProgressStatus;
use App\Models\ActionPlan;
use App\Models\ChecklistItem;
use App\Models\MheType;
use App\Models\PmsDetail;
use App\Models\PmsHeader;
use App\Models\Site;
use App\Models\User;
use App\Services\ActionPlanService;
use App\Services\NumberSequenceService;
use App\Services\PmsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $toyotaUser = User::query()->where('username', 'toyota')->first();
        $admin = User::query()->where('username', 'admin')->first();

        if (! $toyotaUser || ! $admin) {
            return;
        }

        $pmsService = app(PmsService::class);
        $actionPlanService = app(ActionPlanService::class);
        $numberSequence = app(NumberSequenceService::class);

        $site = Site::query()->where('site_code', 'SDC')->firstOrFail();
        $mheType = MheType::query()->where('code', 'CB')->firstOrFail();

        $draft = $pmsService->createDraft($toyotaUser, [
            'site_id' => $site->id,
            'technician_name' => 'Juan Dela Cruz',
            'date_from' => now()->subDays(2),
            'date_to' => now()->subDays(2)->addHours(2),
            'next_schedule_date' => now()->addDays(30)->toDateString(),
            'mhe_type_id' => $mheType->id,
            'unit_number' => 'CB-001',
            'serial_number' => 'SN-10001',
        ]);

        $noFindings = PmsHeader::query()->create([
            'pms_no' => $numberSequence->nextNumber('pms'),
            'supplier_id' => $toyotaUser->supplier_id,
            'site_id' => $site->id,
            'technician_name' => 'Maria Santos',
            'date_from' => now()->subDays(5),
            'date_to' => now()->subDays(5)->addHours(1),
            'next_schedule_date' => now()->addDays(7)->toDateString(),
            'mhe_type_id' => $mheType->id,
            'unit_number' => 'CB-002',
            'serial_number' => 'SN-10002',
            'status' => PmsStatus::NoFindings,
            'action_plan_status' => PmsActionPlanStatus::None,
            'submitted_by' => $toyotaUser->id,
            'submitted_at' => now()->subDays(5),
            'created_by' => $toyotaUser->id,
            'updated_by' => $toyotaUser->id,
        ]);
        $this->seedDetails($noFindings, $toyotaUser, ChecklistAnswer::Good);

        $withFindings = PmsHeader::query()->create([
            'pms_no' => $numberSequence->nextNumber('pms'),
            'supplier_id' => $toyotaUser->supplier_id,
            'site_id' => $site->id,
            'technician_name' => 'Pedro Reyes',
            'date_from' => now()->subDays(3),
            'date_to' => now()->subDays(3)->addHours(2),
            'next_schedule_date' => now()->addDays(14)->toDateString(),
            'mhe_type_id' => $mheType->id,
            'unit_number' => 'CB-003',
            'serial_number' => 'SN-10003',
            'status' => PmsStatus::WithFindings,
            'action_plan_status' => PmsActionPlanStatus::None,
            'submitted_by' => $toyotaUser->id,
            'submitted_at' => now()->subDays(3),
            'created_by' => $toyotaUser->id,
            'updated_by' => $toyotaUser->id,
        ]);
        $findingDetail = $this->seedDetails($withFindings, $toyotaUser, ChecklistAnswer::NoGood, 'Worn brush detected');

        $plan = ActionPlan::query()->create([
            'action_plan_no' => $numberSequence->nextNumber('action_plan'),
            'pms_detail_id' => $findingDetail->id,
            'title' => 'Replace worn brush',
            'description' => 'Replace steering motor brush and perform functional test.',
            'responsible_person' => 'Pedro Reyes',
            'timeline_from' => Carbon::today()->subDays(2),
            'timeline_to' => Carbon::today()->addDays(5),
            'status' => ActionPlanStatus::Pending,
            'created_by' => $toyotaUser->id,
            'updated_by' => $toyotaUser->id,
        ]);

        $actionPlanService->addComment($toyotaUser, $plan, 'Replacement parts ordered.', ProgressStatus::Pending);
        $actionPlanService->addComment($toyotaUser, $plan, 'Component replaced and tested.', ProgressStatus::Implemented);
        $actionPlanService->markImplemented($toyotaUser, $plan, true);
    }

    private function seedDetails(PmsHeader $header, User $user, ChecklistAnswer $defaultAnswer, ?string $remarks = null): PmsDetail
    {
        $firstNoGood = null;
        $items = ChecklistItem::query()->with('checklistGroup')->orderBy('checklist_group_id')->orderBy('sequence')->get();

        foreach ($items as $index => $item) {
            $answer = $index === 3 && $defaultAnswer === ChecklistAnswer::NoGood ? ChecklistAnswer::NoGood : ChecklistAnswer::Good;
            $detail = PmsDetail::query()->create([
                'pms_header_id' => $header->id,
                'checklist_item_id' => $item->id,
                'answer' => $answer,
                'remarks' => $answer === ChecklistAnswer::NoGood ? ($remarks ?? 'Finding noted') : null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            if ($answer === ChecklistAnswer::NoGood && $firstNoGood === null) {
                $firstNoGood = $detail;
            }
        }

        return $firstNoGood ?? PmsDetail::query()->where('pms_header_id', $header->id)->firstOrFail();
    }
}
