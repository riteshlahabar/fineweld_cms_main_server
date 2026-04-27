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
use App\Models\Twilio;
use App\Models\Vonage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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
            $mappings = TallyFieldMapping::orderByDesc('id')->get();
            if ($request->filled('edit')) {
                $editMapping = TallyFieldMapping::find($request->integer('edit'));
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

        return response()->json([
            'status' => $isSuccess,
            'message' => $isSuccess ? 'ODBC connection test successful.' : 'ODBC connection test failed.',
            'details' => [
                'host' => $host,
                'odbc_port' => ['port' => (int) $odbcPort, 'status' => $odbcResult['message']],
                'app_port' => $appPortResult ? ['port' => (int) $port, 'status' => $appPortResult['message']] : null,
            ],
        ], $isSuccess ? 200 : 422);
    }

    public function tallyIntegrationStore(Request $request)
    {
        if (! Schema::hasTable('tally_field_mappings')) {
            return redirect()->back()->withErrors(['migration' => 'Please run migration first for Tally Integration table.'])->withInput();
        }

        $validatedData = $request->validate([
            'project_field' => ['required', 'string', 'max:255'],
            'tally_field' => ['required', 'string', 'max:255'],
        ]);

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
        ]);

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
