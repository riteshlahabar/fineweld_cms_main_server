@extends('layouts.app')
@section('title', 'Tally Integration')

@section('content')
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
                        <div class="col-md-4">
                            <x-label for="host" name="Host / IP" />
                            <x-input type="text" name="host" id="host" :required="true" value="{{ old('host', $connectionSettings->host ?? '') }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="port" name="Port" />
                            <x-input type="number" name="port" id="port" :required="false" value="{{ old('port', $connectionSettings->port ?? '') }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="odbc_port" name="ODBC Port" />
                            <x-input type="number" name="odbc_port" id="odbc_port" :required="true" value="{{ old('odbc_port', $connectionSettings->odbc_port ?? '') }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="username" name="User ID" />
                            <x-input type="text" name="username" id="username" :required="false" value="{{ old('username', $connectionSettings->username ?? '') }}" />
                        </div>
                        <div class="col-md-2">
                            <x-label for="password" name="Password" />
                            <x-input type="password" name="password" id="password" :required="false" value="" />
                        </div>
                        <div class="col-md-12 d-flex gap-2 mt-2">
                            <x-button type="submit" class="primary px-4" text="Save Connection" />
                            <button type="button" class="btn btn-outline-primary px-4" id="testConnectionBtn">Test Connection</button>
                        </div>
                    </div>
                </form>

                <div class="mt-3 d-none" id="connectionTestResult"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-uppercase">Field Mapping</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info border-0 border-start border-5 border-info py-2">
                    <div class="small">
                        Use target field with optional entity prefix for exact mapping:
                        <strong>item.NAME</strong>, <strong>party.PARTYGSTIN</strong>, <strong>sale.VOUCHERNUMBER</strong>.
                        Without prefix, mapping works as global target field.
                    </div>
                </div>
                <form method="POST" action="{{ $editMapping ? route('settings.tally.integration.update', ['id' => $editMapping->id]) : route('settings.tally.integration.store') }}">
                    @csrf
                    @if ($editMapping)
                    @method('PUT')
                    @endif

                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <x-label for="project_field" name="Project Field" />
                            <x-input type="text" name="project_field" id="project_field" :required="true" value="{{ old('project_field', $editMapping->project_field ?? '') }}" />
                        </div>
                        <div class="col-md-5">
                            <x-label for="tally_field" name="Tally Field" />
                            <x-input type="text" name="tally_field" id="tally_field" :required="true" value="{{ old('tally_field', $editMapping->tally_field ?? '') }}" />
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
                                <th>{{ __('app.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($mappings as $mapping)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $mapping->project_field }}</td>
                                <td>{{ $mapping->tally_field }}</td>
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
                                <td colspan="4" class="text-center text-muted">No mappings found.</td>
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
                        <x-label for="manual_sync_entity" name="Entity" />
                        <select id="manual_sync_entity" class="form-select">
                            <option value="item">Item</option>
                            <option value="party">Party</option>
                            <option value="sale">Sale</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <x-label for="manual_sync_id" name="Record ID" />
                        <input id="manual_sync_id" type="number" min="1" class="form-control" placeholder="Enter ID">
                    </div>
                    <div class="col-md-6 d-flex gap-2">
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
    const resultBox = document.getElementById('connectionTestResult');
    const form = document.getElementById('tallyConnectionForm');
    const clientErrorLogUrl = "{{ route('settings.tally.integration.client.error') }}";
    const manualSyncBtn = document.getElementById('manualSyncBtn');
    const manualSyncResult = document.getElementById('manualSyncResult');
    const syncLogsBtn = document.getElementById('loadTallySyncLogsBtn');
    const syncLogsBody = document.getElementById('tallySyncLogsBody');

    if (!testBtn || !resultBox || !form) {
        return;
    }

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

    testBtn.addEventListener('click', async function() {
        const host = document.getElementById('host').value;
        const port = document.getElementById('port').value;
        const odbcPort = document.getElementById('odbc_port').value;

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
                    port: port,
                    odbc_port: odbcPort
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
                const odbcStatus = json.details.odbc_port ? json.details.odbc_port.status : 'N/A';
                const appStatus = json.details.app_port ? json.details.app_port.status : 'N/A';
                extra = `<div class="mt-1"><strong>ODBC:</strong> ${odbcStatus}<br><strong>App Port:</strong> ${appStatus}</div>`;
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

    if (manualSyncBtn) {
        manualSyncBtn.addEventListener('click', async function() {
            const entity = (document.getElementById('manual_sync_entity')?.value || '').trim();
            const id = (document.getElementById('manual_sync_id')?.value || '').trim();
            const baseUrl = manualSyncBtn.dataset.baseUrl || '';

            if (entity === '' || id === '') {
                showManualResult(false, 'Please select entity and enter record ID.');
                return;
            }

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
                    body: JSON.stringify({})
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
