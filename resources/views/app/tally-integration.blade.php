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
    </div>
</div>
@endsection

@section('js')
<script>
(function() {
    const testBtn = document.getElementById('testConnectionBtn');
    const resultBox = document.getElementById('connectionTestResult');
    const form = document.getElementById('tallyConnectionForm');

    if (!testBtn || !resultBox || !form) {
        return;
    }

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
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    host: host,
                    port: port,
                    odbc_port: odbcPort
                })
            });

            const data = await response.json();
            const isOk = !!data.status;
            const css = isOk ? 'alert-success' : 'alert-danger';

            let extra = '';
            if (data.details) {
                const odbcStatus = data.details.odbc_port ? data.details.odbc_port.status : 'N/A';
                const appStatus = data.details.app_port ? data.details.app_port.status : 'N/A';
                extra = `<div class="mt-1"><strong>ODBC:</strong> ${odbcStatus}<br><strong>App Port:</strong> ${appStatus}</div>`;
            }

            resultBox.className = `alert ${css}`;
            resultBox.innerHTML = `<strong>${data.message || 'Done'}</strong>${extra}`;
            resultBox.classList.remove('d-none');

            if (typeof iziToast !== 'undefined') {
                (isOk ? iziToast.success : iziToast.error)({
                    title: isOk ? 'Success' : 'Error',
                    layout: 2,
                    message: data.message || 'Connection test completed.'
                });
            }
        } catch (error) {
            resultBox.className = 'alert alert-danger';
            resultBox.innerHTML = '<strong>Connection test failed due to network or server error.</strong>';
            resultBox.classList.remove('d-none');
            if (typeof iziToast !== 'undefined') {
                iziToast.error({title: 'Error', layout: 2, message: 'Connection test failed due to network or server error.'});
            }
        } finally {
            testBtn.disabled = false;
            testBtn.innerText = 'Test Connection';
        }
    });
})();
</script>
@endsection