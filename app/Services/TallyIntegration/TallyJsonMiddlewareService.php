<?php

namespace App\Services\TallyIntegration;

use App\Models\TallySyncLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TallyJsonMiddlewareService
{
    public function __construct(
        private readonly TallyClientService $client,
    ) {}

    public function createLedger(array $payload): array
    {
        $ledgerName = trim((string) ($payload['ledger_name'] ?? ''));
        $parent = trim((string) ($payload['parent'] ?? 'Sundry Debtors'));
        $openingBalance = (float) ($payload['opening_balance'] ?? 0);

        $result = $this->importWithFallback(
            reportName: 'All Masters',
            context: 'json_ledger_create',
            buildMessageXml: fn (string $action) => $this->ledgerXml($ledgerName, $parent, $action, $openingBalance),
        );

        return $this->finalize('ledger', null, 'upsert', $payload, $result);
    }

    public function createItem(array $payload): array
    {
        $itemName = trim((string) ($payload['item_name'] ?? $payload['name'] ?? ''));
        $unit = trim((string) ($payload['unit'] ?? 'Nos'));
        $stockGroup = trim((string) ($payload['stock_group'] ?? $payload['parent'] ?? 'Primary'));
        $rate = (float) ($payload['rate'] ?? 0);

        $dependencies = [
            'unit' => $this->ensureUnit($unit),
            'stock_group' => $this->ensureStockGroup($stockGroup),
        ];
        if ($failure = $this->firstFailedDependencyMessage($dependencies)) {
            return $this->finalize('item', null, 'upsert', $payload, [
                'status' => false,
                'message' => 'Item dependency sync failed: '.$failure,
                'parsed' => [],
                'dependency_sync' => $dependencies,
            ]);
        }

        $result = $this->importWithFallback(
            reportName: 'All Masters',
            context: 'json_item_create',
            buildMessageXml: fn (string $action) => $this->stockItemXml($itemName, $stockGroup, $unit, $action, $rate),
        );
        $result['dependency_sync'] = $dependencies;

        return $this->finalize('item', null, 'upsert', $payload, $result);
    }

    public function createSalesVoucher(array $payload): array
    {
        $voucherNo = trim((string) ($payload['invoice_no'] ?? $payload['voucher_no'] ?? ''));
        $dateYmd = $this->tallyDate((string) ($payload['date'] ?? now()->toDateString()));
        $customer = trim((string) ($payload['customer'] ?? $payload['party_ledger'] ?? ''));
        $salesLedger = trim((string) ($payload['sales_ledger'] ?? $this->client->settingValue('sales_ledger_name', 'Sales')));
        $items = $payload['items'] ?? [];
        $narration = trim((string) ($payload['narration'] ?? ''));

        $dependencies = [
            'customer' => $this->ensureLedger($customer, (string) ($payload['customer_parent'] ?? 'Sundry Debtors')),
            'sales_ledger' => $this->ensureLedger($salesLedger, 'Sales Accounts'),
        ];

        foreach ($items as $index => $item) {
            $unit = trim((string) ($item['unit'] ?? 'Nos'));
            $itemName = trim((string) ($item['name'] ?? $item['item_name'] ?? ''));
            $stockGroup = trim((string) ($item['stock_group'] ?? 'Primary'));
            $dependencies['item_'.$index.'_unit'] = $this->ensureUnit($unit);
            $dependencies['item_'.$index] = $this->ensureStockItem($itemName, $stockGroup, $unit, (float) ($item['rate'] ?? 0));
        }

        if ($failure = $this->firstFailedDependencyMessage($dependencies)) {
            return $this->finalize('sales_voucher', null, 'upsert', $payload, [
                'status' => false,
                'message' => 'Sales voucher dependency sync failed: '.$failure,
                'parsed' => [],
                'dependency_sync' => $dependencies,
            ]);
        }

        $result = $this->importWithFallback(
            reportName: 'Vouchers',
            context: 'json_sales_voucher_create',
            buildMessageXml: fn (string $action) => $this->salesVoucherXml($voucherNo, $dateYmd, $customer, $salesLedger, $items, $payload, $narration, $action),
        );
        $result['dependency_sync'] = $dependencies;

        return $this->finalize('sales_voucher', null, 'upsert', $payload, $result);
    }

    public function createExpenseVoucher(array $payload): array
    {
        $voucherNo = trim((string) ($payload['voucher_no'] ?? $payload['expense_no'] ?? ''));
        $dateYmd = $this->tallyDate((string) ($payload['date'] ?? now()->toDateString()));
        $expenseLedger = trim((string) ($payload['expense_ledger'] ?? $this->client->settingValue('expense_ledger_name', 'Employee Expense')));
        $paymentLedger = trim((string) ($payload['payment_ledger'] ?? $this->client->settingValue('cash_ledger_name', 'Cash')));
        $amount = (float) ($payload['amount'] ?? 0);
        $voucherType = trim((string) ($payload['voucher_type'] ?? 'Payment'));
        $narration = trim((string) ($payload['narration'] ?? $payload['description'] ?? ''));

        $dependencies = [
            'expense_ledger' => $this->ensureLedger($expenseLedger, (string) ($payload['expense_parent'] ?? 'Indirect Expenses')),
            'payment_ledger' => $this->ensureLedger($paymentLedger, (string) ($payload['payment_parent'] ?? 'Cash-in-Hand')),
        ];
        if ($failure = $this->firstFailedDependencyMessage($dependencies)) {
            return $this->finalize('expense_voucher', null, 'upsert', $payload, [
                'status' => false,
                'message' => 'Expense voucher dependency sync failed: '.$failure,
                'parsed' => [],
                'dependency_sync' => $dependencies,
            ]);
        }

        $result = $this->importWithFallback(
            reportName: 'Vouchers',
            context: 'json_expense_voucher_create',
            buildMessageXml: fn (string $action) => $this->twoLedgerVoucherXml($voucherType, $voucherNo, $dateYmd, $expenseLedger, $paymentLedger, $amount, $narration, $action),
        );
        $result['dependency_sync'] = $dependencies;

        return $this->finalize('expense_voucher', null, 'upsert', $payload, $result);
    }

    public function createJournalVoucher(array $payload): array
    {
        $voucherNo = trim((string) ($payload['voucher_no'] ?? ''));
        $dateYmd = $this->tallyDate((string) ($payload['date'] ?? now()->toDateString()));
        $entries = $payload['entries'] ?? [];
        $narration = trim((string) ($payload['narration'] ?? ''));

        $dependencies = [];
        foreach ($entries as $index => $entry) {
            $dependencies['ledger_'.$index] = $this->ensureLedger(
                (string) ($entry['ledger'] ?? ''),
                (string) ($entry['parent'] ?? 'Indirect Expenses')
            );
        }
        if ($failure = $this->firstFailedDependencyMessage($dependencies)) {
            return $this->finalize('journal_voucher', null, 'upsert', $payload, [
                'status' => false,
                'message' => 'Journal voucher dependency sync failed: '.$failure,
                'parsed' => [],
                'dependency_sync' => $dependencies,
            ]);
        }

        $result = $this->importWithFallback(
            reportName: 'Vouchers',
            context: 'json_journal_voucher_create',
            buildMessageXml: fn (string $action) => $this->journalVoucherXml($voucherNo, $dateYmd, $entries, $narration, $action),
        );
        $result['dependency_sync'] = $dependencies;

        return $this->finalize('journal_voucher', null, 'upsert', $payload, $result);
    }

    private function importWithFallback(string $reportName, string $context, callable $buildMessageXml): array
    {
        $createResult = $this->client->importData($reportName, $buildMessageXml('Create'), $context.'_create');
        if ($createResult['status'] ?? false) {
            return $createResult;
        }

        $alterResult = $this->client->importData($reportName, $buildMessageXml('Alter'), $context.'_alter');
        if ($alterResult['status'] ?? false) {
            return $alterResult;
        }

        $alterResult['message'] = trim(($createResult['message'] ?? 'Create failed').'; '.($alterResult['message'] ?? 'Alter failed'));

        return $alterResult;
    }

    private function ensureLedger(string $ledgerName, string $parent): array
    {
        $ledgerName = trim($ledgerName);
        $parent = trim($parent);
        if ($ledgerName === '' || $parent === '') {
            return ['status' => false, 'message' => 'Ledger name or parent group is empty.'];
        }

        return $this->allowAlreadyExists($this->importWithFallback(
            'All Masters',
            'json_ledger_dependency',
            fn (string $action) => $this->ledgerXml($ledgerName, $parent, $action),
        ), 'Ledger already exists in Tally.');
    }

    private function ensureUnit(string $unitName): array
    {
        $unitName = trim($unitName);
        if ($unitName === '') {
            return ['status' => false, 'message' => 'Unit name is empty.'];
        }

        return $this->allowAlreadyExists($this->importWithFallback(
            'All Masters',
            'json_unit_dependency',
            fn (string $action) => $this->unitXml($unitName, $action),
        ), 'Unit already exists in Tally.');
    }

    private function ensureStockGroup(string $stockGroup): array
    {
        $stockGroup = trim($stockGroup);
        if ($stockGroup === '' || strcasecmp($stockGroup, 'Primary') === 0) {
            return ['status' => true, 'message' => 'Stock group is Primary or empty; no transfer required.'];
        }

        return $this->allowAlreadyExists($this->importWithFallback(
            'All Masters',
            'json_stock_group_dependency',
            fn (string $action) => $this->stockGroupXml($stockGroup, $action),
        ), 'Stock group already exists in Tally.');
    }

    private function ensureStockItem(string $itemName, string $stockGroup, string $unit, float $rate = 0): array
    {
        if (trim($itemName) === '') {
            return ['status' => false, 'message' => 'Item name is empty.'];
        }

        $stockGroupResult = $this->ensureStockGroup($stockGroup);
        if (! (bool) ($stockGroupResult['status'] ?? false)) {
            return [
                'status' => false,
                'message' => 'Stock item dependency failed: '.($stockGroupResult['message'] ?? 'Stock group sync failed.'),
                'dependency_sync' => [
                    'stock_group' => $stockGroupResult,
                ],
            ];
        }

        return $this->allowAlreadyExists($this->importWithFallback(
            'All Masters',
            'json_stock_item_dependency',
            fn (string $action) => $this->stockItemXml($itemName, $stockGroup, $unit, $action, $rate),
        ), 'Stock item already exists in Tally.');
    }

    private function ledgerXml(string $ledgerName, string $parent, string $action, float $openingBalance = 0): string
    {
        $xml = '<TALLYMESSAGE xmlns:UDF="TallyUDF">';
        $xml .= '<LEDGER NAME="'.$this->esc($ledgerName).'" ACTION="'.$this->esc($action).'">';
        $xml .= '<NAME>'.$this->esc($ledgerName).'</NAME>';
        $xml .= '<PARENT>'.$this->esc($parent).'</PARENT>';
        $xml .= '<ISBILLWISEON>No</ISBILLWISEON>';
        if ($openingBalance != 0.0) {
            $xml .= '<OPENINGBALANCE>'.$this->amount($openingBalance).'</OPENINGBALANCE>';
        }
        $xml .= '</LEDGER>';
        $xml .= '</TALLYMESSAGE>';

        return $xml;
    }

    private function unitXml(string $unitName, string $action): string
    {
        return '<TALLYMESSAGE xmlns:UDF="TallyUDF"><UNIT NAME="'.$this->esc($unitName).'" ACTION="'.$this->esc($action).'"><NAME>'.$this->esc($unitName).'</NAME><ISSIMPLEUNIT>Yes</ISSIMPLEUNIT></UNIT></TALLYMESSAGE>';
    }

    private function stockGroupXml(string $stockGroup, string $action): string
    {
        return '<TALLYMESSAGE xmlns:UDF="TallyUDF"><STOCKGROUP NAME="'.$this->esc($stockGroup).'" ACTION="'.$this->esc($action).'"><NAME>'.$this->esc($stockGroup).'</NAME><PARENT>Primary</PARENT><ISADDABLE>Yes</ISADDABLE></STOCKGROUP></TALLYMESSAGE>';
    }

    private function stockItemXml(string $itemName, string $stockGroup, string $unit, string $action, float $rate = 0): string
    {
        $xml = '<TALLYMESSAGE xmlns:UDF="TallyUDF">';
        $xml .= '<STOCKITEM NAME="'.$this->esc($itemName).'" ACTION="'.$this->esc($action).'">';
        $xml .= '<NAME>'.$this->esc($itemName).'</NAME>';
        $xml .= '<PARENT>'.$this->esc($stockGroup ?: 'Primary').'</PARENT>';
        $xml .= '<BASEUNITS>'.$this->esc($unit ?: 'Nos').'</BASEUNITS>';
        if ($rate > 0) {
            $xml .= '<RATE>'.$this->esc($this->amount($rate).'/'.($unit ?: 'Nos')).'</RATE>';
        }
        $xml .= '</STOCKITEM>';
        $xml .= '</TALLYMESSAGE>';

        return $xml;
    }

    private function salesVoucherXml(string $voucherNo, string $dateYmd, string $customer, string $salesLedger, array $items, array $payload, string $narration, string $action): string
    {
        $grandTotal = (float) ($payload['grand_total'] ?? 0);
        $inventoryXml = '';
        $itemTotal = 0.0;

        foreach ($items as $item) {
            $name = trim((string) ($item['name'] ?? $item['item_name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $unit = trim((string) ($item['unit'] ?? 'Nos'));
            $qty = (float) ($item['qty'] ?? $item['quantity'] ?? 0);
            $rate = (float) ($item['rate'] ?? 0);
            $amount = (float) ($item['amount'] ?? ($qty * $rate));
            $itemTotal += $amount;
            $inventoryXml .= '<ALLINVENTORYENTRIES.LIST>';
            $inventoryXml .= '<STOCKITEMNAME>'.$this->esc($name).'</STOCKITEMNAME>';
            $inventoryXml .= '<ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>';
            $inventoryXml .= '<RATE>'.$this->esc($this->amount($rate).'/'.$unit).'</RATE>';
            $inventoryXml .= '<AMOUNT>'.$this->amount(-1 * abs($amount)).'</AMOUNT>';
            $inventoryXml .= '<ACTUALQTY>'.$this->esc($this->quantity(-1 * abs($qty), $unit)).'</ACTUALQTY>';
            $inventoryXml .= '<BILLEDQTY>'.$this->esc($this->quantity(-1 * abs($qty), $unit)).'</BILLEDQTY>';
            $inventoryXml .= '<ACCOUNTINGALLOCATIONS.LIST>';
            $inventoryXml .= '<LEDGERNAME>'.$this->esc($salesLedger).'</LEDGERNAME>';
            $inventoryXml .= '<ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>';
            $inventoryXml .= '<AMOUNT>'.$this->amount(-1 * abs($amount)).'</AMOUNT>';
            $inventoryXml .= '</ACCOUNTINGALLOCATIONS.LIST>';
            $inventoryXml .= '</ALLINVENTORYENTRIES.LIST>';
        }

        if ($grandTotal <= 0) {
            $grandTotal = $itemTotal;
        }

        $xml = '<TALLYMESSAGE xmlns:UDF="TallyUDF">';
        $xml .= '<VOUCHER VCHTYPE="Sales" ACTION="'.$this->esc($action).'" OBJVIEW="Invoice Voucher View">';
        $xml .= '<DATE>'.$dateYmd.'</DATE>';
        $xml .= '<VOUCHERTYPENAME>Sales</VOUCHERTYPENAME>';
        if ($voucherNo !== '') {
            $xml .= '<VOUCHERNUMBER>'.$this->esc($voucherNo).'</VOUCHERNUMBER>';
        }
        $xml .= '<PARTYLEDGERNAME>'.$this->esc($customer).'</PARTYLEDGERNAME>';
        $xml .= '<PERSISTEDVIEW>Invoice Voucher View</PERSISTEDVIEW><ISINVOICE>Yes</ISINVOICE>';
        if ($narration !== '') {
            $xml .= '<NARRATION>'.$this->esc($narration).'</NARRATION>';
        }
        $xml .= $inventoryXml;
        $xml .= '<LEDGERENTRIES.LIST><LEDGERNAME>'.$this->esc($customer).'</LEDGERNAME><ISDEEMEDPOSITIVE>No</ISDEEMEDPOSITIVE><AMOUNT>'.$this->amount(abs($grandTotal)).'</AMOUNT></LEDGERENTRIES.LIST>';
        $xml .= '</VOUCHER></TALLYMESSAGE>';

        return $xml;
    }

    private function twoLedgerVoucherXml(string $voucherType, string $voucherNo, string $dateYmd, string $debitLedger, string $creditLedger, float $amount, string $narration, string $action): string
    {
        $xml = '<TALLYMESSAGE xmlns:UDF="TallyUDF">';
        $xml .= '<VOUCHER VCHTYPE="'.$this->esc($voucherType).'" ACTION="'.$this->esc($action).'">';
        $xml .= '<DATE>'.$dateYmd.'</DATE><VOUCHERTYPENAME>'.$this->esc($voucherType).'</VOUCHERTYPENAME>';
        if ($voucherNo !== '') {
            $xml .= '<VOUCHERNUMBER>'.$this->esc($voucherNo).'</VOUCHERNUMBER>';
        }
        if ($narration !== '') {
            $xml .= '<NARRATION>'.$this->esc($narration).'</NARRATION>';
        }
        $xml .= '<LEDGERENTRIES.LIST><LEDGERNAME>'.$this->esc($debitLedger).'</LEDGERNAME><ISDEEMEDPOSITIVE>No</ISDEEMEDPOSITIVE><AMOUNT>'.$this->amount(abs($amount)).'</AMOUNT></LEDGERENTRIES.LIST>';
        $xml .= '<LEDGERENTRIES.LIST><LEDGERNAME>'.$this->esc($creditLedger).'</LEDGERNAME><ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE><AMOUNT>'.$this->amount(-1 * abs($amount)).'</AMOUNT></LEDGERENTRIES.LIST>';
        $xml .= '</VOUCHER></TALLYMESSAGE>';

        return $xml;
    }

    private function journalVoucherXml(string $voucherNo, string $dateYmd, array $entries, string $narration, string $action): string
    {
        $xml = '<TALLYMESSAGE xmlns:UDF="TallyUDF"><VOUCHER VCHTYPE="Journal" ACTION="'.$this->esc($action).'">';
        $xml .= '<DATE>'.$dateYmd.'</DATE><VOUCHERTYPENAME>Journal</VOUCHERTYPENAME>';
        if ($voucherNo !== '') {
            $xml .= '<VOUCHERNUMBER>'.$this->esc($voucherNo).'</VOUCHERNUMBER>';
        }
        if ($narration !== '') {
            $xml .= '<NARRATION>'.$this->esc($narration).'</NARRATION>';
        }
        foreach ($entries as $entry) {
            $ledger = trim((string) ($entry['ledger'] ?? ''));
            if ($ledger === '') {
                continue;
            }
            $amount = abs((float) ($entry['amount'] ?? 0));
            $type = strtolower((string) ($entry['type'] ?? $entry['entry_type'] ?? 'debit'));
            $isCredit = in_array($type, ['credit', 'cr'], true);
            $xml .= '<LEDGERENTRIES.LIST>';
            $xml .= '<LEDGERNAME>'.$this->esc($ledger).'</LEDGERNAME>';
            $xml .= '<ISDEEMEDPOSITIVE>'.($isCredit ? 'Yes' : 'No').'</ISDEEMEDPOSITIVE>';
            $xml .= '<AMOUNT>'.$this->amount($isCredit ? -1 * $amount : $amount).'</AMOUNT>';
            $xml .= '</LEDGERENTRIES.LIST>';
        }
        $xml .= '</VOUCHER></TALLYMESSAGE>';

        return $xml;
    }

    private function finalize(string $entityType, ?int $entityId, string $operation, array $requestPayload, array $result): array
    {
        $parsed = $result['parsed'] ?? [];
        $response = [
            'status' => (bool) ($result['status'] ?? false),
            'message' => (string) ($result['message'] ?? ''),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'operation' => $operation,
            'created' => $parsed['created'] ?? 0,
            'altered' => $parsed['altered'] ?? 0,
            'deleted' => $parsed['deleted'] ?? 0,
            'errors' => $parsed['errors'] ?? 0,
            'exceptions' => $parsed['exceptions'] ?? 0,
            'line_errors' => $parsed['line_errors'] ?? [],
            'http_status' => $result['http_status'] ?? null,
            'endpoint' => $result['endpoint'] ?? null,
            'tally_response' => $parsed,
            'dependency_sync' => $result['dependency_sync'] ?? null,
        ];

        $this->writeSyncLog([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'operation' => $operation,
            'status' => $response['status'] ? 'success' : 'failed',
            'message' => $response['message'],
            'request_payload' => json_encode($requestPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'response_payload' => json_encode([
                'response_body' => $result['response_body'] ?? null,
                'parsed' => $parsed,
                'http_status' => $result['http_status'] ?? null,
                'endpoint' => $result['endpoint'] ?? null,
                'dependency_sync' => $result['dependency_sync'] ?? null,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'synced_at' => $response['status'] ? now() : null,
        ]);

        return $response;
    }

    private function writeSyncLog(array $payload): void
    {
        try {
            if (Schema::hasTable('tally_sync_logs')) {
                TallySyncLog::create($payload);
            }
        } catch (\Throwable $e) {
            Log::error('Unable to write Tally JSON middleware sync log', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    private function allowAlreadyExists(array $result, string $message): array
    {
        if (($result['status'] ?? false) || ! preg_match('/already\s+exists|exists\s+already|duplicate/i', (string) ($result['message'] ?? ''))) {
            return $result;
        }

        $result['status'] = true;
        $result['message'] = $message;

        return $result;
    }

    private function firstFailedDependencyMessage(array $dependencyResults): ?string
    {
        foreach ($dependencyResults as $name => $dependency) {
            if (! is_array($dependency)) {
                continue;
            }

            if (array_key_exists('status', $dependency) && ! (bool) ($dependency['status'] ?? false)) {
                return ucfirst(str_replace('_', ' ', (string) $name)).': '.($dependency['message'] ?? 'Failed.');
            }
        }

        return null;
    }

    private function tallyDate(string $date): string
    {
        $timestamp = strtotime($date);

        return date('Ymd', $timestamp ?: time());
    }

    private function amount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    private function quantity(float $quantity, string $unit): string
    {
        return number_format($quantity, 3, '.', '').' '.$unit;
    }

    private function esc(?string $value): string
    {
        return $this->client->xmlEscape($value);
    }
}
