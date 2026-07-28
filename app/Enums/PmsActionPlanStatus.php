<?php

namespace App\Enums;

enum PmsActionPlanStatus: string
{
    case None = 'None';
    case Pending = 'Pending';
    case WaitingForFastConfirmation = 'Waiting for FAST Confirmation';
    case Confirmed = 'Confirmed';
    case Rejected = 'Rejected';
    case Cancelled = 'Cancelled';
}
