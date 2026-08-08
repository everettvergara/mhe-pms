<?php

namespace App\Enums;

enum FscWebImportPhase: string
{
    case Purging = 'purging';
    case ImportingUsers = 'importing_users';
    case ExportingDowntimes = 'exporting_downtimes';
    case ImportingDowntimes = 'importing_downtimes';
    case ImportingActionPlans = 'importing_action_plans';
    case ImportingAttachments = 'importing_attachments';
    case Finalizing = 'finalizing';

    public function label(): string
    {
        return match ($this) {
            self::Purging => 'Purging local data',
            self::ImportingUsers => 'Importing users',
            self::ExportingDowntimes => 'Exporting downtimes from Eagle Eye',
            self::ImportingDowntimes => 'Importing downtimes',
            self::ImportingActionPlans => 'Importing action plans',
            self::ImportingAttachments => 'Importing attachments',
            self::Finalizing => 'Finalizing',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Purging => 5,
            self::ImportingUsers => 15,
            self::ExportingDowntimes => 15,
            self::ImportingDowntimes => 40,
            self::ImportingActionPlans => 10,
            self::ImportingAttachments => 10,
            self::Finalizing => 5,
        };
    }
}
