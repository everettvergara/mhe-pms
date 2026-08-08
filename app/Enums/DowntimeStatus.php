<?php

namespace App\Enums;

enum DowntimeStatus: string
{
    case Draft = 'Draft';
    case Posted = 'Posted';
    case Cancelled = 'Cancelled';
}
