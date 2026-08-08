<?php

namespace App\Services\MheDowntimeImport;

class MheDowntimeImportPayload
{
    /**
     * @param  array<int, array<string, mixed>>  $downtimes
     * @param  array<int, array<string, mixed>>  $actionPlans
     * @param  array<int, array<string, mixed>>  $downtimeAttachments
     * @param  array<int, array<string, mixed>>  $actionPlanAttachments
     */
    public function __construct(
        public array $downtimes,
        public array $actionPlans,
        public array $downtimeAttachments,
        public array $actionPlanAttachments,
        public ?string $seedPath = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'downtimes' => $this->downtimes,
            'action_plans' => $this->actionPlans,
            'downtime_attachments' => $this->downtimeAttachments,
            'action_plan_attachments' => $this->actionPlanAttachments,
        ];
    }
}
