<?php

namespace App\Enums;

enum ProgressStatus: string
{
    case Pending = 'Pending';
    case Implemented = 'Implemented';
    case Cancelled = 'Cancelled';
}
