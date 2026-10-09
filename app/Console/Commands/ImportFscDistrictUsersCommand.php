<?php

namespace App\Console\Commands;

use App\Services\FscWebImport\FscDistrictImportToken;
use App\Services\FscWebImport\FscDistrictUserImportService;
use App\Services\FscWebImport\FscWebConnection;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class ImportFscDistrictUsersCommand extends Command
{
    protected $signature = 'fsc:import-district-users
                            {--districts= : Comma-separated fsc_web district codes}
                            {--preview : Print the user preview and a preview token}
                            {--preview-token= : Token from a previous preview}
                            {--deactivate : Set imported users inactive in fsc_web}
                            {--confirm : Required to import}';

    protected $description = 'Preview or import fsc_web MHE Transaction users for selected districts.';

    public function handle(FscDistrictUserImportService $importService): int
    {
        $previewOnly = (bool) $this->option('preview');
        $token = $this->option('preview-token');

        if (! $previewOnly && ! $token) {
            $this->error('Run with --preview first, then import with --preview-token and --confirm.');

            return self::FAILURE;
        }

        if (! $previewOnly && ! $this->option('confirm')) {
            $this->error('Import requires --confirm. Phrase: '.config('fsc_web_import.confirmation_phrase'));

            return self::FAILURE;
        }

        try {
            if ($previewOnly) {
                $pdo = FscWebConnection::connect(FscWebConnection::configFromEnv());
                $districts = array_map(trim(...), explode(',', (string) $this->option('districts')));
                $preview = $importService->preview($pdo, $districts, FscWebConnection::configFromEnv());
                $this->renderPreview($preview);

                if (($preview['metrics']['users_will_import'] ?? 0) === 0) {
                    return self::FAILURE;
                }

                return self::SUCCESS;
            }

            $payload = FscDistrictImportToken::parse((string) $token);
            $pdo = FscWebConnection::connect($payload['connection'] ?? []);
            $result = $importService->import(
                $pdo,
                (string) $token,
                (bool) $this->option('deactivate'),
            );
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Imported %d user(s).', $result['users_created']));

        if ($this->option('deactivate')) {
            $this->info(sprintf('Deactivated %d user(s) in fsc_web.', $result['deactivated']));
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $preview
     */
    protected function renderPreview(array $preview): void
    {
        $this->table(
            ['Metric', 'Count'],
            collect($preview['metrics'])->map(fn ($count, $key) => [$key, $count])->values()->all(),
        );

        $rows = array_slice($preview['users'], 0, 40);
        $this->table(
            ['Id', 'Code', 'Name', 'Status', 'Sites'],
            array_map(fn (array $user) => [
                $user['legacy_id'],
                $user['code'],
                $user['name'],
                $user['status'],
                implode(', ', $user['site_codes']),
            ], $rows),
        );

        if (count($preview['users']) > count($rows)) {
            $this->line('Showing '.count($rows).' of '.count($preview['users']).' users.');
        }

        $this->newLine();
        $this->line('Preview token:');
        $this->line($preview['preview_token']);
    }
}
