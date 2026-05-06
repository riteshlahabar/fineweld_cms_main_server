@extends('layouts.app')
@section('title', 'Tally Integration')

@section('content')
@php
    $xmlPort = old('xml_port', $connectionSettings->xml_port ?? $connectionSettings->port ?? $connectionSettings->odbc_port ?? 9000);
    $defaultCompanyName = old('company_name', $connectionSettings->company_name ?? '');
    $salesLedgerName = old('sales_ledger_name', $connectionSettings->sales_ledger_name ?? 'Sales');
    $purchaseLedgerName = old('purchase_ledger_name', $connectionSettings->purchase_ledger_name ?? 'Purchase');
    $expenseLedgerName = old('expense_ledger_name', $connectionSettings->expense_ledger_name ?? 'Employee Expense');
    $cashLedgerName = old('cash_ledger_name', $connectionSettings->cash_ledger_name ?? 'Cash');
    $bankLedgerName = old('bank_ledger_name', $connectionSettings->bank_ledger_name ?? '');
    $roundOffLedgerName = old('round_off_ledger_name', $connectionSettings->round_off_ledger_name ?? 'Round Off');
    $cgstLedgerName = old('cgst_ledger_name', $connectionSettings->cgst_ledger_name ?? 'CGST');
    $sgstLedgerName = old('sgst_ledger_name', $connectionSettings->sgst_ledger_name ?? 'SGST');
    $igstLedgerName = old('igst_ledger_name', $connectionSettings->igst_ledger_name ?? 'IGST');
    $tallyFieldOptions = [
        'NAME', 'PARENT', 'PARTYGSTIN', 'LEDGERMOBILE', 'EMAIL',
        'OPENINGBALANCE', 'CLOSINGBALANCE', 'BASEUNITS', 'HSNCODE',
        'VOUCHERNUMBER', 'DATE', 'VOUCHERTYPENAME', 'PARTYLEDGERNAME',
        'REFERENCE', 'NARRATION', 'AMOUNT', 'LEDGERNAME', 'STOCKITEMNAME',
        'RATE', 'ACTUALQTY', 'BILLEDQTY',
    ];
    $projectFieldOptions = [
        'item.name', 'item.baseUnit.name', 'item.category.name', 'item.item_code',
        'item.hsn', 'item.description', 'item.sale_price', 'item.purchase_price',
        'party.company_name', 'party.vendor_type', 'party.primary_mobile',
        'party.primary_email', 'party.company_gst', 'party.company_pan',
        'party.billing_address', 'party.shipping_address',
        'sale.sale_code', 'sale.party.company_name', 'sale.reference_no',
        'sale.note', 'sale.grand_total', 'sale.round_off', 'sale.sale_date',
        'sale_item.item.name', 'sale_item.unit.name', 'sale_item.quantity',
        'sale_item.unit_price', 'sale_item.total', 'sale_item.description',
    ];
@endphp
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumb :langArray="[
                                'app.settings',
                                'Tally Integration',
                            ]"/>

        @if (session('success'))
        <div class="alert border-0 border-start border-5 border-success alert-dismissible fade show py-2">
            <div class="d-flex align-items-center">
                <div class="font-35 text-success"><i class='bx bxs-check-circle'></i></div>
                <div class="ms-3">
                    <h6 class="mb-0 text-success">Success</h6>
                    <div>{{ session('success') }}</div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if ($errors->any())
        <div class="alert border-0 border-start border-5 border-danger alert-dismissible fade show py-2">
            <div class="d-flex align-items-center">
                <div class="font-35 text-danger"><i class='bx bxs-error-circle'></i></div>
                <div class="ms-3">
                    <h6 class="mb-0 text-danger">Error</h6>
                    @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <div class="card">
            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-uppercase">Tally Connection Settings</h5>
            </div>
            <div class="card-body">
                <form id="tallyConnectionForm" method="POST" action="{{ route('settings.tally.integration.connection.store') }}">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <x-label for="host" name="Host / IP" />
                            <x-input type="text" name="host" id="host" :required="true" value="{{ old('host', $connectionSettings->host ?? '') }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="xml_port" name="XML Port" />
                            <x-input type="number" name="xml_port" id="xml_port" :required="true" value="{{ $xmlPort }}" />
                        </div>
                        <div class="col-md-3">
                            <x-label for="connection_company_name" name="Tally Company Name" />
                            <x-input type="text" name="company_name" id="connection_company_name" :required="true" value="{{ $defaultCompanyName }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="sales_ledger_name" name="Sales Ledger" />
                            <x-input type="text" name="sales_ledger_name" id="sales_ledger_name" :required="false" value="{{ $salesLedgerName }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="username" name="User ID" />
                            <x-input type="text" name="username" id="username" :required="false" value="{{ old('username', $connectionSettings->username ?? '') }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="password" name="Password" />
                            <x-input type="password" name="password" id="password" :required="false" value="" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="purchase_ledger_name" name="Purchase Ledger" />
                            <x-input type="text" name="purchase_ledger_name" id="purchase_ledger_name" :required="false" value="{{ $purchaseLedgerName }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="expense_ledger_name" name="Expense Ledger" />
                            <x-input type="text" name="expense_ledger_name" id="expense_ledger_name" :required="false" value="{{ $expenseLedgerName }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="cash_ledger_name" name="Cash Ledger" />
                            <x-input type="text" name="cash_ledger_name" id="cash_ledger_name" :required="false" value="{{ $cashLedgerName }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="bank_ledger_name" name="Bank Ledger" />
                            <x-input type="text" name="bank_ledger_name" id="bank_ledger_name" :required="false" value="{{ $bankLedgerName }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="round_off_ledger_name" name="Round Off Ledger" />
                            <x-input type="text" name="round_off_ledger_name" id="round_off_ledger_name" :required="false" value="{{ $roundOffLedgerName }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="cgst_ledger_name" name="CGST Ledger" />
                            <x-input type="text" name="cgst_ledger_name" id="cgst_ledger_name" :required="false" value="{{ $cgstLedgerName }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="sgst_ledger_name" name="SGST Ledger" />
                            <x-input type="text" name="sgst_ledger_name" id="sgst_ledger_name" :required="false" value="{{ $sgstLedgerName }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="igst_ledger_name" name="IGST Ledger" />
                            <x-input type="text" name="igst_ledger_name" id="igst_ledger_name" :required="false" value="{{ $igstLedgerName }}" />
                        </div>
                        <div class="col-md-12 d-flex gap-2 mt-2">
                            <x-button type="submit" class="primary px-4" text="Save Connection" />
                            <button type="button" class="btn btn-outline-primary px-4" id="testConnectionBtn">Test Connection</button>
                            <button type="button" class="btn btn-outline-secondary px-4" id="fetchTallyMastersBtn">Fetch Tally Masters</button>
                        </div>
                    </div>
                </form>

                <datalist id="tallyCompanyOptions"></datalist>
                <datalist id="tallyLedgerOptions"></datalist>
                <datalist id="tallyGroupOptions"></datalist>
                <datalist id="tallyStockItemOptions"></datalist>
                <datalist id="tallyUnitOptions"></datalist>
                <datalist id="tallyVoucherTypeOptions"></datalist>
                <datalist id="tallyFieldOptions">
                    @foreach ($tallyFieldOptions as $fieldOption)
                    <option value="{{ $fieldOption }}"></option>
                    @endforeach
                </datalist>
                <datalist id="projectFieldOptions">
                    @foreach ($projectFieldOptions as $fieldOption)
                    <option value="{{ $fieldOption }}"></option>
                    @endforeach
                </datalist>

                <div class="mt-3 d-none" id="connectionTestResult"></div>
                <div class="mt-3 d-none" id="masterFetchResult"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-uppercase">Field Mapping</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info border-0 border-start border-5 border-info py-2">
                    <div class="small">
                        Use single Tally target field names only:
                        <strong>NAME</strong>, <strong>PARTYGSTIN</strong>, <strong>VOUCHERNUMBER</strong>.
                        Do not use prefixes like <strong>item.</strong>, <strong>party.</strong> or <strong>sale.</strong>.
                    </div>
                </div>
                <form method="POST" action="{{ $editMapping ? route('settings.tally.integration.update', ['id' => $editMapping->id]) : route('settings.tally.integration.store') }}">
                    @csrf
                    @if ($editMapping)
                    @method('PUT')
                    @endif

                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <x-label for="project_field" name="Project Field" />
                            <x-input type="text" name="project_field" id="project_field" :required="true" value="{{ old('project_field', $editMapping->project_field ?? '') }}" />
                        </div>
                        <div class="col-md-4">
                            <x-label for="tally_field" name="Tally Field" />
                            <x-input type="text" name="tally_field" id="tally_field" :required="true" value="{{ old('tally_field', $editMapping->tally_field ?? '') }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="company_name" name="Company Name" />
                            <x-input type="text" name="company_name" id="company_name" :required="false" value="{{ old('company_name', $editMapping->company_name ?? '') }}" />
                        </div>
                        <div class="col-md-2">
                            <x-button type="submit" class="primary w-100" text="{{ $editMapping ? __('app.update') : __('app.save') }}" />
                        </div>
                    </div>
                </form>
                @if ($editMapping)
                <div class="mt-3">
                    <a class="btn btn-outline-secondary px-4" href="{{ route('settings.tally.integration') }}">Cancel Edit</a>
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-uppercase">Saved Field Mappings</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered border w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Project Field</th>
                                <th>Tally Field</th>
                                <th>Company Name</th>
                                <th>{{ __('app.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($mappings as $mapping)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $mapping->project_field }}</td>
                                <td>{{ $mapping->tally_field }}</td>
                                <td>{{ $mapping->company_name ?: '-' }}</td>
                                <td class="text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('settings.tally.integration', ['edit' => $mapping->id]) }}">Edit</a>
                                    <form class="d-inline-block" method="POST" action="{{ route('settings.tally.integration.delete', ['id' => $mapping->id]) }}" onsubmit="return confirm('Delete this mapping?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No mappings found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-uppercase">Manual Sync & Logs</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <x-label for="manual_sync_project_field" name="Project Field" />
                        <input id="manual_sync_project_field" type="text" class="form-control" placeholder="e.g. name">
                    </div>
                    <div class="col-md-2">
                        <x-label for="manual_sync_tally_field" name="Tally Field" />
                        <input id="manual_sync_tally_field" type="text" class="form-control" placeholder="e.g. VOUCHERNUMBER">
                    </div>
                    <div class="col-md-2">
                        <x-label for="manual_sync_company_name" name="Company Name" />
                        <input id="manual_sync_company_name" type="text" class="form-control" placeholder="Exact Tally company" value="{{ $defaultCompanyName }}" required>
                    </div>
                    <div class="col-md-2">
                        <x-label for="manual_sync_id" name="ID" />
                        <input id="manual_sync_id" type="number" min="1" class="form-control" placeholder="Record ID">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button id="manualSyncBtn" type="button" class="btn btn-outline-primary px-4" data-base-url="{{ url('settings/tally-integration/sync') }}">Run Manual Sync</button>
                        <button id="loadTallySyncLogsBtn" type="button" class="btn btn-outline-secondary px-4" data-url="{{ route('settings.tally.integration.sync.logs') }}">Load Latest Logs</button>
                    </div>
                </div>

                <div class="mt-3 d-none" id="manualSyncResult"></div>

                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered border w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Entity</th>
                                <th>Record ID</th>
                                <th>Operation</th>
                                <th>Status</th>
                                <th>Message</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody id="tallySyncLogsBody">
                            <tr><td colspan="7" class="text-center text-muted">Click "Load Latest Logs" to view sync history.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
(function() {
    const testBtn = document.getElementById('testConnectionBtn');
    const fetchMastersBtn = document.getElementById('fetchTallyMastersBtn');
    const resultBox = document.getElementById('connectionTestResult');
    const masterFetchResult = document.getElementById('masterFetchResult');
    const form = document.getElementById('tallyConnectionForm');
    const clientErrorLogUrl = "{{ route('settings.tally.integration.client.error') }}";
    const masterOptionsUrl = "{{ route('settings.tally.integration.master.options') }}";
    const manualSyncBtn = document.getElementById('manualSyncBtn');
    const manualSyncResult = document.getElementById('manualSyncResult');
    const syncLogsBtn = document.getElementById('loadTallySyncLogsBtn');
    const syncLogsBody = document.getElementById('tallySyncLogsBody');

    if (!testBtn || !resultBox || !form) {
        return;
    }

    const ledgerInputIds = [
        'sales_ledger_name',
        'purchase_ledger_name',
        'expense_ledger_name',
        'cash_ledger_name',
        'bank_ledger_name',
        'round_off_ledger_name',
        'cgst_ledger_name',
        'sgst_ledger_name',
        'igst_ledger_name'
    ];
    ledgerInputIds.forEach((id) => document.getElementById(id)?.setAttribute('list', 'tallyLedgerOptions'));
    ['connection_company_name', 'company_name', 'manual_sync_company_name'].forEach((id) => document.getElementById(id)?.setAttribute('list', 'tallyCompanyOptions'));
    ['tally_field', 'manual_sync_tally_field'].forEach((id) => document.getElementById(id)?.setAttribute('list', 'tallyFieldOptions'));
    ['project_field', 'manual_sync_project_field'].forEach((id) => document.getElementById(id)?.setAttribute('list', 'projectFieldOptions'));

    const getCsrfToken = () => {
        const tokenInput = form.querySelector('input[name="_token"]');
        if (tokenInput && tokenInput.value) {
            return tokenInput.value;
        }
        if (typeof _csrf_token !== 'undefined' && _csrf_token) {
            return _csrf_token;
        }
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    };

    const sendClientErrorLog = async (payload) => {
        try {
            await fetch(clientErrorLogUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });
        } catch (e) {
            // Keep silent on logging transport failures
        }
    };

    window.addEventListener('error', function(event) {
        sendClientErrorLog({
            message: event.message || 'Unknown JS error',
            stack: event.error && event.error.stack ? event.error.stack : '',
            source: event.filename || '',
            line: event.lineno || 0,
            column: event.colno || 0,
            context: 'tally_integration_page',
            url: window.location.href
        });
    });

    window.addEventListener('unhandledrejection', function(event) {
        const reason = event.reason || {};
        sendClientErrorLog({
            message: reason.message || String(reason) || 'Unhandled promise rejection',
            stack: reason.stack || '',
            source: '',
            line: 0,
            column: 0,
            context: 'tally_integration_page_unhandledrejection',
            url: window.location.href
        });
    });

    const parseResponse = async (response) => {
        const text = await response.text();
        let json = null;

        try {
            json = text ? JSON.parse(text) : null;
        } catch (e) {
            json = null;
        }

        return { text, json };
    };

    const normalizeHostInput = () => {
        const hostInput = document.getElementById('host');
        if (!hostInput) {
            return '';
        }

        const host = hostInput.value;
        try {
            const normalizedUrl = host.includes('://') ? new URL(host) : new URL(`http://${host}`);
            if (normalizedUrl.hostname) {
                hostInput.value = normalizedUrl.hostname;
            }
        } catch (e) {
            // Server-side validation will report invalid host input.
        }

        return hostInput.value;
    };

    const showMasterResult = (isSuccess, message) => {
        if (!masterFetchResult) {
            return;
        }

        masterFetchResult.className = `alert ${isSuccess ? 'alert-success' : 'alert-warning'}`;
        masterFetchResult.innerHTML = `<strong>${message}</strong>`;
        masterFetchResult.classList.remove('d-none');
    };

    const replaceDatalistOptions = (listId, rows) => {
        const datalist = document.getElementById(listId);
        if (!datalist) {
            return;
        }

        const seen = new Set();
        datalist.innerHTML = '';

        (rows || []).forEach((row) => {
            const name = typeof row === 'string' ? row : String(row?.name || '').trim();
            if (!name || seen.has(name.toLowerCase())) {
                return;
            }

            seen.add(name.toLowerCase());
            const option = document.createElement('option');
            option.value = name;

            if (row && typeof row === 'object' && row.parent) {
                option.label = row.parent;
            }

            datalist.appendChild(option);
        });
    };

    const flattenFieldOptions = (fieldOptions) => {
        const values = [];
        Object.values(fieldOptions || {}).forEach((group) => {
            (group || []).forEach((field) => values.push(String(field || '').trim().toUpperCase()));
        });

        return [...new Set(values.filter(Boolean))].sort();
    };

    const pickLedger = (ledgers, parentText, nameText = '') => {
        const parentNeedle = String(parentText || '').toLowerCase();
        const nameNeedle = String(nameText || '').toLowerCase();

        return (ledgers || []).find((ledger) => {
            const parent = String(ledger?.parent || '').toLowerCase();
            const name = String(ledger?.name || '').toLowerCase();

            return (!parentNeedle || parent.includes(parentNeedle))
                && (!nameNeedle || name.includes(nameNeedle));
        }) || (ledgers || []).find((ledger) => nameNeedle && String(ledger?.name || '').toLowerCase().includes(nameNeedle));
    };

    const setLedgerValue = (inputId, ledger, defaultValues = []) => {
        const input = document.getElementById(inputId);
        const name = String(ledger?.name || '').trim();
        if (!input || !name) {
            return;
        }

        const current = String(input.value || '').trim();
        const canReplace = current === ''
            || input.dataset.autoFilled === '1'
            || defaultValues.map((value) => value.toLowerCase()).includes(current.toLowerCase());

        if (canReplace) {
            input.value = name;
            input.dataset.autoFilled = '1';
        }
    };

    const applyFetchedMasterOptions = (json) => {
        const ledgers = Array.isArray(json?.ledgers) ? json.ledgers : [];
        const companies = Array.isArray(json?.companies) && json.companies.length
            ? json.companies
            : (json?.current_company ? [{ name: json.current_company }] : []);

        replaceDatalistOptions('tallyCompanyOptions', companies);
        replaceDatalistOptions('tallyLedgerOptions', ledgers);
        replaceDatalistOptions('tallyGroupOptions', json?.groups || []);
        replaceDatalistOptions('tallyStockItemOptions', json?.stock_items || []);
        replaceDatalistOptions('tallyUnitOptions', json?.units || []);
        replaceDatalistOptions('tallyVoucherTypeOptions', json?.voucher_types || []);
        const fetchedFieldOptions = flattenFieldOptions(json?.field_options || {});
        if (fetchedFieldOptions.length > 0) {
            replaceDatalistOptions('tallyFieldOptions', fetchedFieldOptions);
        }

        if (json?.current_company) {
            document.getElementById('connection_company_name').value = json.current_company;
            const mappingCompany = document.getElementById('company_name');
            const manualCompany = document.getElementById('manual_sync_company_name');
            if (mappingCompany && !mappingCompany.value.trim()) {
                mappingCompany.value = json.current_company;
            }
            if (manualCompany) {
                manualCompany.value = json.current_company;
            }
        }

        setLedgerValue('sales_ledger_name', pickLedger(ledgers, 'sales accounts', 'sales'), ['Sales']);
        setLedgerValue('purchase_ledger_name', pickLedger(ledgers, 'purchase accounts', 'purchase'), ['Purchase']);
        setLedgerValue('expense_ledger_name', pickLedger(ledgers, 'indirect expenses', 'expense') || pickLedger(ledgers, '', 'employee expense'), ['Employee Expense']);
        setLedgerValue('cash_ledger_name', pickLedger(ledgers, 'cash-in-hand', 'cash') || pickLedger(ledgers, '', 'cash'), ['Cash']);
        setLedgerValue('bank_ledger_name', pickLedger(ledgers, 'bank accounts'), ['']);
        setLedgerValue('round_off_ledger_name', pickLedger(ledgers, '', 'round'), ['Round Off']);
        setLedgerValue('cgst_ledger_name', pickLedger(ledgers, '', 'cgst'), ['CGST']);
        setLedgerValue('sgst_ledger_name', pickLedger(ledgers, '', 'sgst'), ['SGST']);
        setLedgerValue('igst_ledger_name', pickLedger(ledgers, '', 'igst'), ['IGST']);
    };

    const showManualResult = (isSuccess, message) => {
        if (!manualSyncResult) {
            return;
        }
        const cssClass = isSuccess ? 'alert alert-success' : 'alert alert-danger';
        manualSyncResult.className = cssClass;
        manualSyncResult.innerHTML = `<strong>${message}</strong>`;
        manualSyncResult.classList.remove('d-none');
    };

    const renderSyncLogs = (logs) => {
        if (!syncLogsBody) {
            return;
        }

        if (!Array.isArray(logs) || logs.length === 0) {
            syncLogsBody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No sync logs found.</td></tr>';
            return;
        }

        syncLogsBody.innerHTML = logs.map((row) => {
            const statusClass = row.status === 'success' ? 'badge bg-success' : 'badge bg-danger';
            return `
                <tr>
                    <td>${row.id ?? ''}</td>
                    <td>${row.entity_type ?? ''}</td>
                    <td>${row.entity_id ?? ''}</td>
                    <td>${row.operation ?? ''}</td>
                    <td><span class="${statusClass}">${row.status ?? ''}</span></td>
                    <td>${row.message ?? ''}</td>
                    <td>${row.created_at ?? ''}</td>
                </tr>
            `;
        }).join('');
    };

    const normalizeTallyField = (value) => {
        const rawValue = String(value || '').trim();
        if (rawValue === '') {
            return '';
        }

        if (rawValue.includes('.')) {
            const prefix = rawValue.split('.', 1)[0].toLowerCase();
            const tail = rawValue.split('.').pop() || '';

            if (['item', 'items'].includes(prefix)) {
                return String(tail).trim().toUpperCase();
            }
            if (['party', 'vendor', 'customer', 'supplier'].includes(prefix)) {
                return String(tail).trim().toUpperCase();
            }
            if (['sale', 'invoice'].includes(prefix)) {
                return String(tail).trim().toUpperCase();
            }
        }

        return rawValue.toUpperCase();
    };

    const resolveSyncEntity = (projectFieldValue, tallyFieldValue) => {
        const rawValue = String(tallyFieldValue || '').trim();
        if (rawValue === '') {
            return null;
        }

        const lowerRawValue = rawValue.toLowerCase();
        if (lowerRawValue.includes('.')) {
            const prefix = lowerRawValue.split('.', 1)[0];
            if (['item', 'items'].includes(prefix)) {
                return 'item';
            }
            if (['party', 'vendor', 'customer', 'supplier'].includes(prefix)) {
                return 'party';
            }
            if (['sale', 'invoice'].includes(prefix)) {
                return 'sale';
            }
        }

        const tallyField = normalizeTallyField(tallyFieldValue);
        const projectField = String(projectFieldValue || '').trim().toLowerCase();

        const tallyFieldEntityMap = {
            item: ['BASEUNITS', 'ADDITIONALUNITS', 'CONVERSION', 'HSNCODE', 'OPENINGBALANCE', 'ALIAS'],
            party: ['PARTYGSTIN', 'INCOMETAXNUMBER', 'SHIPPINGADDRESS', 'LEDGERMOBILE'],
            sale: ['VOUCHERNUMBER', 'PARTYLEDGERNAME', 'VOUCHERTYPENAME', 'REFERENCE'],
        };

        for (const [entity, fields] of Object.entries(tallyFieldEntityMap)) {
            if (fields.includes(tallyField)) {
                return entity;
            }
        }

        // NAME/EMAIL/MOBILE/ADDRESS/AMOUNT/DATE/NARRATION can appear in multiple contexts.
        if (projectField.includes('sale') || projectField.includes('order') || projectField.includes('invoice')) {
            return 'sale';
        }
        if (projectField.includes('party') || projectField.includes('vendor') || projectField.includes('customer') || projectField.includes('gst') || projectField.includes('ledger')) {
            return 'party';
        }
        if (projectField.includes('item') || projectField.includes('stock') || projectField.includes('hsn') || projectField.includes('unit')) {
            return 'item';
        }

        // Default fallback for ambiguous fields
        return 'sale';
    };

    testBtn.addEventListener('click', async function() {
        const host = normalizeHostInput();
        const xmlPort = document.getElementById('xml_port').value;
        const companyName = document.getElementById('connection_company_name').value;

        testBtn.disabled = true;
        testBtn.innerText = 'Testing...';

        try {
            const response = await fetch("{{ route('settings.tally.integration.test.connection') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    host: host,
                    xml_port: xmlPort,
                    company_name: companyName
                })
            });

            const { text, json } = await parseResponse(response);

            if (!json) {
                throw new Error((text || `HTTP ${response.status}`).substring(0, 300));
            }

            const successFlag = (typeof json.status !== 'undefined') ? !!json.status : !!json.success;
            const isOk = response.ok && successFlag;
            const css = isOk ? 'alert-success' : 'alert-danger';

            let extra = '';
            if (json.details) {
                const tallyMessage = json.details.tally_message || '';
                const endpoint = json.details.endpoint || '';
                const httpStatus = json.details.http_status || 'N/A';
                extra = `<div class="mt-1"><strong>Endpoint:</strong> ${endpoint}<br><strong>HTTP:</strong> ${httpStatus}<br><strong>Tally:</strong> ${tallyMessage}</div>`;
            }

            resultBox.className = `alert ${css}`;
            resultBox.innerHTML = `<strong>${json.message || 'Done'}</strong>${extra}`;
            resultBox.classList.remove('d-none');

            if (typeof iziToast !== 'undefined') {
                if (isOk) {
                    iziToast.success({
                        title: 'Success',
                        layout: 2,
                        message: json.message || 'Connection test completed.'
                    });
                } else {
                    iziToast.error({
                        title: 'Error',
                        layout: 2,
                        message: json.message || 'Connection test completed.'
                    });
                }
            }
        } catch (error) {
            const message = error && error.message ? error.message : 'Connection test failed due to network or server error.';
            sendClientErrorLog({
                message: message,
                stack: error && error.stack ? error.stack : '',
                source: '',
                line: 0,
                column: 0,
                context: 'tally_test_connection_click',
                url: window.location.href
            });
            resultBox.className = 'alert alert-danger';
            resultBox.innerHTML = `<strong>${message}</strong>`;
            resultBox.classList.remove('d-none');
            if (typeof iziToast !== 'undefined') {
                iziToast.error({title: 'Error', layout: 2, message: message});
            }
        } finally {
            testBtn.disabled = false;
            testBtn.innerText = 'Test Connection';
        }
    });

    if (fetchMastersBtn) {
        fetchMastersBtn.addEventListener('click', async function() {
            const host = normalizeHostInput();
            const xmlPort = document.getElementById('xml_port').value;
            const companyName = document.getElementById('connection_company_name').value;

            fetchMastersBtn.disabled = true;
            fetchMastersBtn.innerText = 'Fetching...';

            try {
                const response = await fetch(masterOptionsUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        host: host,
                        xml_port: xmlPort,
                        company_name: companyName
                    })
                });

                const { text, json } = await parseResponse(response);
                if (!json) {
                    throw new Error((text || `HTTP ${response.status}`).substring(0, 300));
                }

                const hasData = (json.ledgers || []).length
                    || (json.groups || []).length
                    || (json.stock_items || []).length
                    || (json.units || []).length
                    || (json.voucher_types || []).length
                    || json.current_company;

                if (!response.ok && !hasData) {
                    throw new Error(json.message || `HTTP ${response.status}`);
                }

                applyFetchedMasterOptions(json);

                const counts = json.counts || {};
                const message = `${json.message || 'Tally masters fetched.'} Ledgers: ${counts.ledgers ?? (json.ledgers || []).length}, Groups: ${counts.groups ?? (json.groups || []).length}, Items: ${counts.stock_items ?? (json.stock_items || []).length}.`;
                showMasterResult(response.ok && !!json.status, message);

                if (typeof iziToast !== 'undefined') {
                    const toastPayload = {
                        title: response.ok && json.status ? 'Success' : 'Warning',
                        layout: 2,
                        message: json.message || 'Tally masters fetched.'
                    };
                    if (response.ok && json.status) {
                        iziToast.success(toastPayload);
                    } else if (typeof iziToast.warning === 'function') {
                        iziToast.warning(toastPayload);
                    } else {
                        iziToast.error(toastPayload);
                    }
                }
            } catch (error) {
                const message = error?.message || 'Unable to fetch Tally masters.';
                sendClientErrorLog({
                    message: message,
                    stack: error && error.stack ? error.stack : '',
                    source: '',
                    line: 0,
                    column: 0,
                    context: 'tally_fetch_masters_click',
                    url: window.location.href
                });
                showMasterResult(false, message);
                if (typeof iziToast !== 'undefined') {
                    iziToast.error({title: 'Error', layout: 2, message: message});
                }
            } finally {
                fetchMastersBtn.disabled = false;
                fetchMastersBtn.innerText = 'Fetch Tally Masters';
            }
        });
    }

    if (manualSyncBtn) {
        manualSyncBtn.addEventListener('click', async function() {
            const projectField = (document.getElementById('manual_sync_project_field')?.value || '').trim();
            const tallyField = (document.getElementById('manual_sync_tally_field')?.value || '').trim();
            const companyName = (document.getElementById('manual_sync_company_name')?.value || '').trim();
            const id = (document.getElementById('manual_sync_id')?.value || '').trim();
            const baseUrl = manualSyncBtn.dataset.baseUrl || '';

            if (projectField === '' || tallyField === '' || id === '' || companyName === '') {
                showManualResult(false, 'Please enter Project Field, Tally Field, Company Name and ID.');
                return;
            }

            const entity = resolveSyncEntity(projectField, tallyField);
            if (!entity) {
                showManualResult(false, 'Unable to detect entity from mapping fields. Use clear project/tally field names.');
                return;
            }

            const normalizedTallyField = normalizeTallyField(tallyField);

            manualSyncBtn.disabled = true;
            const originalText = manualSyncBtn.innerText;
            manualSyncBtn.innerText = 'Syncing...';

            try {
                const response = await fetch(`${baseUrl}/${encodeURIComponent(entity)}/${encodeURIComponent(id)}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        project_field: projectField,
                        tally_field: normalizedTallyField,
                        company_name: companyName
                    })
                });

                const { text, json } = await parseResponse(response);
                if (!json) {
                    throw new Error((text || `HTTP ${response.status}`).substring(0, 300));
                }

                showManualResult(!!json.status, json.message || 'Manual sync response received.');
            } catch (error) {
                showManualResult(false, error?.message || 'Manual sync request failed.');
            } finally {
                manualSyncBtn.disabled = false;
                manualSyncBtn.innerText = originalText;
            }
        });
    }

    if (syncLogsBtn) {
        syncLogsBtn.addEventListener('click', async function() {
            const url = syncLogsBtn.dataset.url;
            syncLogsBtn.disabled = true;
            const originalText = syncLogsBtn.innerText;
            syncLogsBtn.innerText = 'Loading...';

            try {
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const { text, json } = await parseResponse(response);
                if (!json) {
                    throw new Error((text || `HTTP ${response.status}`).substring(0, 300));
                }

                renderSyncLogs(json.data || []);
            } catch (error) {
                renderSyncLogs([]);
                showManualResult(false, error?.message || 'Unable to load sync logs.');
            } finally {
                syncLogsBtn.disabled = false;
                syncLogsBtn.innerText = originalText;
            }
        });
    }
})();
</script>
@endsection
