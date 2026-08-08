<?php

namespace App\Enums;

enum DowntimeActionPlanStatus: string
{
    case Pending = 'Pending';
    case WaitingForFastConfirmation = 'Waiting for FAST Confirmation';
    case Confirmed = 'Confirmed';
    case Rejected = 'Rejected';
    case Cancelled = 'Cancelled';
}
