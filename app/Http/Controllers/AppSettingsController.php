<?php

namespace App\Http\Controllers;

use App\Enums\App;
use App\Http\Requests\GeneralSettingsRequest;
use App\Http\Requests\LogoRequest;
use App\Models\AppSettings;
use App\Models\Company;
use App\Models\Expenses\Expense;
use App\Models\SmtpSettings;
use App\Models\Purchase\Purchase;
use App\Models\Sale\Sale;
use App\Models\TallyFieldMapping;
use App\Models\TallyIntegrationSetting;
use App\Models\TallySyncLog;
use App\Models\Twilio;
use App\Models\Vonage;
use App\Services\TallyIntegration\TallyClientService;
use App\Services\TallyIntegration\TallySyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AppSettingsController extends Controller
{
    protected $appSettingsRecordId;

    protected $smtpSettingsRecordId;

    protected $companyId;

    public function __construct()
    {
        $this->appSettingsRecordId = App::APP_SETTINGS_RECORD_ID->value;
        $this->smtpSettingsRecordId = App::APP_SETTINGS_RECORD_ID->value;
        $this->companyId = App::APP_SETTINGS_RECORD_ID->value;
    }

    public function index()
    {
        $data = AppSettings::findOrNew($this->appSettingsRecordId);
        $company = Company::findOrNew($this->companyId);
        $smtp = SmtpSettings::findOrNew($this->smtpSettingsRecordId);
        $twilio = Twilio::findOrNew($this->smtpSettingsRecordId);
        $vonage = Vonage::findOrNew($this->smtpSettingsRecordId);
        // $data->fevicon = $data->fevicon;
        // $data->colored_logo = $data->colored_logo;
        // $data->light_logo = $data->light_logo;

        // echo "<pre>";print_r($data);exit;
        return view('app.settings', compact('data', 'company', 'smtp', 'twilio', 'vonage'));
    }

    public function tallyIntegration(Request $request)
    {
        $mappings = collect();
        $editMapping = null;
        $connectionSettings = null;

        if (Schema::hasTable('tally_field_mappings')) {
            $mappings = TallyFieldMapping::orderByDesc('id')->get()->map(function (TallyFieldMapping $mapping) {
                $mapping->tally_field = $this->normalizeTallyFieldInput((string) $mapping->tally_field);

                return $mapping;
            });
            if ($request->filled('edit')) {
                $editMapping = TallyFieldMapping::find($request->integer('edit'));
                if ($editMapping) {
                    $editMapping->tally_field = $this->normalizeTallyFieldInput((string) $editMapping->tally_field);
                }
            }
        }

        if (Schema::hasTable('tally_integration_settings')) {
            $connectionSettings = TallyIntegrationSetting::query()->latest('id')->first();
        }

        return view('app.tally-integration', compact('mappings', 'editMapping', 'connectionSettings'));
    }

    public function tallyIntegrationConnectionStore(Request $request)
    {
        if (! Schema::hasTable('tally_integration_settings')) {
            return redirect()->back()->withErrors(['migration' => 'Please run migration first for Tally Integration settings table.'])->withInput();
        }

        $validatedData = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'xml_port' => ['required', 'integer', 'between:1,65535'],
            'sales_ledger_name' => ['nullable', 'string', 'max:255'],
            'purchase_ledger_name' => ['nullable', 'string', 'max:255'],
            'expense_ledger_name' => ['nullable', 'string', 'max:255'],
            'cash_ledger_name' => ['nullable', 'string', 'max:255'],
            'bank_ledger_name' => ['nullable', 'string', 'max:255'],
            'round_off_ledger_name' => ['nullable', 'string', 'max:255'],
            'cgst_ledger_name' => ['nullable', 'string', 'max:255'],
            'sgst_ledger_name' => ['nullable', 'string', 'max:255'],
            'igst_ledger_name' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'odbc_port' => ['nullable', 'integer', 'between:1,65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = TallyIntegrationSetting::query()->first() ?? new TallyIntegrationSetting();
        $settings->host = $validatedData['host'];
        $settings->port = (int) $validatedData['xml_port'];
        if (Schema::hasColumn('tally_integration_settings', 'company_name')) {
            $settings->company_name = $validatedData['company_name'];
        }
        if (Schema::hasColumn('tally_integration_settings', 'xml_port')) {
            $settings->xml_port = (int) $validatedData['xml_port'];
        }
        if (Schema::hasColumn('tally_integration_settings', 'sales_ledger_name')) {
            $settings->sales_ledger_name = $validatedData['sales_ledger_name'] ?? null;
        }
        foreach ([
            'purchase_ledger_name',
            'expense_ledger_name',
            'cash_ledger_name',
            'bank_ledger_name',
            'round_off_ledger_name',
            'cgst_ledger_name',
            'sgst_ledger_name',
            'igst_ledger_name',
        ] as $ledgerSetting) {
            if (Schema::hasColumn('tally_integration_settings', $ledgerSetting)) {
                $settings->{$ledgerSetting} = $validatedData[$ledgerSetting] ?? null;
            }
        }
        if (array_key_exists('odbc_port', $validatedData) && ! empty($validatedData['odbc_port'])) {
            $settings->odbc_port = $validatedData['odbc_port'];
        }
        $settings->username = $validatedData['username'] ?? null;
        if (! empty($validatedData['password'])) {
            $settings->password = $validatedData['password'];
        }
        $settings->status = 1;
        $settings->save();

        return redirect()->route('settings.tally.integration')->with('success', 'Tally XML connection settings saved successfully.');
    }

    public function tallyIntegrationTestConnection(Request $request, TallyClientService $tallyClient): JsonResponse
    {
        $validatedData = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'xml_port' => ['required', 'integer', 'between:1,65535'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $host = trim((string) $validatedData['host']);
            $xmlPort = (int) $validatedData['xml_port'];
            $companyName = trim((string) ($validatedData['company_name'] ?? ''));
            $result = $tallyClient->fetchCurrentCompany($host, $xmlPort);
            $isSuccess = (bool) ($result['status'] ?? false);
            $activeCompanyName = (string) ($result['current_company'] ?? '');

            if ($isSuccess) {
                Log::info('Tally XML connection test successful', [
                    'host' => $host,
                    'xml_port' => $xmlPort,
                    'company_name' => $companyName,
                    'active_company_name' => $activeCompanyName,
                    'parsed' => $result['parsed'] ?? [],
                    'user_id' => auth()->id(),
                ]);
            } else {
                Log::warning('Tally XML connection test failed', [
                    'host' => $host,
                    'xml_port' => $xmlPort,
                    'company_name' => $companyName,
                    'message' => $result['message'] ?? null,
                    'parsed' => $result['parsed'] ?? [],
                    'user_id' => auth()->id(),
                ]);
            }

            $responseExcerpt = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($result['response_body'] ?? ''))) ?? '');

            return response()->json([
                'status' => $isSuccess,
                'message' => $isSuccess ? 'Tally XML connection successful.' : ($result['message'] ?? 'Tally XML connection failed.'),
                'details' => [
                    'host' => $host,
                    'xml_port' => $xmlPort,
                    'company_name' => $activeCompanyName ?: $companyName,
                    'endpoint' => $result['endpoint'] ?? null,
                    'http_status' => $result['http_status'] ?? null,
                    'tally_message' => $activeCompanyName !== '' ? 'Active company: '.$activeCompanyName : ($result['parsed']['message'] ?? null),
                    'tally_errors' => $result['parsed']['line_errors'] ?? [],
                    'response_excerpt' => Str::limit($responseExcerpt, 500, ''),
                ],
            ], $isSuccess ? 200 : 422);
        } catch (\Throwable $e) {
            Log::error('Tally XML test connection exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Connection test error: '.$e->getMessage(),
            ], 500);
        }
    }

    public function tallyIntegrationMasterOptions(Request $request, TallyClientService $tallyClient): JsonResponse
    {
        $validatedData = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'xml_port' => ['required', 'integer', 'between:1,65535'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $result = $tallyClient->fetchMasterOptions(
                companyName: trim((string) ($validatedData['company_name'] ?? '')) ?: null,
                host: trim((string) $validatedData['host']),
                xmlPort: (int) $validatedData['xml_port'],
            );

            return response()->json($result, ($result['status'] ?? false) ? 200 : 422);
        } catch (\Throwable $e) {
            Log::error('Tally master options fetch exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Tally master options fetch error: '.$e->getMessage(),
            ], 500);
        }
    }

    public function tallyIntegrationClientError(Request $request): JsonResponse
    {
        try {
            Log::error('Tally integration client-side JS error', [
                'message' => mb_substr((string) $request->input('message', ''), 0, 2000),
                'stack' => mb_substr((string) $request->input('stack', ''), 0, 8000),
                'source' => mb_substr((string) $request->input('source', ''), 0, 2000),
                'line' => (int) $request->input('line', 0),
                'column' => (int) $request->input('column', 0),
                'context' => mb_substr((string) $request->input('context', ''), 0, 255),
                'url' => mb_substr((string) $request->input('url', ''), 0, 2000),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            ]);

            return response()->json([
                'status' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to write Tally integration client JS log', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Unable to log client error',
            ], 500);
        }
    }

    public function tallyIntegrationSyncEntity(Request $request, string $entity, int $id, TallySyncService $tallySyncService): JsonResponse
    {
        $entity = Str::lower(trim($entity));
        $validatedData = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);
        $companyName = trim((string) ($validatedData['company_name'] ?? '')) ?: null;

        $result = match ($entity) {
            'item', 'items' => $tallySyncService->syncItemById($id, 'upsert', $companyName),
            'party', 'vendor', 'customer' => $tallySyncService->syncPartyById($id, 'upsert', $companyName),
            'sale', 'invoice' => $tallySyncService->syncSaleById($id, 'upsert', $companyName),
            'purchase', 'bill' => $tallySyncService->syncPurchaseById($id, 'upsert', $companyName),
            'expense', 'payment' => $tallySyncService->syncExpenseById($id, 'upsert', $companyName),
            default => [
                'status' => false,
                'message' => 'Unsupported entity. Allowed: item, party, sale, purchase, expense.',
                'entity_type' => $entity,
                'entity_id' => $id,
            ],
        };

        return response()->json($result, ($result['status'] ?? false) ? 200 : 422);
    }

    public function tallyIntegrationSyncByDate(Request $request, TallySyncService $tallySyncService): JsonResponse
    {
        $validatedData = $request->validate([
            'entity' => ['required', 'string', 'max:50'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        $entity = Str::lower(trim((string) $validatedData['entity']));
        $fromDate = $validatedData['from_date'];
        $toDate = $validatedData['to_date'];
        $companyName = trim((string) ($validatedData['company_name'] ?? '')) ?: null;
        $results = [];

        if ($entity === 'sale') {
            foreach (Sale::query()->whereBetween('sale_date', [$fromDate, $toDate])->orderBy('sale_date')->orderBy('id')->get(['id']) as $record) {
                $results[] = $tallySyncService->syncSaleById((int) $record->id, 'upsert', $companyName);
            }
        } elseif ($entity === 'purchase') {
            foreach (Purchase::query()->whereBetween('purchase_date', [$fromDate, $toDate])->orderBy('purchase_date')->orderBy('id')->get(['id']) as $record) {
                $results[] = $tallySyncService->syncPurchaseById((int) $record->id, 'upsert', $companyName);
            }
        } elseif ($entity === 'expense') {
            foreach (Expense::query()->whereBetween('expense_date', [$fromDate, $toDate])->orderBy('expense_date')->orderBy('id')->get(['id']) as $record) {
                $results[] = $tallySyncService->syncExpenseById((int) $record->id, 'upsert', $companyName);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Unsupported sync type. Allowed: sale, purchase, expense.',
                'data' => [],
            ], 422);
        }

        $failed = collect($results)->filter(fn ($result) => ! (bool) ($result['status'] ?? false))->count();

        return response()->json([
            'status' => $failed === 0,
            'message' => 'Tally sync completed. Total: '.count($results).', Failed: '.$failed.'.',
            'data' => $results,
        ], $failed === 0 ? 200 : 422);
    }

    public function tallyIntegrationSyncLogs(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tally_sync_logs')) {
            return response()->json([
                'status' => false,
                'message' => 'tally_sync_logs table not found. Please run latest migrations.',
                'data' => [],
            ], 422);
        }

        $limit = (int) $request->input('limit', 50);
        $limit = max(1, min(500, $limit));

        $logs = TallySyncLog::query()
            ->when($request->filled('entity_type'), fn ($query) => $query->where('entity_type', Str::lower((string) $request->input('entity_type'))))
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->input('status')))
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('created_at', '>=', (string) $request->input('from_date')))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('created_at', '<=', (string) $request->input('to_date')))
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (TallySyncLog $log) {
                $responsePayload = json_decode((string) $log->response_payload, true) ?: [];
                $requestPayload = json_decode((string) $log->request_payload, true) ?: [];

                return [
                    'id' => $log->id,
                    'entity_type' => $log->entity_type,
                    'entity_id' => $log->entity_id,
                    'operation' => $log->operation,
                    'status' => $log->status,
                    'message' => $log->message,
                    'voucher_no' => data_get($requestPayload, 'mapped_payload.VOUCHERNUMBER', data_get($requestPayload, 'mapped_payload.NAME', '')),
                    'voucher_type' => data_get($requestPayload, 'mapped_payload.VOUCHERTYPENAME', $log->entity_type),
                    'amount' => data_get($requestPayload, 'mapped_payload.AMOUNT', ''),
                    'tally_created' => data_get($responsePayload, 'parsed.created', 0),
                    'tally_altered' => data_get($responsePayload, 'parsed.altered', 0),
                    'tally_errors' => data_get($responsePayload, 'parsed.errors', 0),
                    'tally_line_errors' => data_get($responsePayload, 'parsed.line_errors', []),
                    'synced_at' => optional($log->synced_at)->toDateTimeString(),
                    'created_at' => optional($log->created_at)->toDateTimeString(),
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Tally sync logs fetched successfully.',
            'data' => $logs,
        ]);
    }

    public function tallyIntegrationStore(Request $request)
    {
        if (! Schema::hasTable('tally_field_mappings')) {
            return redirect()->back()->withErrors(['migration' => 'Please run migration first for Tally Integration table.'])->withInput();
        }

        $validatedData = $request->validate([
            'project_field' => ['required', 'string', 'max:255'],
            'tally_field' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);
        $validatedData['tally_field'] = $this->normalizeTallyFieldInput($validatedData['tally_field']);

        TallyFieldMapping::create($validatedData);

        return redirect()->route('settings.tally.integration')->with('success', 'Mapping saved successfully.');
    }

    public function tallyIntegrationUpdate(Request $request, int $id)
    {
        if (! Schema::hasTable('tally_field_mappings')) {
            return redirect()->back()->withErrors(['migration' => 'Please run migration first for Tally Integration table.'])->withInput();
        }

        $validatedData = $request->validate([
            'project_field' => ['required', 'string', 'max:255'],
            'tally_field' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);
        $validatedData['tally_field'] = $this->normalizeTallyFieldInput($validatedData['tally_field']);

        $mapping = TallyFieldMapping::findOrFail($id);
        $mapping->update($validatedData);

        return redirect()->route('settings.tally.integration')->with('success', 'Mapping updated successfully.');
    }

    public function tallyIntegrationDelete(int $id)
    {
        if (! Schema::hasTable('tally_field_mappings')) {
            return redirect()->route('settings.tally.integration')->withErrors(['migration' => 'Please run migration first for Tally Integration table.']);
        }

        $mapping = TallyFieldMapping::findOrFail($id);
        $mapping->delete();

        return redirect()->route('settings.tally.integration')->with('success', 'Mapping deleted successfully.');
    }

    private function normalizeTallyFieldInput(string $field): string
    {
        $normalized = trim($field);
        if ($normalized === '') {
            return $normalized;
        }

        if (Str::contains($normalized, '.')) {
            $parts = array_values(array_filter(array_map('trim', explode('.', $normalized))));
            if (! empty($parts)) {
                $normalized = (string) end($parts);
            }
        }

        return Str::upper($normalized);
    }

    public function store(GeneralSettingsRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        // Save the application settings
        $settings = AppSettings::findOrNew($this->appSettingsRecordId);
        $settings->application_name = $validatedData['application_name'];
        $settings->footer_text = $validatedData['footer_text'];
        $settings->language_id = $validatedData['language_id'];
        $settings->save();

        $company = Company::findOrNew($this->companyId);
        $company->timezone = $validatedData['timezone'];
        $company->date_format = $validatedData['date_format'];
        $company->time_format = $validatedData['time_format'];
        $company->save();

        return response()->json([
            'message' => __('app.record_saved_successfully'),
        ]);
    }

    public function storeTwilio(Request $request): JsonResponse
    {

        // Save the application settings
        $twilio = Twilio::findOrNew($this->appSettingsRecordId);
        $twilio->sid = $request['sid'];
        $twilio->auth_token = $request['auth_token'];
        $twilio->twilio_number = $request['twilio_number'];
        $twilio->status = $request['twilio_status'];
        $twilio->save();

        if ($request['twilio_status'] == 1) {
            $this->updateActiveSMSAPI('Twilio');
        }

        return response()->json([
            'message' => __('app.record_saved_successfully'),
        ]);
    }

    public function storeVonage(Request $request): JsonResponse
    {
        // Save the application settings
        $vonage = Vonage::findOrNew($this->appSettingsRecordId);
        $vonage->api_key = $request['api_key'];
        $vonage->api_secret = $request['api_secret'];
        $vonage->status = $request['vonage_status'];
        $vonage->save();

        if ($request['vonage_status'] == 1) {
            $this->updateActiveSMSAPI('Vonage');
        }

        return response()->json([
            'message' => __('app.record_saved_successfully'),
        ]);
    }

    public function updateActiveSMSAPI($active_sms_api)
    {

        $twilioStatus = Twilio::find($this->appSettingsRecordId)?->status;
        $vonageStatus = Vonage::find($this->appSettingsRecordId)?->status;

        $company = Company::find($this->companyId);
        $company->active_sms_api = (is_null($vonageStatus) && is_null($twilioStatus)) ? null : $active_sms_api;
        $company->save();

        if ($active_sms_api == 'Twilio') {
            // Update Status of Vonage inactive
            $vonage = Vonage::find($this->appSettingsRecordId);
            if ($vonage) {
                $vonage->status = 0;
                $vonage->save(); // Save the updated record
            }
        } else {
            // Update Status of Twilio inactive
            $twilio = Twilio::find($this->appSettingsRecordId);
            if ($twilio) {
                $twilio->status = 0;
                $twilio->save(); // Save the updated record
            }
        }
    }

    public function storeLogo(LogoRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $settings = AppSettings::findOrNew($this->appSettingsRecordId);

        if ($request->hasFile('fevicon') && $request->file('fevicon')->isValid()) {
            $filename = $this->uploadImage($request->file('fevicon'), $externalPath = 'fevicon');
            $settings->fevicon = $filename;
        }

        if ($request->hasFile('colored_logo') && $request->file('colored_logo')->isValid()) {
            $filename = $this->uploadImage($request->file('colored_logo'), $externalPath = 'app-logo');
            $settings->colored_logo = $filename;
        }

        if ($request->hasFile('light_logo') && $request->file('light_logo')->isValid()) {
            $filename = $this->uploadImage($request->file('light_logo'), $externalPath = 'app-logo');
            $settings->light_logo = $filename;
        }
        $settings->save();

        return response()->json([
            'message' => __('app.record_saved_successfully'),
        ]);
    }

    private function uploadImage($image, $externalPath = null)
    {
        // Generate a unique filename for the image
        $filename = uniqid().'.'.$image->getClientOriginalExtension();

        // Save the image to the storage disk
        Storage::putFileAs('public/images/'.$externalPath, $image, $filename);

        return $filename;
    }

    public function clearCache(): JsonResponse
    {
        // Clear the application cache
        Artisan::call('cache:clear');

        // Clear the view cache
        Artisan::call('view:clear');

        // Clear the route cache
        Artisan::call('route:clear');

        // Clear the configuration cache
        Artisan::call('config:clear');

        // Clear and optimize all caches
        // Artisan::call('optimize:clear');

        // Clear and clear Compiled classes
        // Artisan::call('clear-compiled');

        Artisan::call('debugbar:clear');

        // Make the route cache
        Artisan::call('route:cache');

        // make the view cache
        Artisan::call('view:cache'); // New

        // make the configuration cache
        // Artisan::call('config:cache');

        return response()->json([
            'message' => __('app.app_cache_cleared'),
        ]);
    }

    public function migrate()
    {
        // Run the database migrations
        Artisan::call('migrate');

        // seed
        Artisan::call('db:seed');
        // Clear the cache after migration

        return $this->clearCache();
    }

    public function clearAppLog()
    {
        try {
            // Clear the default log channel
            // Log::channel('stack')->clear();

            // Optionally clear other specific channels
            // Log::channel('custom')->clear();

            return response()->json([
                'status' => true,
                'message' => 'Log files cleared successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'error' => 'Failed to clear log files: '.$e->getMessage(),
            ], 500);
        }
    }

    public function databaseBackup()
    {

        Artisan::call('backup:run');

        $file = storage_path('app/backupfile');

        return response()->download($file);
    }
}

