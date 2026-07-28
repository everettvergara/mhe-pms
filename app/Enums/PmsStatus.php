<?php

namespace App\Enums;

enum PmsStatus: string
{
    case Draft = 'Draft';
    case WithFindings = 'With Findings';
    case NoFindings = 'No Findings';
    case Cancelled = 'Cancelled';
}
