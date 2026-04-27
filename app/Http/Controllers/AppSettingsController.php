<?php

namespace App\Http\Controllers;

use App\Enums\App;
use App\Http\Requests\GeneralSettingsRequest;
use App\Http\Requests\LogoRequest;
use App\Models\AppSettings;
use App\Models\Company;
use App\Models\SmtpSettings;
use App\Models\TallyFieldMapping;
use App\Models\TallyIntegrationSetting;
use App\Models\TallySyncLog;
use App\Models\Twilio;
use App\Models\Vonage;
use App\Services\TallyIntegration\TallySyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
        $mappingDefinitions = $this->tallyMappingDefinitions();
        $selectedMappingEntity = old('entity', 'item');
        $selectedProjectFieldKey = old('project_field_key', '');
        $selectedTallyFieldKey = old('tally_field_key', '');

        if (Schema::hasTable('tally_field_mappings')) {
            $mappings = TallyFieldMapping::orderByDesc('id')->get();
            if ($request->filled('edit')) {
                $editMapping = TallyFieldMapping::find($request->integer('edit'));
            }

            $mappings = $mappings->map(function (TallyFieldMapping $mapping) use ($mappingDefinitions) {
                [$entityKey, $tallyFieldKey] = $this->splitTallyFieldWithEntity($mapping->tally_field);
                if (! $entityKey || ! isset($mappingDefinitions[$entityKey])) {
                    $entityKey = $this->detectEntityByTallyField($mappingDefinitions, $tallyFieldKey) ?? 'item';
                }

                $mapping->entity_key = $entityKey;
                $mapping->entity_label = $mappingDefinitions[$entityKey]['label'] ?? Str::title($entityKey);
                $mapping->project_field_label = $this->resolveFieldLabel($mappingDefinitions, $entityKey, 'project_fields', (string) $mapping->project_field);
                $mapping->tally_field_key = $tallyFieldKey;
                $mapping->tally_field_label = $this->resolveFieldLabel($mappingDefinitions, $entityKey, 'tally_fields', (string) $tallyFieldKey);

                return $mapping;
            });
        }

        if (Schema::hasTable('tally_integration_settings')) {
            $connectionSettings = TallyIntegrationSetting::query()->latest('id')->first();
        }

        if ($editMapping) {
            [$entityKey, $tallyFieldKey] = $this->splitTallyFieldWithEntity($editMapping->tally_field);
            if (! $entityKey || ! isset($mappingDefinitions[$entityKey])) {
                $entityKey = $this->detectEntityByTallyField($mappingDefinitions, $tallyFieldKey) ?? 'item';
            }

            if (! old('entity')) {
                $selectedMappingEntity = $entityKey;
            }
            if (! old('project_field_key')) {
                $selectedProjectFieldKey = (string) $editMapping->project_field;
            }
            if (! old('tally_field_key')) {
                $selectedTallyFieldKey = (string) $tallyFieldKey;
            }
        }

        return view('app.tally-integration', compact(
            'mappings',
            'editMapping',
            'connectionSettings',
            'mappingDefinitions',
            'selectedMappingEntity',
            'selectedProjectFieldKey',
            'selectedTallyFieldKey'
        ));
    }

    public function tallyIntegrationConnectionStore(Request $request)
    {
        if (! Schema::hasTable('tally_integration_settings')) {
            return redirect()->back()->withErrors(['migration' => 'Please run migration first for Tally Integration settings table.'])->withInput();
        }

        $validatedData = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'odbc_port' => ['required', 'integer', 'between:1,65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = TallyIntegrationSetting::query()->first() ?? new TallyIntegrationSetting();
        $settings->host = $validatedData['host'];
        $settings->port = $validatedData['port'] ?? null;
        $settings->odbc_port = $validatedData['odbc_port'];
        $settings->username = $validatedData['username'] ?? null;
        if (! empty($validatedData['password'])) {
            $settings->password = $validatedData['password'];
        }
        $settings->status = 1;
        $settings->save();

        return redirect()->route('settings.tally.integration')->with('success', 'Connection settings saved successfully.');
    }

    public function tallyIntegrationTestConnection(Request $request): JsonResponse
    {
        try {
            $inputHost = $request->input('host');
            $inputPort = $request->input('port');
            $inputOdbcPort = $request->input('odbc_port');

            $settings = null;
            if (Schema::hasTable('tally_integration_settings')) {
                $settings = TallyIntegrationSetting::query()->latest('id')->first();
            }

            $host = $inputHost ?: ($settings->host ?? null);
            $port = $inputPort ?: ($settings->port ?? null);
            $odbcPort = $inputOdbcPort ?: ($settings->odbc_port ?? null);

            if (empty($host) || empty($odbcPort)) {
                Log::warning('Tally integration connection test validation failed', [
                    'host' => $host,
                    'port' => $port,
                    'odbc_port' => $odbcPort,
                    'user_id' => auth()->id(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Host and ODBC Port are required to test connection.',
                ], 422);
            }

            $testPort = function (string $hostAddress, int $portNumber): array {
                $errno = 0;
                $errstr = '';
                $connection = @stream_socket_client(
                    "tcp://{$hostAddress}:{$portNumber}",
                    $errno,
                    $errstr,
                    5
                );

                if ($connection !== false) {
                    fclose($connection);
                    return ['open' => true, 'message' => 'Open'];
                }

                return ['open' => false, 'message' => $errstr ?: "Connection failed ({$errno})"];
            };

            $odbcResult = $testPort((string) $host, (int) $odbcPort);
            $appPortResult = null;
            if (! empty($port)) {
                $appPortResult = $testPort((string) $host, (int) $port);
            }

            $isSuccess = $odbcResult['open'] === true;

            if ($isSuccess) {
                Log::info('Tally integration ODBC connection test successful', [
                    'host' => $host,
                    'port' => $port,
                    'odbc_port' => $odbcPort,
                    'odbc_status' => $odbcResult['message'],
                    'app_port_status' => $appPortResult['message'] ?? null,
                    'user_id' => auth()->id(),
                ]);
            } else {
                Log::warning('Tally integration ODBC connection test failed', [
                    'host' => $host,
                    'port' => $port,
                    'odbc_port' => $odbcPort,
                    'odbc_status' => $odbcResult['message'],
                    'app_port_status' => $appPortResult['message'] ?? null,
                    'user_id' => auth()->id(),
                ]);
            }

            return response()->json([
                'status' => $isSuccess,
                'message' => $isSuccess ? 'ODBC connection test successful.' : 'ODBC connection test failed.',
                'details' => [
                    'host' => $host,
                    'odbc_port' => ['port' => (int) $odbcPort, 'status' => $odbcResult['message']],
                    'app_port' => $appPortResult ? ['port' => (int) $port, 'status' => $appPortResult['message']] : null,
                ],
            ], $isSuccess ? 200 : 422);
        } catch (\Throwable $e) {
            Log::error('Tally integration test connection exception', [
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

        $result = match ($entity) {
            'item', 'items' => $tallySyncService->syncItemById($id, 'upsert'),
            'party', 'vendor', 'customer' => $tallySyncService->syncPartyById($id, 'upsert'),
            'sale', 'invoice' => $tallySyncService->syncSaleById($id, 'upsert'),
            default => [
                'status' => false,
                'message' => 'Unsupported entity. Allowed: item, party, sale.',
                'entity_type' => $entity,
                'entity_id' => $id,
            ],
        };

        return response()->json($result, ($result['status'] ?? false) ? 200 : 422);
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
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

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

        $validatedData = $this->validateTallyMappingSelection($request);

        TallyFieldMapping::create($validatedData);

        return redirect()->route('settings.tally.integration')->with('success', 'Mapping saved successfully.');
    }

    public function tallyIntegrationUpdate(Request $request, int $id)
    {
        if (! Schema::hasTable('tally_field_mappings')) {
            return redirect()->back()->withErrors(['migration' => 'Please run migration first for Tally Integration table.'])->withInput();
        }

        $validatedData = $this->validateTallyMappingSelection($request);

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

    private function tallyMappingDefinitions(): array
    {
        return [
            'item' => [
                'label' => 'Item',
                'project_fields' => [
                    ['value' => 'name', 'label' => 'Item Name'],
                    ['value' => 'item_code', 'label' => 'Item Code'],
                    ['value' => 'category.name', 'label' => 'Category Name'],
                    ['value' => 'baseUnit.short_code', 'label' => 'Base Unit Short Code'],
                    ['value' => 'secondaryUnit.short_code', 'label' => 'Secondary Unit Short Code'],
                    ['value' => 'conversion_rate', 'label' => 'Conversion Rate'],
                    ['value' => 'hsn', 'label' => 'HSN Code'],
                    ['value' => 'description', 'label' => 'Description'],
                ],
                'tally_fields' => [
                    ['value' => 'NAME', 'label' => 'NAME'],
                    ['value' => 'PARENT', 'label' => 'PARENT (Stock Group)'],
                    ['value' => 'BASEUNITS', 'label' => 'BASEUNITS'],
                    ['value' => 'ADDITIONALUNITS', 'label' => 'ADDITIONALUNITS'],
                    ['value' => 'CONVERSION', 'label' => 'CONVERSION'],
                    ['value' => 'HSNCODE', 'label' => 'HSNCODE'],
                ],
            ],
            'party' => [
                'label' => 'Party',
                'project_fields' => [
                    ['value' => 'company_name', 'label' => 'Company Name'],
                    ['value' => 'primary_name', 'label' => 'Primary Name'],
                    ['value' => 'vendor_type', 'label' => 'Vendor Type'],
                    ['value' => 'primary_mobile', 'label' => 'Primary Mobile'],
                    ['value' => 'primary_email', 'label' => 'Primary Email'],
                    ['value' => 'company_gst', 'label' => 'GST Number'],
                    ['value' => 'billing_address', 'label' => 'Billing Address'],
                    ['value' => 'shipping_address', 'label' => 'Shipping Address'],
                ],
                'tally_fields' => [
                    ['value' => 'NAME', 'label' => 'NAME'],
                    ['value' => 'PARENT', 'label' => 'PARENT (Ledger Group)'],
                    ['value' => 'LEDGERMOBILE', 'label' => 'LEDGERMOBILE'],
                    ['value' => 'EMAIL', 'label' => 'EMAIL'],
                    ['value' => 'PARTYGSTIN', 'label' => 'PARTYGSTIN'],
                    ['value' => 'ADDRESS', 'label' => 'ADDRESS'],
                ],
            ],
            'sale' => [
                'label' => 'Sale',
                'project_fields' => [
                    ['value' => 'sale_code', 'label' => 'Sale Code'],
                    ['value' => 'sale_date', 'label' => 'Sale Date'],
                    ['value' => 'note', 'label' => 'Note'],
                    ['value' => 'party.company_name', 'label' => 'Party Company Name'],
                    ['value' => 'party.primary_name', 'label' => 'Party Primary Name'],
                    ['value' => 'grand_total', 'label' => 'Grand Total'],
                ],
                'tally_fields' => [
                    ['value' => 'VOUCHERNUMBER', 'label' => 'VOUCHERNUMBER'],
                    ['value' => 'PARTYLEDGERNAME', 'label' => 'PARTYLEDGERNAME'],
                    ['value' => 'DATE', 'label' => 'DATE'],
                    ['value' => 'NARRATION', 'label' => 'NARRATION'],
                    ['value' => 'VOUCHERTYPENAME', 'label' => 'VOUCHERTYPENAME'],
                ],
            ],
        ];
    }

    private function validateTallyMappingSelection(Request $request): array
    {
        $mappingDefinitions = $this->tallyMappingDefinitions();

        $validator = Validator::make($request->all(), [
            'entity' => ['required', 'string', 'max:50'],
            'project_field_key' => ['required', 'string', 'max:255'],
            'tally_field_key' => ['required', 'string', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($request, $mappingDefinitions) {
            $entity = Str::lower(trim((string) $request->input('entity')));
            $projectFieldKey = trim((string) $request->input('project_field_key'));
            $tallyFieldKey = Str::upper(trim((string) $request->input('tally_field_key')));

            if (! isset($mappingDefinitions[$entity])) {
                $validator->errors()->add('entity', 'Please select a valid entity.');

                return;
            }

            $allowedProjectFieldKeys = collect($mappingDefinitions[$entity]['project_fields'] ?? [])
                ->pluck('value')
                ->map(fn ($value) => (string) $value)
                ->all();

            if (! in_array($projectFieldKey, $allowedProjectFieldKeys, true)) {
                $validator->errors()->add('project_field_key', 'Please select a valid project field.');
            }

            $allowedTallyFieldKeys = collect($mappingDefinitions[$entity]['tally_fields'] ?? [])
                ->pluck('value')
                ->map(fn ($value) => Str::upper((string) $value))
                ->all();

            if (! in_array($tallyFieldKey, $allowedTallyFieldKeys, true)) {
                $validator->errors()->add('tally_field_key', 'Please select a valid Tally field.');
            }
        });

        $validated = $validator->validate();

        $entity = Str::lower(trim((string) $validated['entity']));
        $projectFieldKey = trim((string) $validated['project_field_key']);
        $tallyFieldKey = Str::upper(trim((string) $validated['tally_field_key']));

        return [
            'project_field' => $projectFieldKey,
            'tally_field' => $entity.'.'.$tallyFieldKey,
        ];
    }

    private function splitTallyFieldWithEntity(?string $tallyField): array
    {
        $tallyField = trim((string) $tallyField);
        if ($tallyField === '') {
            return [null, null];
        }

        if (Str::contains($tallyField, '.')) {
            [$entity, $field] = explode('.', $tallyField, 2);

            return [Str::lower(trim((string) $entity)), Str::upper(trim((string) $field))];
        }

        return [null, Str::upper($tallyField)];
    }

    private function detectEntityByTallyField(array $mappingDefinitions, ?string $tallyFieldKey): ?string
    {
        $tallyFieldKey = Str::upper(trim((string) $tallyFieldKey));
        if ($tallyFieldKey === '') {
            return null;
        }

        foreach ($mappingDefinitions as $entityKey => $definition) {
            $allowedTallyFields = collect($definition['tally_fields'] ?? [])
                ->pluck('value')
                ->map(fn ($value) => Str::upper((string) $value))
                ->all();

            if (in_array($tallyFieldKey, $allowedTallyFields, true)) {
                return $entityKey;
            }
        }

        return null;
    }

    private function resolveFieldLabel(array $mappingDefinitions, string $entityKey, string $fieldType, string $fieldValue): string
    {
        $fieldValue = trim((string) $fieldValue);
        if ($fieldValue === '') {
            return '-';
        }

        $definition = $mappingDefinitions[$entityKey][$fieldType] ?? [];
        foreach ($definition as $fieldDefinition) {
            if ((string) ($fieldDefinition['value'] ?? '') === $fieldValue) {
                return (string) ($fieldDefinition['label'] ?? $fieldValue);
            }
        }

        return $fieldValue;
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

