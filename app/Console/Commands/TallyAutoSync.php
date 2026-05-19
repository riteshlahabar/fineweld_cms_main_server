<?php

namespace App\Console\Commands;

use App\Models\Expenses\Expense;
use App\Models\Purchase\Purchase;
use App\Models\Sale\Sale;
use App\Models\TallySyncLog;
use App\Services\TallyIntegration\TallyClientService;
use App\Services\TallyIntegration\TallySyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TallyAutoSync extends Command
{
    protected $signature = 'tally:auto-sync
        {--entity=sale,purchase,expense : Comma separated entities: sale,purchase,expense}
        {--from= : From date, default today}
        {--to= : To date, default today}
        {--limit=100 : Maximum records per entity}
        {--include-success : Re-sync records that already have a successful Tally log}';

    protected $description = 'Automatically sync unsynced CMS transactions to Tally.';

    public function handle(TallySyncService $tallySyncService, TallyClientService $tallyClient): int
    {
        $settings = $tallyClient->activeSettings();
        if (! $settings || trim((string) $settings->host) === '') {
            $this->warn('Tally auto sync skipped: active Tally connection settings are missing.');

            return self::FAILURE;
        }

        $fromDate = $this->dateOption('from');
        $toDate = $this->dateOption('to');
        $limit = max(1, min(500, (int) $this->option('limit')));
        $companyName = $tallyClient->defaultCompanyName();
        $includeSuccess = (bool) $this->option('include-success');
        $entities = $this->entitiesOption();

        $total = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($entities as $entity) {
            $records = $this->recordsForEntity($entity, $fromDate, $toDate, $limit, $includeSuccess);

            foreach ($records as $record) {
                $entityId = (int) $record->id;
                if (! $includeSuccess && $this->hasSuccessfulSync($entity, $entityId)) {
                    $skipped++;
                    continue;
                }

                $total++;
                $result = match ($entity) {
                    'sale' => $tallySyncService->syncSaleById($entityId, 'upsert', $companyName),
                    'purchase' => $tallySyncService->syncPurchaseById($entityId, 'upsert', $companyName),
                    'expense' => $tallySyncService->syncExpenseById($entityId, 'upsert', $companyName),
                };

                if (! (bool) ($result['status'] ?? false)) {
                    $failed++;
                    $this->warn("{$entity} #{$entityId} failed: ".($result['message'] ?? 'Unknown Tally error.'));
                }
            }
        }

        $this->info("Tally auto sync completed. Synced: {$total}, Failed: {$failed}, Skipped: {$skipped}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function dateOption(string $name): string
    {
        $value = trim((string) $this->option($name));

        return Carbon::parse($value !== '' ? $value : now()->toDateString())->toDateString();
    }

    private function entitiesOption(): array
    {
        $allowed = ['sale', 'purchase', 'expense'];

        return collect(explode(',', (string) $this->option('entity')))
            ->map(fn (string $entity) => Str::lower(trim($entity)))
            ->filter(fn (string $entity) => in_array($entity, $allowed, true))
            ->unique()
            ->values()
            ->all() ?: $allowed;
    }

    private function recordsForEntity(string $entity, string $fromDate, string $toDate, int $limit, bool $includeSuccess)
    {
        [$modelClass, $dateColumn] = match ($entity) {
            'sale' => [Sale::class, 'sale_date'],
            'purchase' => [Purchase::class, 'purchase_date'],
            'expense' => [Expense::class, 'expense_date'],
        };

        $model = new $modelClass();
        $query = $modelClass::query()->whereBetween($dateColumn, [$fromDate, $toDate]);

        if (! $includeSuccess && Schema::hasTable('tally_sync_logs')) {
            $query->whereNotExists(function ($subQuery) use ($entity, $model) {
                $subQuery->selectRaw('1')
                    ->from('tally_sync_logs')
                    ->whereColumn('tally_sync_logs.entity_id', $model->getTable().'.id')
                    ->where('tally_sync_logs.entity_type', $entity)
                    ->where('tally_sync_logs.status', 'success');
            });
        }

        return $query
            ->orderBy($dateColumn)
            ->orderBy('id')
            ->limit($limit)
            ->get(['id']);
    }

    private function hasSuccessfulSync(string $entityType, int $entityId): bool
    {
        if (! Schema::hasTable('tally_sync_logs')) {
            return false;
        }

        return TallySyncLog::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', 'success')
            ->exists();
    }
}
