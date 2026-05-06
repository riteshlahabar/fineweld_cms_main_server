<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TallySyncLog;
use App\Services\TallyIntegration\TallyClientService;
use App\Services\TallyIntegration\TallyJsonMiddlewareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TallyMiddlewareController extends Controller
{
    public function testConnection(Request $request, TallyClientService $tallyClient): JsonResponse
    {
        $validated = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'xml_port' => ['nullable', 'integer', 'between:1,65535'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = $tallyClient->activeSettings();
        $host = trim((string) ($validated['host'] ?? $settings?->host ?? ''));
        $xmlPort = (int) ($validated['xml_port'] ?? $tallyClient->resolvedXmlPort($settings));
        $companyName = trim((string) ($validated['company_name'] ?? $tallyClient->defaultCompanyName() ?? ''));

        if ($host === '') {
            return response()->json([
                'status' => false,
                'message' => 'Tally host is missing. Save Tally connection settings first or pass host/xml_port.',
            ], 422);
        }

        $result = $tallyClient->testXmlConnection($host, $xmlPort, $companyName);

        return response()->json([
            'status' => (bool) ($result['status'] ?? false),
            'message' => $result['message'] ?? '',
            'endpoint' => $result['endpoint'] ?? null,
            'http_status' => $result['http_status'] ?? null,
            'tally_response' => $result['parsed'] ?? [],
        ], ($result['status'] ?? false) ? 200 : 422);
    }

    public function currentCompany(Request $request, TallyClientService $tallyClient): JsonResponse
    {
        $validated = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'xml_port' => ['nullable', 'integer', 'between:1,65535'],
        ]);

        [$host, $xmlPort] = $this->connectionParams($validated, $tallyClient);

        return $this->exportResult($tallyClient->fetchCurrentCompany($host, $xmlPort));
    }

    public function ledgers(Request $request, TallyClientService $tallyClient): JsonResponse
    {
        $validated = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'xml_port' => ['nullable', 'integer', 'between:1,65535'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        [$host, $xmlPort, $companyName] = $this->connectionParams($validated, $tallyClient);

        return $this->exportResult($tallyClient->fetchLedgers($companyName, $host, $xmlPort));
    }

    public function groups(Request $request, TallyClientService $tallyClient): JsonResponse
    {
        $validated = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'xml_port' => ['nullable', 'integer', 'between:1,65535'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        [$host, $xmlPort, $companyName] = $this->connectionParams($validated, $tallyClient);

        return $this->exportResult($tallyClient->fetchGroups($companyName, $host, $xmlPort));
    }

    public function stockItems(Request $request, TallyClientService $tallyClient): JsonResponse
    {
        $validated = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'xml_port' => ['nullable', 'integer', 'between:1,65535'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        [$host, $xmlPort, $companyName] = $this->connectionParams($validated, $tallyClient);

        return $this->exportResult($tallyClient->fetchStockItems($companyName, $host, $xmlPort));
    }

    public function units(Request $request, TallyClientService $tallyClient): JsonResponse
    {
        $validated = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'xml_port' => ['nullable', 'integer', 'between:1,65535'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        [$host, $xmlPort, $companyName] = $this->connectionParams($validated, $tallyClient);

        return $this->exportResult($tallyClient->fetchUnits($companyName, $host, $xmlPort));
    }

    public function voucherTypes(Request $request, TallyClientService $tallyClient): JsonResponse
    {
        $validated = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'xml_port' => ['nullable', 'integer', 'between:1,65535'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        [$host, $xmlPort, $companyName] = $this->connectionParams($validated, $tallyClient);

        return $this->exportResult($tallyClient->fetchVoucherTypes($companyName, $host, $xmlPort));
    }

    public function masterOptions(Request $request, TallyClientService $tallyClient): JsonResponse
    {
        $validated = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'xml_port' => ['nullable', 'integer', 'between:1,65535'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        [$host, $xmlPort, $companyName] = $this->connectionParams($validated, $tallyClient);
        $result = $tallyClient->fetchMasterOptions($companyName, $host, $xmlPort);

        return response()->json($result, ($result['status'] ?? false) ? 200 : 422);
    }

    public function fields(TallyClientService $tallyClient): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'Tally field options fetched successfully.',
            'data' => $tallyClient->tallyFieldOptions(),
        ]);
    }

    public function createLedger(Request $request, TallyJsonMiddlewareService $middleware): JsonResponse
    {
        $payload = $request->validate([
            'ledger_name' => ['required', 'string', 'max:255'],
            'parent' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['nullable', 'numeric'],
        ]);

        return $this->jsonResult($middleware->createLedger($payload));
    }

    public function createItem(Request $request, TallyJsonMiddlewareService $middleware): JsonResponse
    {
        $payload = $request->validate([
            'item_name' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:100'],
            'stock_group' => ['nullable', 'string', 'max:255'],
            'parent' => ['nullable', 'string', 'max:255'],
            'rate' => ['nullable', 'numeric'],
        ]);

        if (empty($payload['item_name']) && empty($payload['name'])) {
            return response()->json([
                'status' => false,
                'message' => 'item_name or name is required.',
            ], 422);
        }

        return $this->jsonResult($middleware->createItem($payload));
    }

    public function createSalesVoucher(Request $request, TallyJsonMiddlewareService $middleware): JsonResponse
    {
        $payload = $request->validate([
            'invoice_no' => ['nullable', 'string', 'max:255'],
            'voucher_no' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'customer' => ['required', 'string', 'max:255'],
            'customer_parent' => ['nullable', 'string', 'max:255'],
            'sales_ledger' => ['nullable', 'string', 'max:255'],
            'grand_total' => ['nullable', 'numeric'],
            'narration' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.qty' => ['nullable', 'numeric'],
            'items.*.quantity' => ['nullable', 'numeric'],
            'items.*.rate' => ['nullable', 'numeric'],
            'items.*.amount' => ['nullable', 'numeric'],
            'items.*.unit' => ['nullable', 'string', 'max:100'],
            'items.*.stock_group' => ['nullable', 'string', 'max:255'],
        ]);

        return $this->jsonResult($middleware->createSalesVoucher($payload));
    }

    public function createExpenseVoucher(Request $request, TallyJsonMiddlewareService $middleware): JsonResponse
    {
        $payload = $request->validate([
            'voucher_no' => ['nullable', 'string', 'max:255'],
            'expense_no' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'employee_name' => ['nullable', 'string', 'max:255'],
            'expense_ledger' => ['nullable', 'string', 'max:255'],
            'expense_parent' => ['nullable', 'string', 'max:255'],
            'payment_ledger' => ['nullable', 'string', 'max:255'],
            'payment_parent' => ['nullable', 'string', 'max:255'],
            'voucher_type' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:2000'],
            'narration' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->jsonResult($middleware->createExpenseVoucher($payload));
    }

    public function createJournalVoucher(Request $request, TallyJsonMiddlewareService $middleware): JsonResponse
    {
        $payload = $request->validate([
            'voucher_no' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'narration' => ['nullable', 'string', 'max:2000'],
            'entries' => ['required', 'array', 'min:2'],
            'entries.*.ledger' => ['required', 'string', 'max:255'],
            'entries.*.parent' => ['nullable', 'string', 'max:255'],
            'entries.*.type' => ['nullable', 'string', 'max:20'],
            'entries.*.entry_type' => ['nullable', 'string', 'max:20'],
            'entries.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        return $this->jsonResult($middleware->createJournalVoucher($payload));
    }

    public function logs(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tally_sync_logs')) {
            return response()->json([
                'status' => false,
                'message' => 'tally_sync_logs table not found. Run migrations.',
                'data' => [],
            ], 422);
        }

        $limit = max(1, min(500, (int) $request->input('limit', 50)));

        return response()->json([
            'status' => true,
            'data' => TallySyncLog::query()->orderByDesc('id')->limit($limit)->get(),
        ]);
    }

    private function jsonResult(array $result): JsonResponse
    {
        return response()->json($result, ($result['status'] ?? false) ? 200 : 422);
    }

    private function connectionParams(array $validated, TallyClientService $tallyClient): array
    {
        $settings = $tallyClient->activeSettings();
        $host = trim((string) ($validated['host'] ?? $settings?->host ?? ''));
        $xmlPort = (int) ($validated['xml_port'] ?? $tallyClient->resolvedXmlPort($settings));
        $companyName = trim((string) ($validated['company_name'] ?? $tallyClient->defaultCompanyName() ?? ''));

        return [$host ?: null, $xmlPort, $companyName ?: null];
    }

    private function exportResult(array $result): JsonResponse
    {
        return response()->json([
            'status' => (bool) ($result['status'] ?? false),
            'message' => $result['message'] ?? '',
            'count' => $result['count'] ?? count($result['data'] ?? []),
            'current_company' => $result['current_company'] ?? null,
            'data' => $result['data'] ?? [],
            'endpoint' => $result['endpoint'] ?? null,
            'http_status' => $result['http_status'] ?? null,
            'tally_response' => $result['parsed'] ?? [],
        ], ($result['status'] ?? false) ? 200 : 422);
    }
}
