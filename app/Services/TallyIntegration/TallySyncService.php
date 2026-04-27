<?php

namespace App\Services\TallyIntegration;

use App\Models\Items\Item;
use App\Models\Party\Party;
use App\Models\Sale\Sale;
use App\Models\TallySyncLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TallySyncService
{
    public function __construct(
        private readonly TallyClientService $client,
        private readonly TallyMappingService $mappingService,
    ) {}

    public function syncItemById(int $itemId, string $operation = 'upsert'): array
    {
        $item = Item::with(['tax', 'baseUnit', 'category', 'itemGeneralQuantities'])->find($itemId);
        if (! $item) {
            return $this->finalizeAndReturn(
                entityType: 'item',
                entityId: $itemId,
                operation: $operation,
                success: false,
                message: 'Item record not found for Tally sync.',
            );
        }

        $itemName = (string) $this->mappingService->valueForTarget('item', $item, 'NAME', 'name', $item->name);
        $baseUnit = (string) $this->mappingService->valueForTarget('item', $item, 'BASEUNITS', 'baseUnit.name', $item->baseUnit?->name ?: 'Nos');
        $stockGroup = (string) $this->mappingService->valueForTarget('item', $item, 'PARENT', 'category.name', $item->category?->name ?: 'Primary');
        $alias = (string) $this->mappingService->valueForTarget('item', $item, 'ALIAS', 'item_code', $item->item_code ?: '');
        $hsnCode = (string) $this->mappingService->valueForTarget('item', $item, 'HSNCODE', 'hsn', $item->hsn ?: '');
        $description = (string) $this->mappingService->valueForTarget('item', $item, 'DESCRIPTION', 'description', $item->description ?: '');
        $rate = (float) $this->mappingService->valueForTarget('item', $item, 'RATE', 'sale_price', $item->sale_price ?: 0);
        $openingQty = (float) ($item->itemGeneralQuantities?->sum('quantity') ?? 0);

        $mappedPayload = [
            'NAME' => $itemName,
            'PARENT' => $stockGroup,
            'BASEUNITS' => $baseUnit,
            'ALIAS' => $alias,
            'HSNCODE' => $hsnCode,
            'DESCRIPTION' => $description,
            'RATE' => $rate,
            'OPENINGBALANCE' => $openingQty,
        ];

        $result = $this->pushWithFallback(
            reportName: 'All Masters',
            context: 'item_sync',
            operation: $operation,
            buildMessageXml: function (string $action) use ($itemName, $stockGroup, $baseUnit, $alias, $hsnCode, $description, $rate, $openingQty) {
                $xml = '<TALLYMESSAGE xmlns:UDF="TallyUDF">';
                $xml .= '<STOCKITEM NAME="'.$this->esc($itemName).'" ACTION="'.$this->esc($action).'">';
                $xml .= '<NAME>'.$this->esc($itemName).'</NAME>';
                $xml .= '<PARENT>'.$this->esc($stockGroup).'</PARENT>';
                $xml .= '<BASEUNITS>'.$this->esc($baseUnit).'</BASEUNITS>';
                if ($alias !== '') {
                    $xml .= '<ALIAS>'.$this->esc($alias).'</ALIAS>';
                }
                if ($hsnCode !== '') {
                    $xml .= '<HSNCODE>'.$this->esc($hsnCode).'</HSNCODE>';
                }
                if ($description !== '') {
                    $xml .= '<NARRATION>'.$this->esc($description).'</NARRATION>';
                }
                if ($rate > 0) {
                    $xml .= '<RATE>'.$this->esc(number_format($rate, 2, '.', '').'/'.$baseUnit).'</RATE>';
                }
                if ($openingQty > 0) {
                    $xml .= '<OPENINGBALANCE>'.$this->esc(number_format($openingQty, 3, '.', '').' '.$baseUnit).'</OPENINGBALANCE>';
                }
                $xml .= '</STOCKITEM>';
                $xml .= '</TALLYMESSAGE>';

                return $xml;
            }
        );

        return $this->finalizeAndReturn(
            entityType: 'item',
            entityId: $item->id,
            operation: $operation,
            success: (bool) ($result['status'] ?? false),
            message: (string) ($result['message'] ?? 'Item sync failed.'),
            requestPayload: [
                'mapped_payload' => $mappedPayload,
                'request_xml' => $result['request_xml'] ?? null,
            ],
            responsePayload: [
                'response_body' => $result['response_body'] ?? null,
                'parsed' => $result['parsed'] ?? [],
                'http_status' => $result['http_status'] ?? null,
            ],
        );
    }

    public function syncPartyById(int $partyId, string $operation = 'upsert'): array
    {
        $party = Party::find($partyId);
        if (! $party) {
            return $this->finalizeAndReturn(
                entityType: 'party',
                entityId: $partyId,
                operation: $operation,
                success: false,
                message: 'Party record not found for Tally sync.',
            );
        }

        $ledgerName = (string) $this->mappingService->valueForTarget('party', $party, 'NAME', 'company_name', $party->company_name ?: $party->primary_name);
        $partyTypeRaw = (string) $this->mappingService->valueForTarget('party', $party, 'PARENT', 'vendor_type', $party->vendor_type ?: 'customer');
        $parentLedger = $this->normalizePartyParentLedger($partyTypeRaw);
        $mobile = (string) $this->mappingService->valueForTarget('party', $party, 'MOBILE', 'primary_mobile', $party->primary_mobile ?: '');
        $email = (string) $this->mappingService->valueForTarget('party', $party, 'EMAIL', 'primary_email', $party->primary_email ?: '');
        $gstin = (string) $this->mappingService->valueForTarget('party', $party, 'PARTYGSTIN', 'company_gst', $party->company_gst ?: '');
        $pan = (string) $this->mappingService->valueForTarget('party', $party, 'INCOMETAXNUMBER', 'company_pan', $party->company_pan ?: '');
        $billingAddress = (string) $this->mappingService->valueForTarget('party', $party, 'ADDRESS', 'billing_address', $party->billing_address ?: '');
        $shippingAddress = (string) $this->mappingService->valueForTarget('party', $party, 'SHIPPINGADDRESS', 'shipping_address', $party->shipping_address ?: '');

        $mappedPayload = [
            'NAME' => $ledgerName,
            'PARENT' => $parentLedger,
            'MOBILE' => $mobile,
            'EMAIL' => $email,
            'PARTYGSTIN' => $gstin,
            'INCOMETAXNUMBER' => $pan,
            'ADDRESS' => $billingAddress,
            'SHIPPINGADDRESS' => $shippingAddress,
        ];

        $result = $this->pushWithFallback(
            reportName: 'All Masters',
            context: 'party_sync',
            operation: $operation,
            buildMessageXml: function (string $action) use ($ledgerName, $parentLedger, $mobile, $email, $gstin, $pan, $billingAddress, $shippingAddress) {
                $xml = '<TALLYMESSAGE xmlns:UDF="TallyUDF">';
                $xml .= '<LEDGER NAME="'.$this->esc($ledgerName).'" ACTION="'.$this->esc($action).'">';
                $xml .= '<NAME>'.$this->esc($ledgerName).'</NAME>';
                $xml .= '<PARENT>'.$this->esc($parentLedger).'</PARENT>';
                $xml .= '<ISBILLWISEON>Yes</ISBILLWISEON>';
                $xml .= '<MAILINGNAME>'.$this->esc($ledgerName).'</MAILINGNAME>';
                if ($billingAddress !== '') {
                    $xml .= '<ADDRESS.LIST TYPE="String"><ADDRESS>'.$this->esc($billingAddress).'</ADDRESS></ADDRESS.LIST>';
                }
                if ($shippingAddress !== '') {
                    $xml .= '<SHIPPINGADDRESS>'.$this->esc($shippingAddress).'</SHIPPINGADDRESS>';
                }
                if ($mobile !== '') {
                    $xml .= '<MOBILE>'.$this->esc($mobile).'</MOBILE>';
                }
                if ($email !== '') {
                    $xml .= '<EMAIL>'.$this->esc($email).'</EMAIL>';
                }
                if ($gstin !== '') {
                    $xml .= '<GSTREGISTRATIONTYPE>Regular</GSTREGISTRATIONTYPE>';
                    $xml .= '<PARTYGSTIN>'.$this->esc($gstin).'</PARTYGSTIN>';
                }
                if ($pan !== '') {
                    $xml .= '<INCOMETAXNUMBER>'.$this->esc($pan).'</INCOMETAXNUMBER>';
                }
                $xml .= '</LEDGER>';
                $xml .= '</TALLYMESSAGE>';

                return $xml;
            }
        );

        return $this->finalizeAndReturn(
            entityType: 'party',
            entityId: $party->id,
            operation: $operation,
            success: (bool) ($result['status'] ?? false),
            message: (string) ($result['message'] ?? 'Party sync failed.'),
            requestPayload: [
                'mapped_payload' => $mappedPayload,
                'request_xml' => $result['request_xml'] ?? null,
            ],
            responsePayload: [
                'response_body' => $result['response_body'] ?? null,
                'parsed' => $result['parsed'] ?? [],
                'http_status' => $result['http_status'] ?? null,
            ],
        );
    }

    public function syncSaleById(int $saleId, string $operation = 'upsert'): array
    {
        $sale = Sale::with([
            'party',
            'itemTransaction.item',
            'itemTransaction.unit',
            'itemTransaction.tax',
        ])->find($saleId);

        if (! $sale) {
            return $this->finalizeAndReturn(
                entityType: 'sale',
                entityId: $saleId,
                operation: $operation,
                success: false,
                message: 'Sale record not found for Tally sync.',
            );
        }

        if (! $sale->party) {
            return $this->finalizeAndReturn(
                entityType: 'sale',
                entityId: $sale->id,
                operation: $operation,
                success: false,
                message: 'Sale party not found for Tally sync.',
            );
        }

        $lineRecords = $sale->itemTransaction;
        if ($lineRecords->isEmpty()) {
            return $this->finalizeAndReturn(
                entityType: 'sale',
                entityId: $sale->id,
                operation: $operation,
                success: false,
                message: 'Sale items not found for Tally sync.',
            );
        }

        // Ensure dependencies in Tally: Party + Items
        $partySync = $this->syncPartyById((int) $sale->party_id, 'upsert');
        $itemSyncSummary = [];
        $itemIds = $lineRecords->pluck('item_id')->filter()->unique()->values();
        foreach ($itemIds as $itemId) {
            $itemSyncSummary[] = $this->syncItemById((int) $itemId, 'upsert');
        }

        $voucherType = 'Sales';
        $voucherNo = (string) $this->mappingService->valueForTarget('sale', $sale, 'VOUCHERNUMBER', 'sale_code', $sale->sale_code);
        $partyLedgerName = (string) $this->mappingService->valueForTarget('sale', $sale, 'PARTYLEDGERNAME', 'party.company_name', $sale->party->company_name ?: '');
        $referenceNo = (string) $this->mappingService->valueForTarget('sale', $sale, 'REFERENCE', 'reference_no', $sale->reference_no ?: '');
        $narration = (string) $this->mappingService->valueForTarget('sale', $sale, 'NARRATION', 'note', $sale->note ?: '');
        $grandTotal = (float) $this->mappingService->valueForTarget('sale', $sale, 'AMOUNT', 'grand_total', $sale->grand_total ?: 0);
        $salesLedgerName = 'Sales A/c';
        $dateYmd = date('Ymd', strtotime((string) $sale->sale_date));

        $inventoryEntryXml = '';
        foreach ($lineRecords as $line) {
            $itemName = (string) $this->mappingService->valueForTarget('sale_item', $line, 'STOCKITEMNAME', 'item.name', $line->item?->name ?: '');
            if ($itemName === '') {
                continue;
            }

            $unitName = (string) $this->mappingService->valueForTarget('sale_item', $line, 'UNIT', 'unit.name', $line->unit?->name ?: 'Nos');
            $quantity = (float) $this->mappingService->valueForTarget('sale_item', $line, 'BILLEDQTY', 'quantity', $line->quantity ?: 0);
            $rate = (float) $this->mappingService->valueForTarget('sale_item', $line, 'RATE', 'unit_price', $line->unit_price ?: 0);
            $amount = (float) $this->mappingService->valueForTarget('sale_item', $line, 'AMOUNT', 'total', $line->total ?: 0);
            $description = (string) $this->mappingService->valueForTarget('sale_item', $line, 'DESCRIPTION', 'description', $line->description ?: '');

            $amountNegative = -1 * abs($amount);
            $qtyNegative = -1 * abs($quantity);
            $qtyText = number_format($qtyNegative, 3, '.', '').' '.$unitName;

            $inventoryEntryXml .= '<ALLINVENTORYENTRIES.LIST>';
            $inventoryEntryXml .= '<STOCKITEMNAME>'.$this->esc($itemName).'</STOCKITEMNAME>';
            $inventoryEntryXml .= '<ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>';
            $inventoryEntryXml .= '<RATE>'.$this->esc(number_format($rate, 2, '.', '').'/'.$unitName).'</RATE>';
            $inventoryEntryXml .= '<AMOUNT>'.$this->esc(number_format($amountNegative, 2, '.', '')).'</AMOUNT>';
            $inventoryEntryXml .= '<ACTUALQTY>'.$this->esc($qtyText).'</ACTUALQTY>';
            $inventoryEntryXml .= '<BILLEDQTY>'.$this->esc($qtyText).'</BILLEDQTY>';
            if ($description !== '') {
                $inventoryEntryXml .= '<DESCRIPTION>'.$this->esc($description).'</DESCRIPTION>';
            }
            $inventoryEntryXml .= '</ALLINVENTORYENTRIES.LIST>';
        }

        $mappedPayload = [
            'VOUCHERTYPENAME' => $voucherType,
            'VOUCHERNUMBER' => $voucherNo,
            'PARTYLEDGERNAME' => $partyLedgerName,
            'REFERENCE' => $referenceNo,
            'NARRATION' => $narration,
            'AMOUNT' => $grandTotal,
            'DATE' => $dateYmd,
            'ITEMS_COUNT' => $lineRecords->count(),
        ];

        $result = $this->pushWithFallback(
            reportName: 'Vouchers',
            context: 'sale_sync',
            operation: $operation,
            buildMessageXml: function (string $action) use ($voucherType, $voucherNo, $partyLedgerName, $referenceNo, $narration, $grandTotal, $salesLedgerName, $dateYmd, $inventoryEntryXml) {
                $voucherAmount = number_format(abs($grandTotal), 2, '.', '');
                $salesAmountNegative = number_format(-1 * abs($grandTotal), 2, '.', '');

                $xml = '<TALLYMESSAGE xmlns:UDF="TallyUDF">';
                $xml .= '<VOUCHER VCHTYPE="'.$this->esc($voucherType).'" ACTION="'.$this->esc($action).'" OBJVIEW="Invoice Voucher View">';
                $xml .= '<DATE>'.$this->esc($dateYmd).'</DATE>';
                $xml .= '<VOUCHERTYPENAME>'.$this->esc($voucherType).'</VOUCHERTYPENAME>';
                $xml .= '<VOUCHERNUMBER>'.$this->esc($voucherNo).'</VOUCHERNUMBER>';
                $xml .= '<PARTYLEDGERNAME>'.$this->esc($partyLedgerName).'</PARTYLEDGERNAME>';
                $xml .= '<PERSISTEDVIEW>Invoice Voucher View</PERSISTEDVIEW>';
                $xml .= '<ISINVOICE>Yes</ISINVOICE>';
                if ($referenceNo !== '') {
                    $xml .= '<REFERENCE>'.$this->esc($referenceNo).'</REFERENCE>';
                }
                if ($narration !== '') {
                    $xml .= '<NARRATION>'.$this->esc($narration).'</NARRATION>';
                }

                $xml .= $inventoryEntryXml;

                $xml .= '<LEDGERENTRIES.LIST>';
                $xml .= '<LEDGERNAME>'.$this->esc($partyLedgerName).'</LEDGERNAME>';
                $xml .= '<ISDEEMEDPOSITIVE>No</ISDEEMEDPOSITIVE>';
                $xml .= '<AMOUNT>'.$this->esc($voucherAmount).'</AMOUNT>';
                $xml .= '</LEDGERENTRIES.LIST>';

                $xml .= '<LEDGERENTRIES.LIST>';
                $xml .= '<LEDGERNAME>'.$this->esc($salesLedgerName).'</LEDGERNAME>';
                $xml .= '<ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>';
                $xml .= '<AMOUNT>'.$this->esc($salesAmountNegative).'</AMOUNT>';
                $xml .= '</LEDGERENTRIES.LIST>';

                $xml .= '</VOUCHER>';
                $xml .= '</TALLYMESSAGE>';

                return $xml;
            }
        );

        $responsePayload = [
            'response_body' => $result['response_body'] ?? null,
            'parsed' => $result['parsed'] ?? [],
            'http_status' => $result['http_status'] ?? null,
            'dependency_sync' => [
                'party' => $partySync,
                'items' => $itemSyncSummary,
            ],
        ];

        return $this->finalizeAndReturn(
            entityType: 'sale',
            entityId: $sale->id,
            operation: $operation,
            success: (bool) ($result['status'] ?? false),
            message: (string) ($result['message'] ?? 'Sale sync failed.'),
            requestPayload: [
                'mapped_payload' => $mappedPayload,
                'request_xml' => $result['request_xml'] ?? null,
            ],
            responsePayload: $responsePayload,
        );
    }

    private function pushWithFallback(string $reportName, string $context, string $operation, callable $buildMessageXml): array
    {
        $operation = strtolower(trim($operation));

        if ($operation === 'update') {
            return $this->client->importData(
                reportName: $reportName,
                requestDataXml: $buildMessageXml('Alter'),
                context: $context.'_alter',
            );
        }

        if ($operation === 'create') {
            return $this->client->importData(
                reportName: $reportName,
                requestDataXml: $buildMessageXml('Create'),
                context: $context.'_create',
            );
        }

        // default upsert behavior: create then alter
        $createResult = $this->client->importData(
            reportName: $reportName,
            requestDataXml: $buildMessageXml('Create'),
            context: $context.'_create',
        );

        if ($createResult['status'] ?? false) {
            return $createResult;
        }

        $alterResult = $this->client->importData(
            reportName: $reportName,
            requestDataXml: $buildMessageXml('Alter'),
            context: $context.'_alter_fallback',
        );

        if ($alterResult['status'] ?? false) {
            return $alterResult;
        }

        $alterResult['message'] = trim(
            ($createResult['message'] ?? 'Create failed').'; '
            .($alterResult['message'] ?? 'Alter failed')
        );

        return $alterResult;
    }

    private function normalizePartyParentLedger(string $raw): string
    {
        $normalized = strtolower(trim($raw));

        return match ($normalized) {
            'supplier', 'vendor', 'creditor', 'sundry creditors' => 'Sundry Creditors',
            'both' => 'Sundry Debtors',
            default => 'Sundry Debtors',
        };
    }

    private function esc(?string $value): string
    {
        return $this->client->xmlEscape($value);
    }

    private function finalizeAndReturn(
        string $entityType,
        ?int $entityId,
        string $operation,
        bool $success,
        string $message,
        array $requestPayload = [],
        array $responsePayload = [],
    ): array {
        $status = $success ? 'success' : 'failed';

        $logPayload = [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'operation' => $operation,
            'status' => $status,
            'message' => $message,
            'request_payload' => ! empty($requestPayload) ? json_encode($requestPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'response_payload' => ! empty($responsePayload) ? json_encode($responsePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'synced_at' => $success ? now() : null,
        ];

        $this->writeSyncLog($logPayload);

        return [
            'status' => $success,
            'message' => $message,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'operation' => $operation,
            'log_status' => $status,
        ];
    }

    private function writeSyncLog(array $payload): void
    {
        try {
            if (Schema::hasTable('tally_sync_logs')) {
                TallySyncLog::create($payload);

                return;
            }

            Log::info('Tally sync log (table missing)', $payload);
        } catch (\Throwable $e) {
            Log::error('Unable to write Tally sync log', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }
}

