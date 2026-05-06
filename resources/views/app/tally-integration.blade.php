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

                <div class="mt-3 d-none" id="connectionTestResult"></div>
                <div class="mt-3 d-none" id="masterFetchResult"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-uppercase">Tally Transfers</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <x-label for="manual_sync_entity" name="Transfer Type" />
                        <select id="manual_sync_entity" class="form-control">
                            <option value="sale">Sales</option>
                            <option value="purchase">Purchase</option>
                            <option value="expense">Expense</option>
                            <option value="party">Party Ledger</option>
                            <option value="item">Stock Item</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <x-label for="manual_sync_id" name="Record ID" />
                        <input id="manual_sync_id" type="number" min="1" class="form-control" placeholder="Optional ID">
                    </div>
                    <div class="col-md-2">
                        <x-label for="manual_sync_from_date" name="From Date" />
                        <input id="manual_sync_from_date" type="date" class="form-control" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-md-2">
                        <x-label for="manual_sync_to_date" name="To Date" />
                        <input id="manual_sync_to_date" type="date" class="form-control" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-md-2">
                        <x-label for="manual_sync_company_name" name="Company Name" />
                        <input id="manual_sync_company_name" type="text" class="form-control" placeholder="Exact Tally company" value="{{ $defaultCompanyName }}">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button id="manualSyncBtn" type="button" class="btn btn-outline-primary px-3" data-base-url="{{ url('settings/tally-integration/sync') }}" data-date-url="{{ url('settings/tally-integration/sync-by-date') }}">Transfer</button>
                        <button id="loadTallySyncLogsBtn" type="button" class="btn btn-outline-secondary px-3" data-url="{{ route('settings.tally.integration.sync.logs') }}">Logs</button>
                    </div>
                </div>

                <div class="mt-3 d-none" id="manualSyncResult"></div>

                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered border w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Record ID</th>
                                <th>Voucher</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Tally Result</th>
                                <th>Error / Message</th>
                            </tr>
                        </thead>
                        <tbody id="tallySyncLogsBody">
                            <tr><td colspan="8" class="text-center text-muted">Click "Logs" to view date-wise transfer history.</td></tr>
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
    const masterOptionsUrl = "{{ url('api/tally/master-options') }}";
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
            syncLogsBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No sync logs found.</td></tr>';
            return;
        }

        syncLogsBody.innerHTML = logs.map((row) => {
            const statusClass = row.status === 'success' ? 'badge bg-success' : 'badge bg-danger';
            const lineErrors = Array.isArray(row.tally_line_errors) ? row.tally_line_errors.join(' | ') : '';
            const tallyResult = `Created: ${row.tally_created ?? 0}, Altered: ${row.tally_altered ?? 0}, Errors: ${row.tally_errors ?? 0}`;
            return `
                <tr>
                    <td>${row.id ?? ''}</td>
                    <td>${row.synced_at || row.created_at || ''}</td>
                    <td>${row.voucher_type || row.entity_type || ''}</td>
                    <td>${row.entity_id ?? ''}</td>
                    <td>${row.voucher_no || ''}</td>
                    <td>${row.amount || ''}</td>
                    <td><span class="${statusClass}">${row.status ?? ''}</span></td>
                    <td>${tallyResult}</td>
                    <td>${lineErrors || row.message || ''}</td>
                </tr>
            `;
        }).join('');
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
            const entity = (document.getElementById('manual_sync_entity')?.value || '').trim();
            const companyName = (document.getElementById('manual_sync_company_name')?.value || '').trim();
            const id = (document.getElementById('manual_sync_id')?.value || '').trim();
            const fromDate = (document.getElementById('manual_sync_from_date')?.value || '').trim();
            const toDate = (document.getElementById('manual_sync_to_date')?.value || '').trim();
            const baseUrl = manualSyncBtn.dataset.baseUrl || '';
            const dateUrl = manualSyncBtn.dataset.dateUrl || '';

            if (entity === '') {
                showManualResult(false, 'Please select a transfer type.');
                return;
            }

            if (id === '' && (fromDate === '' || toDate === '')) {
                showManualResult(false, 'Enter Record ID or select From Date and To Date.');
                return;
            }

            manualSyncBtn.disabled = true;
            const originalText = manualSyncBtn.innerText;
            manualSyncBtn.innerText = 'Transferring...';

            try {
                const url = id !== ''
                    ? `${baseUrl}/${encodeURIComponent(entity)}/${encodeURIComponent(id)}`
                    : dateUrl;
                const payload = id !== ''
                    ? { company_name: companyName }
                    : { entity: entity, from_date: fromDate, to_date: toDate, company_name: companyName };

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                const { text, json } = await parseResponse(response);
                if (!json) {
                    throw new Error((text || `HTTP ${response.status}`).substring(0, 300));
                }

                showManualResult(!!json.status, json.message || 'Manual sync response received.');
                if (syncLogsBtn) {
                    syncLogsBtn.click();
                }
            } catch (error) {
                showManualResult(false, error?.message || 'Transfer request failed.');
            } finally {
                manualSyncBtn.disabled = false;
                manualSyncBtn.innerText = originalText;
            }
        });
    }

    if (syncLogsBtn) {
        syncLogsBtn.addEventListener('click', async function() {
            const baseUrl = syncLogsBtn.dataset.url;
            const entity = (document.getElementById('manual_sync_entity')?.value || '').trim();
            const fromDate = (document.getElementById('manual_sync_from_date')?.value || '').trim();
            const toDate = (document.getElementById('manual_sync_to_date')?.value || '').trim();
            const params = new URLSearchParams({limit: '100'});
            if (entity) {
                params.set('entity_type', entity);
            }
            if (fromDate) {
                params.set('from_date', fromDate);
            }
            if (toDate) {
                params.set('to_date', toDate);
            }
            const url = `${baseUrl}?${params.toString()}`;
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
