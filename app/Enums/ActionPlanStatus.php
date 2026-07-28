<?php

namespace App\Enums;

enum ActionPlanStatus: string
{
    case Pending = 'Pending';
    case WaitingForFastConfirmation = 'Waiting for FAST Confirmation';
    case Confirmed = 'Confirmed';
    case Rejected = 'Rejected';
    case Cancelled = 'Cancelled';

    public function countKey(): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->name));
    }

    public function countAttribute(): string
    {
        return 'action_plans_'.$this->countKey().'_count';
    }
}
