<?php

namespace App\Services\TallyIntegration;

use App\Models\Items\Item;
use App\Models\Party\Party;
use App\Models\Purchase\Purchase;
use App\Models\Expenses\Expense;
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

    public function syncItemById(int $itemId, string $operation = 'upsert', ?string $companyName = null): array
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

        $dependencySync = [
            'unit' => $this->syncUnitMaster($baseUnit, $companyName),
            'stock_group' => $this->syncStockGroupMaster($stockGroup, $companyName),
        ];
        $dependencyFailure = $this->firstFailedDependencyMessage($dependencySync);
        if ($dependencyFailure !== null) {
            return $this->finalizeAndReturn(
                entityType: 'item',
                entityId: $item->id,
                operation: $operation,
                success: false,
                message: 'Item dependency sync failed before stock item transfer: '.$dependencyFailure,
                requestPayload: [
                    'mapped_payload' => $mappedPayload,
                ],
                responsePayload: [
                    'dependency_sync' => $dependencySync,
                ],
            );
        }

        $result = $this->pushWithFallback(
            reportName: 'All Masters',
            context: 'item_sync',
            operation: $operation,
            companyName: $companyName,
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
                'dependency_sync' => $dependencySync,
            ],
        );
    }

    public function syncPartyById(int $partyId, string $operation = 'upsert', ?string $companyName = null): array
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
            companyName: $companyName,
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

    public function syncSaleById(int $saleId, string $operation = 'upsert', ?string $companyName = null): array
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
        $partySync = $this->syncPartyById((int) $sale->party_id, 'upsert', $companyName);
        $itemSyncSummary = [];
        $itemIds = $lineRecords->pluck('item_id')->filter()->unique()->values();
        foreach ($itemIds as $itemId) {
            $itemSyncSummary[] = $this->syncItemById((int) $itemId, 'upsert', $companyName);
        }

        $dependencySync = [
            'party' => $partySync,
            'items' => $itemSyncSummary,
        ];
        $dependencyFailure = $this->firstFailedDependencyMessage($dependencySync);
        if ($dependencyFailure !== null) {
            return $this->finalizeAndReturn(
                entityType: 'sale',
                entityId: $sale->id,
                operation: $operation,
                success: false,
                message: 'Sale dependency sync failed before voucher transfer: '.$dependencyFailure,
                responsePayload: [
                    'dependency_sync' => $dependencySync,
                ],
            );
        }

        $voucherType = 'Sales';
        $voucherNo = (string) $this->mappingService->valueForTarget('sale', $sale, 'VOUCHERNUMBER', 'sale_code', $sale->sale_code);
        $partyLedgerName = (string) $this->mappingService->valueForTarget('sale', $sale, 'PARTYLEDGERNAME', 'party.company_name', $sale->party->company_name ?: '');
        $referenceNo = (string) $this->mappingService->valueForTarget('sale', $sale, 'REFERENCE', 'reference_no', $sale->reference_no ?: '');
        $narration = (string) $this->mappingService->valueForTarget('sale', $sale, 'NARRATION', 'note', $sale->note ?: '');
        $grandTotal = (float) $this->mappingService->valueForTarget('sale', $sale, 'AMOUNT', 'grand_total', $sale->grand_total ?: 0);
        $salesLedgerName = (string) $this->mappingService->valueForTarget('sale', $sale, 'SALESLEDGERNAME', 'tally_sales_ledger_name', $this->client->defaultSalesLedgerName());
        $dateYmd = date('Ymd', strtotime((string) $sale->sale_date));

        if ($voucherNo === '' || $partyLedgerName === '' || trim($salesLedgerName) === '') {
            return $this->finalizeAndReturn(
                entityType: 'sale',
                entityId: $sale->id,
                operation: $operation,
                success: false,
                message: 'Sale voucher is missing required Tally fields: voucher number, party ledger, or sales ledger.',
                responsePayload: [
                    'dependency_sync' => $dependencySync,
                ],
            );
        }

        $dependencySync['sales_ledger'] = $this->syncLedgerMaster($salesLedgerName, 'Sales Accounts', $companyName, true);
        $dependencyFailure = $this->firstFailedDependencyMessage($dependencySync);
        if ($dependencyFailure !== null) {
            return $this->finalizeAndReturn(
                entityType: 'sale',
                entityId: $sale->id,
                operation: $operation,
                success: false,
                message: 'Sale dependency sync failed before voucher transfer: '.$dependencyFailure,
                responsePayload: [
                    'dependency_sync' => $dependencySync,
                ],
            );
        }

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
            $inventoryEntryXml .= '<ACCOUNTINGALLOCATIONS.LIST>';
            $inventoryEntryXml .= '<LEDGERNAME>'.$this->esc($salesLedgerName).'</LEDGERNAME>';
            $inventoryEntryXml .= '<ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE>';
            $inventoryEntryXml .= '<AMOUNT>'.$this->esc(number_format($amountNegative, 2, '.', '')).'</AMOUNT>';
            $inventoryEntryXml .= '</ACCOUNTINGALLOCATIONS.LIST>';
            $inventoryEntryXml .= '</ALLINVENTORYENTRIES.LIST>';
        }

        if ($inventoryEntryXml === '') {
            return $this->finalizeAndReturn(
                entityType: 'sale',
                entityId: $sale->id,
                operation: $operation,
                success: false,
                message: 'Sale voucher has no valid inventory lines for Tally transfer.',
                responsePayload: [
                    'dependency_sync' => $dependencySync,
                ],
            );
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
            companyName: $companyName,
            buildMessageXml: function (string $action) use ($voucherType, $voucherNo, $partyLedgerName, $referenceNo, $narration, $grandTotal, $salesLedgerName, $dateYmd, $inventoryEntryXml) {
                $voucherAmount = number_format(abs($grandTotal), 2, '.', '');

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

                $xml .= '</VOUCHER>';
                $xml .= '</TALLYMESSAGE>';

                return $xml;
            }
        );

        $responsePayload = [
            'response_body' => $result['response_body'] ?? null,
            'parsed' => $result['parsed'] ?? [],
            'http_status' => $result['http_status'] ?? null,
            'dependency_sync' => $dependencySync,
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

    public function syncPurchaseById(int $purchaseId, string $operation = 'upsert', ?string $companyName = null): array
    {
        $purchase = Purchase::with([
            'party',
            'itemTransaction.item',
            'itemTransaction.unit',
        ])->find($purchaseId);

        if (! $purchase) {
            return $this->finalizeAndReturn('purchase', $purchaseId, $operation, false, 'Purchase record not found for Tally sync.');
        }

        if (! $purchase->party) {
            return $this->finalizeAndReturn('purchase', $purchase->id, $operation, false, 'Purchase party not found for Tally sync.');
        }

        $lineRecords = $purchase->itemTransaction;
        if ($lineRecords->isEmpty()) {
            return $this->finalizeAndReturn('purchase', $purchase->id, $operation, false, 'Purchase items not found for Tally sync.');
        }

        $partySync = $this->syncPartyById((int) $purchase->party_id, 'upsert', $companyName);
        $itemSyncSummary = [];
        foreach ($lineRecords->pluck('item_id')->filter()->unique()->values() as $itemId) {
            $itemSyncSummary[] = $this->syncItemById((int) $itemId, 'upsert', $companyName);
        }

        $purchaseLedgerName = $this->client->settingValue('purchase_ledger_name', 'Purchase');
        $dependencySync = [
            'party' => $partySync,
            'items' => $itemSyncSummary,
            'purchase_ledger' => $this->syncLedgerMaster($purchaseLedgerName, 'Purchase Accounts', $companyName, true),
        ];
        if ($dependencyFailure = $this->firstFailedDependencyMessage($dependencySync)) {
            return $this->finalizeAndReturn('purchase', $purchase->id, $operation, false, 'Purchase dependency sync failed before voucher transfer: '.$dependencyFailure, responsePayload: [
                'dependency_sync' => $dependencySync,
            ]);
        }

        $voucherNo = (string) ($purchase->purchase_code ?: $purchase->id);
        $partyLedgerName = (string) ($purchase->party->company_name ?: $purchase->party->primary_name ?: '');
        $dateYmd = date('Ymd', strtotime((string) $purchase->purchase_date));
        $grandTotal = (float) ($purchase->grand_total ?: 0);
        $narration = (string) ($purchase->note ?: '');
        $inventoryEntryXml = '';

        foreach ($lineRecords as $line) {
            $itemName = (string) ($line->item?->name ?: '');
            if ($itemName === '') {
                continue;
            }

            $unitName = (string) ($line->unit?->name ?: 'Nos');
            $quantity = abs((float) ($line->quantity ?: 0));
            $rate = abs((float) ($line->unit_price ?: 0));
            $amount = abs((float) ($line->total ?: 0));
            $qtyText = number_format($quantity, 3, '.', '').' '.$unitName;

            $inventoryEntryXml .= '<ALLINVENTORYENTRIES.LIST>';
            $inventoryEntryXml .= '<STOCKITEMNAME>'.$this->esc($itemName).'</STOCKITEMNAME>';
            $inventoryEntryXml .= '<ISDEEMEDPOSITIVE>No</ISDEEMEDPOSITIVE>';
            $inventoryEntryXml .= '<RATE>'.$this->esc(number_format($rate, 2, '.', '').'/'.$unitName).'</RATE>';
            $inventoryEntryXml .= '<AMOUNT>'.$this->esc(number_format($amount, 2, '.', '')).'</AMOUNT>';
            $inventoryEntryXml .= '<ACTUALQTY>'.$this->esc($qtyText).'</ACTUALQTY>';
            $inventoryEntryXml .= '<BILLEDQTY>'.$this->esc($qtyText).'</BILLEDQTY>';
            $inventoryEntryXml .= '<ACCOUNTINGALLOCATIONS.LIST>';
            $inventoryEntryXml .= '<LEDGERNAME>'.$this->esc($purchaseLedgerName).'</LEDGERNAME>';
            $inventoryEntryXml .= '<ISDEEMEDPOSITIVE>No</ISDEEMEDPOSITIVE>';
            $inventoryEntryXml .= '<AMOUNT>'.$this->esc(number_format($amount, 2, '.', '')).'</AMOUNT>';
            $inventoryEntryXml .= '</ACCOUNTINGALLOCATIONS.LIST>';
            $inventoryEntryXml .= '</ALLINVENTORYENTRIES.LIST>';
        }

        if ($inventoryEntryXml === '' || $partyLedgerName === '') {
            return $this->finalizeAndReturn('purchase', $purchase->id, $operation, false, 'Purchase voucher is missing party ledger or inventory lines.', responsePayload: [
                'dependency_sync' => $dependencySync,
            ]);
        }

        $result = $this->pushWithFallback(
            reportName: 'Vouchers',
            context: 'purchase_sync',
            operation: $operation,
            companyName: $companyName,
            buildMessageXml: function (string $action) use ($voucherNo, $partyLedgerName, $dateYmd, $grandTotal, $narration, $inventoryEntryXml) {
                $xml = '<TALLYMESSAGE xmlns:UDF="TallyUDF">';
                $xml .= '<VOUCHER VCHTYPE="Purchase" ACTION="'.$this->esc($action).'" OBJVIEW="Invoice Voucher View">';
                $xml .= '<DATE>'.$this->esc($dateYmd).'</DATE>';
                $xml .= '<VOUCHERTYPENAME>Purchase</VOUCHERTYPENAME>';
                $xml .= '<VOUCHERNUMBER>'.$this->esc($voucherNo).'</VOUCHERNUMBER>';
                $xml .= '<PARTYLEDGERNAME>'.$this->esc($partyLedgerName).'</PARTYLEDGERNAME>';
                $xml .= '<PERSISTEDVIEW>Invoice Voucher View</PERSISTEDVIEW><ISINVOICE>Yes</ISINVOICE>';
                if ($narration !== '') {
                    $xml .= '<NARRATION>'.$this->esc($narration).'</NARRATION>';
                }
                $xml .= $inventoryEntryXml;
                $xml .= '<LEDGERENTRIES.LIST><LEDGERNAME>'.$this->esc($partyLedgerName).'</LEDGERNAME><ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE><AMOUNT>'.$this->esc(number_format(-1 * abs($grandTotal), 2, '.', '')).'</AMOUNT></LEDGERENTRIES.LIST>';
                $xml .= '</VOUCHER></TALLYMESSAGE>';

                return $xml;
            }
        );

        return $this->finalizeAndReturn('purchase', $purchase->id, $operation, (bool) ($result['status'] ?? false), (string) ($result['message'] ?? 'Purchase sync failed.'), [
            'mapped_payload' => [
                'VOUCHERTYPENAME' => 'Purchase',
                'VOUCHERNUMBER' => $voucherNo,
                'PARTYLEDGERNAME' => $partyLedgerName,
                'AMOUNT' => $grandTotal,
                'DATE' => $dateYmd,
                'ITEMS_COUNT' => $lineRecords->count(),
            ],
            'request_xml' => $result['request_xml'] ?? null,
        ], [
            'response_body' => $result['response_body'] ?? null,
            'parsed' => $result['parsed'] ?? [],
            'http_status' => $result['http_status'] ?? null,
            'dependency_sync' => $dependencySync,
        ]);
    }

    public function syncExpenseById(int $expenseId, string $operation = 'upsert', ?string $companyName = null): array
    {
        $expense = Expense::with(['category', 'items.itemDetails'])->find($expenseId);
        if (! $expense) {
            return $this->finalizeAndReturn('expense', $expenseId, $operation, false, 'Expense record not found for Tally sync.');
        }

        $expenseLedger = (string) ($expense->category?->name ?: $this->client->settingValue('expense_ledger_name', 'Employee Expense'));
        $paymentLedger = $this->client->settingValue('cash_ledger_name', 'Cash');
        $voucherNo = (string) ($expense->expense_code ?: $expense->id);
        $dateYmd = date('Ymd', strtotime((string) $expense->expense_date));
        $amount = abs((float) ($expense->grand_total ?: 0));
        $narration = (string) ($expense->note ?: '');

        $dependencies = [
            'expense_ledger' => $this->syncLedgerMaster($expenseLedger, 'Indirect Expenses', $companyName),
            'payment_ledger' => $this->syncLedgerMaster($paymentLedger, 'Cash-in-Hand', $companyName),
        ];
        if ($dependencyFailure = $this->firstFailedDependencyMessage($dependencies)) {
            return $this->finalizeAndReturn('expense', $expense->id, $operation, false, 'Expense dependency sync failed before voucher transfer: '.$dependencyFailure, responsePayload: [
                'dependency_sync' => $dependencies,
            ]);
        }

        $result = $this->pushWithFallback(
            reportName: 'Vouchers',
            context: 'expense_sync',
            operation: $operation,
            companyName: $companyName,
            buildMessageXml: function (string $action) use ($voucherNo, $dateYmd, $expenseLedger, $paymentLedger, $amount, $narration) {
                $xml = '<TALLYMESSAGE xmlns:UDF="TallyUDF"><VOUCHER VCHTYPE="Payment" ACTION="'.$this->esc($action).'">';
                $xml .= '<DATE>'.$this->esc($dateYmd).'</DATE><VOUCHERTYPENAME>Payment</VOUCHERTYPENAME>';
                $xml .= '<VOUCHERNUMBER>'.$this->esc($voucherNo).'</VOUCHERNUMBER>';
                if ($narration !== '') {
                    $xml .= '<NARRATION>'.$this->esc($narration).'</NARRATION>';
                }
                $xml .= '<LEDGERENTRIES.LIST><LEDGERNAME>'.$this->esc($expenseLedger).'</LEDGERNAME><ISDEEMEDPOSITIVE>No</ISDEEMEDPOSITIVE><AMOUNT>'.$this->esc(number_format($amount, 2, '.', '')).'</AMOUNT></LEDGERENTRIES.LIST>';
                $xml .= '<LEDGERENTRIES.LIST><LEDGERNAME>'.$this->esc($paymentLedger).'</LEDGERNAME><ISDEEMEDPOSITIVE>Yes</ISDEEMEDPOSITIVE><AMOUNT>'.$this->esc(number_format(-1 * $amount, 2, '.', '')).'</AMOUNT></LEDGERENTRIES.LIST>';
                $xml .= '</VOUCHER></TALLYMESSAGE>';

                return $xml;
            }
        );

        return $this->finalizeAndReturn('expense', $expense->id, $operation, (bool) ($result['status'] ?? false), (string) ($result['message'] ?? 'Expense sync failed.'), [
            'mapped_payload' => [
                'VOUCHERTYPENAME' => 'Payment',
                'VOUCHERNUMBER' => $voucherNo,
                'EXPENSELEDGER' => $expenseLedger,
                'PAYMENTLEDGER' => $paymentLedger,
                'AMOUNT' => $amount,
                'DATE' => $dateYmd,
            ],
            'request_xml' => $result['request_xml'] ?? null,
        ], [
            'response_body' => $result['response_body'] ?? null,
            'parsed' => $result['parsed'] ?? [],
            'http_status' => $result['http_status'] ?? null,
            'dependency_sync' => $dependencies,
        ]);
    }

    private function pushWithFallback(string $reportName, string $context, string $operation, callable $buildMessageXml, ?string $companyName = null): array
    {
        $operation = strtolower(trim($operation));

        if ($operation === 'update') {
            return $this->client->importData(
                reportName: $reportName,
                requestDataXml: $buildMessageXml('Alter'),
                context: $context.'_alter',
                companyName: $companyName,
            );
        }

        if ($operation === 'create') {
            return $this->client->importData(
                reportName: $reportName,
                requestDataXml: $buildMessageXml('Create'),
                context: $context.'_create',
                companyName: $companyName,
            );
        }

        // default upsert behavior: create then alter
        $createResult = $this->client->importData(
            reportName: $reportName,
            requestDataXml: $buildMessageXml('Create'),
            context: $context.'_create',
            companyName: $companyName,
        );

        if ($createResult['status'] ?? false) {
            return $createResult;
        }

        $alterResult = $this->client->importData(
            reportName: $reportName,
            requestDataXml: $buildMessageXml('Alter'),
            context: $context.'_alter_fallback',
            companyName: $companyName,
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

    private function syncUnitMaster(string $unitName, ?string $companyName = null): array
    {
        $unitName = trim($unitName);
        if ($unitName === '') {
            return [
                'status' => false,
                'message' => 'Unit name is empty.',
            ];
        }

        $result = $this->pushWithFallback(
            reportName: 'All Masters',
            context: 'unit_master_sync',
            operation: 'upsert',
            companyName: $companyName,
            buildMessageXml: function (string $action) use ($unitName) {
                $xml = '<TALLYMESSAGE xmlns:UDF="TallyUDF">';
                $xml .= '<UNIT NAME="'.$this->esc($unitName).'" ACTION="'.$this->esc($action).'">';
                $xml .= '<NAME>'.$this->esc($unitName).'</NAME>';
                $xml .= '<ISSIMPLEUNIT>Yes</ISSIMPLEUNIT>';
                $xml .= '</UNIT>';
                $xml .= '</TALLYMESSAGE>';

                return $xml;
            }
        );

        return $this->allowAlreadyExistsDependency($result, 'Unit already exists in Tally.');
    }

    private function syncStockGroupMaster(string $stockGroupName, ?string $companyName = null): array
    {
        $stockGroupName = trim($stockGroupName);
        if ($stockGroupName === '' || strcasecmp($stockGroupName, 'Primary') === 0) {
            return [
                'status' => true,
                'message' => 'Stock group is Primary or empty; no transfer required.',
            ];
        }

        $result = $this->pushWithFallback(
            reportName: 'All Masters',
            context: 'stock_group_master_sync',
            operation: 'upsert',
            companyName: $companyName,
            buildMessageXml: function (string $action) use ($stockGroupName) {
                $xml = '<TALLYMESSAGE xmlns:UDF="TallyUDF">';
                $xml .= '<STOCKGROUP NAME="'.$this->esc($stockGroupName).'" ACTION="'.$this->esc($action).'">';
                $xml .= '<NAME>'.$this->esc($stockGroupName).'</NAME>';
                $xml .= '<PARENT>Primary</PARENT>';
                $xml .= '<ISADDABLE>Yes</ISADDABLE>';
                $xml .= '</STOCKGROUP>';
                $xml .= '</TALLYMESSAGE>';

                return $xml;
            }
        );

        return $this->allowAlreadyExistsDependency($result, 'Stock group already exists in Tally.');
    }

    private function syncLedgerMaster(string $ledgerName, string $parentLedgerName, ?string $companyName = null, bool $affectsStock = false): array
    {
        $ledgerName = trim($ledgerName);
        $parentLedgerName = trim($parentLedgerName);
        if ($ledgerName === '' || $parentLedgerName === '') {
            return [
                'status' => false,
                'message' => 'Ledger name or parent ledger group is empty.',
            ];
        }

        $result = $this->pushWithFallback(
            reportName: 'All Masters',
            context: 'ledger_master_sync',
            operation: 'upsert',
            companyName: $companyName,
            buildMessageXml: function (string $action) use ($ledgerName, $parentLedgerName, $affectsStock) {
                $xml = '<TALLYMESSAGE xmlns:UDF="TallyUDF">';
                $xml .= '<LEDGER NAME="'.$this->esc($ledgerName).'" ACTION="'.$this->esc($action).'">';
                $xml .= '<NAME>'.$this->esc($ledgerName).'</NAME>';
                $xml .= '<PARENT>'.$this->esc($parentLedgerName).'</PARENT>';
                $xml .= '<ISBILLWISEON>No</ISBILLWISEON>';
                if ($affectsStock) {
                    $xml .= '<AFFECTSSTOCK>Yes</AFFECTSSTOCK>';
                }
                $xml .= '</LEDGER>';
                $xml .= '</TALLYMESSAGE>';

                return $xml;
            }
        );

        return $this->allowAlreadyExistsDependency($result, 'Ledger already exists in Tally.');
    }

    private function allowAlreadyExistsDependency(array $result, string $successMessage): array
    {
        if (($result['status'] ?? false) || ! preg_match('/already\s+exists|exists\s+already|duplicate/i', (string) ($result['message'] ?? ''))) {
            return $result;
        }

        $result['status'] = true;
        $result['message'] = $successMessage;

        return $result;
    }

    private function firstFailedDependencyMessage(array $dependencyResults, string $prefix = ''): ?string
    {
        foreach ($dependencyResults as $name => $dependency) {
            if (! is_array($dependency)) {
                continue;
            }

            $label = is_string($name) ? ucfirst(str_replace('_', ' ', $name)) : trim($prefix);

            if (array_key_exists('status', $dependency)) {
                if (! (bool) ($dependency['status'] ?? false)) {
                    $message = trim((string) ($dependency['message'] ?? 'Failed.'));

                    return trim($label.': '.$message);
                }

                continue;
            }

            $nestedPrefix = trim($prefix.' '.$label);
            $nestedMessage = $this->firstFailedDependencyMessage($dependency, $nestedPrefix);
            if ($nestedMessage !== null) {
                return $nestedMessage;
            }
        }

        return null;
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
            'tally_response' => $responsePayload['parsed'] ?? null,
            'dependency_sync' => $responsePayload['dependency_sync'] ?? null,
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
