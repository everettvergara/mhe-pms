<?php

namespace App\Enums;

enum MheDowntimeImportSource: string
{
    case EagleEyeJsonSeed = 'eagle_eye_json_seed';
    case EagleEyeMysql = 'eagle_eye_mysql';
    case EagleEyeSqlDump = 'eagle_eye_sql_dump';

    public function label(): string
    {
        return match ($this) {
            self::EagleEyeJsonSeed => 'Eagle Eye JSON seed files',
            self::EagleEyeMysql => 'Eagle Eye live MySQL database',
            self::EagleEyeSqlDump => 'Eagle Eye SQL dump file',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::EagleEyeJsonSeed => 'Import from bundled JSON under database/data/eagle_eye (exported from fsc_web).',
            self::EagleEyeMysql => 'Connect to an Eagle Eye MySQL server, export on the fly, then import.',
            self::EagleEyeSqlDump => 'Upload or point to an Eagle Eye .sql dump; export on the fly, then import.',
        };
    }
}
