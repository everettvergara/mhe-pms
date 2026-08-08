<?php

namespace App\Services\EagleEye;

use App\Support\EagleEye\SqlDumpTableExtractor;
use Illuminate\Support\Facades\File;
use PDO;

class EagleEyeSeedExporter
{
    public function __construct(
        protected SqlDumpTableExtractor $extractor,
    ) {}

    /**
     * @return array<string, int>
     */
    public function exportFromSqlDump(string $sqlPath, ?string $outputPath = null): array
    {
        $outputPath ??= config('eagle_eye.seed_path');
        File::ensureDirectoryExists($outputPath);

        $siteMap = $this->buildSiteMap($sqlPath);
        $typeMap = $this->buildSimpleCodeMap($sqlPath, 'tb_sf_mf_mhe_type', 1);
        $categoryMap = $this->buildSimpleCodeMap($sqlPath, 'tb_sf_mf_mhe_category', 1);

        File::put("{$outputPath}/ee_sites_by_id.json", json_encode($siteMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        File::put("{$outputPath}/ee_mhe_types_by_id.json", json_encode($typeMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        File::put("{$outputPath}/ee_mhe_categories_by_id.json", json_encode($categoryMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $downtimes = [];
        foreach ($this->extractor->extract($sqlPath, 'tb_sf_tr_mhe') as $row) {
            $downtimes[] = $this->mapDowntimeRow($row);
        }

        $actionPlans = [];
        foreach ($this->extractor->extract($sqlPath, 'tb_sf_tr_mhe_action_plan') as $row) {
            $actionPlans[] = $this->mapActionPlanRow($row);
        }

        $downtimeAttachments = [];
        foreach ($this->extractor->extract($sqlPath, 'tb_sf_tr_mhe_attachment') as $row) {
            $downtimeAttachments[] = $this->mapAttachmentRow($row, 'mhe_id');
        }

        $actionPlanAttachments = [];
        foreach ($this->extractor->extract($sqlPath, 'tb_sf_tr_mhe_action_plan_attachment') as $row) {
            $actionPlanAttachments[] = $this->mapAttachmentRow($row, 'mhe_action_plan_id');
        }

        File::put("{$outputPath}/downtimes.json", json_encode($downtimes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        File::put("{$outputPath}/action_plans.json", json_encode($actionPlans, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        File::put("{$outputPath}/downtime_attachments.json", json_encode($downtimeAttachments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        File::put("{$outputPath}/action_plan_attachments.json", json_encode($actionPlanAttachments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return [
            'downtimes' => count($downtimes),
            'action_plans' => count($actionPlans),
            'downtime_attachments' => count($downtimeAttachments),
            'action_plan_attachments' => count($actionPlanAttachments),
            'sites' => count($siteMap),
            'mhe_types' => count($typeMap),
            'mhe_categories' => count($categoryMap),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function exportFromConnection(PDO $pdo, ?string $outputPath = null): array
    {
        $outputPath ??= config('eagle_eye.seed_path');
        File::ensureDirectoryExists($outputPath);

        $siteMap = $this->fetchIdCodeMap($pdo, 'SELECT id, code FROM tb_fin_mf_site');
        $typeMap = $this->fetchIdCodeMap($pdo, 'SELECT id, code FROM tb_sf_mf_mhe_type');
        $categoryMap = $this->fetchIdCodeMap($pdo, 'SELECT id, code FROM tb_sf_mf_mhe_category');

        File::put("{$outputPath}/ee_sites_by_id.json", json_encode($siteMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        File::put("{$outputPath}/ee_mhe_types_by_id.json", json_encode($typeMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        File::put("{$outputPath}/ee_mhe_categories_by_id.json", json_encode($categoryMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $downtimes = array_map(
            fn (array $row) => $this->mapDowntimeRowFromDb($row),
            $pdo->query('SELECT * FROM tb_sf_tr_mhe ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        );

        $actionPlans = array_map(
            fn (array $row) => $this->mapActionPlanRowFromDb($row),
            $pdo->query('SELECT * FROM tb_sf_tr_mhe_action_plan ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        );

        $downtimeAttachments = array_map(
            fn (array $row) => $this->mapAttachmentRowFromDb($row, 'mhe_id'),
            $pdo->query('SELECT * FROM tb_sf_tr_mhe_attachment ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        );

        $actionPlanAttachments = array_map(
            fn (array $row) => $this->mapAttachmentRowFromDb($row, 'mhe_action_plan_id'),
            $pdo->query('SELECT * FROM tb_sf_tr_mhe_action_plan_attachment ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
        );

        File::put("{$outputPath}/downtimes.json", json_encode($downtimes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        File::put("{$outputPath}/action_plans.json", json_encode($actionPlans, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        File::put("{$outputPath}/downtime_attachments.json", json_encode($downtimeAttachments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        File::put("{$outputPath}/action_plan_attachments.json", json_encode($actionPlanAttachments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return [
            'downtimes' => count($downtimes),
            'action_plans' => count($actionPlans),
            'downtime_attachments' => count($downtimeAttachments),
            'action_plan_attachments' => count($actionPlanAttachments),
            'sites' => count($siteMap),
            'mhe_types' => count($typeMap),
            'mhe_categories' => count($categoryMap),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function buildSiteMap(string $sqlPath): array
    {
        $map = [];

        foreach ($this->extractor->extract($sqlPath, 'tb_fin_mf_site') as $row) {
            if (! isset($row[0], $row[1])) {
                continue;
            }

            $map[(string) $row[0]] = (string) $row[1];
        }

        return $map;
    }

    /**
     * @return array<string, string>
     */
    protected function buildSimpleCodeMap(string $sqlPath, string $table, int $codeIndex): array
    {
        $map = [];

        foreach ($this->extractor->extract($sqlPath, $table) as $row) {
            if (! isset($row[0], $row[$codeIndex])) {
                continue;
            }

            $map[(string) $row[0]] = (string) $row[$codeIndex];
        }

        return $map;
    }

    /**
     * @return array<string, string>
     */
    protected function fetchIdCodeMap(PDO $pdo, string $sql): array
    {
        $map = [];

        foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[(string) $row['id']] = (string) $row['code'];
        }

        return $map;
    }

    /**
     * @param  array<int, string|null>  $row
     * @return array<string, mixed>
     */
    protected function mapDowntimeRow(array $row): array
    {
        return [
            'legacy_id' => (int) $row[0],
            'title' => $row[1],
            'ee_site_id' => (int) $row[2],
            'hours_down' => $row[3],
            'ee_mhe_category_id' => (int) $row[4],
            'date_of_incident' => $row[5],
            'description' => $row[6],
            'ee_created_by_id' => (int) $row[8],
            'ee_status_id' => $row[9] !== null ? (int) $row[9] : null,
            'created_at' => $row[10],
            'updated_at' => $row[11],
            'ee_mhe_type_id' => (int) $row[12],
            'uptime' => $row[14],
            'ref_unit_no' => $row[15],
            'w_spare_unit' => $row[16] !== null ? (bool) (int) $row[16] : false,
            'root_cause' => $row[17],
            'time_from' => $row[18],
            'time_to' => $row[19],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function mapDowntimeRowFromDb(array $row): array
    {
        return [
            'legacy_id' => (int) $row['id'],
            'title' => $row['title'],
            'ee_site_id' => (int) $row['site_id'],
            'hours_down' => $row['hours_down'],
            'ee_mhe_category_id' => (int) $row['mhe_category_id'],
            'date_of_incident' => $row['date_of_incident'],
            'description' => $row['description'],
            'ee_created_by_id' => (int) $row['created_by_id'],
            'ee_status_id' => $row['status_id'] !== null ? (int) $row['status_id'] : null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'ee_mhe_type_id' => (int) $row['mhe_type_id'],
            'uptime' => $row['uptime'],
            'ref_unit_no' => $row['ref_unit_no'],
            'w_spare_unit' => (bool) (int) ($row['w_spare_unit'] ?? 0),
            'root_cause' => $row['root_cause'],
            'time_from' => $row['time_from'],
            'time_to' => $row['time_to'],
        ];
    }

    /**
     * @param  array<int, string|null>  $row
     * @return array<string, mixed>
     */
    protected function mapActionPlanRow(array $row): array
    {
        return [
            'legacy_id' => (int) $row[0],
            'mhe_id' => (int) $row[1],
            'action_plan' => $row[2],
            'action_plan_date' => $row[3],
            'responsible_person' => $row[4],
            'ee_action_plan_status_id' => $row[5] !== null ? (int) $row[5] : null,
            'date_implemented' => $row[6],
            'created_at' => $row[7],
            'updated_at' => $row[8],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function mapActionPlanRowFromDb(array $row): array
    {
        return [
            'legacy_id' => (int) $row['id'],
            'mhe_id' => (int) $row['mhe_id'],
            'action_plan' => $row['action_plan'],
            'action_plan_date' => $row['action_plan_date'],
            'responsible_person' => $row['responsible'],
            'ee_action_plan_status_id' => $row['action_plan_id'] !== null ? (int) $row['action_plan_id'] : null,
            'date_implemented' => $row['date_implemented'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }

    /**
     * @param  array<int, string|null>  $row
     * @return array<string, mixed>
     */
    protected function mapAttachmentRow(array $row, string $parentKey): array
    {
        return [
            'legacy_id' => (int) $row[0],
            $parentKey => (int) $row[1],
            'attachment' => $row[2],
            'created_at' => $row[3],
            'updated_at' => $row[4],
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function mapAttachmentRowFromDb(array $row, string $parentKey): array
    {
        return [
            'legacy_id' => (int) $row['id'],
            $parentKey => (int) $row[$parentKey],
            'attachment' => $row['attachment'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }
}
