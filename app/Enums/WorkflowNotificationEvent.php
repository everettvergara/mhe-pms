<?php

namespace App\Enums;

enum WorkflowNotificationEvent: string
{
    case ActionPlanCreated = 'action_plan_created';
    case ActionPlanImplemented = 'action_plan_implemented';
    case ActionPlanConfirmed = 'action_plan_confirmed';
    case ActionPlanRejected = 'action_plan_rejected';
}
