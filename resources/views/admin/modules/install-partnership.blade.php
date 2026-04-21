@extends('layouts.app')
@section('title', __('app.modules'))

@section('css')
<link rel="stylesheet" href="{{ versionedAsset('custom/libraries/quil-editor/quill.snow.css') }}">
@endsection

        @section('content')
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                <x-breadcrumb :langArray="[
                                            'app.modules',
                                            'app.install',
                                        ]"/>
                <div class="row">
                    <div class="col-12 col-lg-12">

                        @include('layouts.session')

                        <div class="card">
                            <div class="card-header px-4 py-3">
                                <h5 class="mb-0">{{ __('app.install_module') }}</h5>
                            </div>
                            <div class="card-body p-4">
                                    <div class="alert alert-info">
                                        <h5><i class="fas fa-info-circle"></i> Installation Instructions</h5>
                                        <ol>
                                            <li>{{ __('app.purchase_and_download_module') }}</li>
                                            <li>{{ __('app.upload_zip_file_below') }}</li>
                                            <li>{{ __('app.system_auto_install_module') }}</li>
                                            <li>{{ __('app.you_see_module_in_your_menu') }}</li>
                                        </ol>
                                    </div>

                                    <!-- Module Management Card -->
                                    <div class="mb-4">
                                            <!-- Install / Uninstall Form -->
                                            <form id="install-partnership" action="{{ route('admin.modules.install-partnership.submit') }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <!-- File upload & actions -->
                                                <div class="row g-3 align-items-start">
                                                    <!-- File input and Install button in single line -->
                                                    <div class="col-md-6">
                                                        <label for="module_file" class="form-label fw-semibold mb-2">Upload ZIP</label>
                                                        <div class="d-flex gap-3 align-items-end">
                                                            <!-- File input -->
                                                            <div class="flex-grow-1">
                                                                <div class="input-group">
                                                                    <input type="file"
                                                                        class="form-control @error('module_file') is-invalid @enderror"
                                                                        id="module_file"
                                                                        name="module_file"
                                                                        accept=".zip"
                                                                        required
                                                                        onchange="this.nextElementSibling.textContent = this.files[0]?.name || 'CodeCanyon ZIP'">
                                                                    <label class="input-group-text text-truncate" for="module_file">
                                                                        CodeCanyon ZIP
                                                                    </label>
                                                                </div>
                                                                @error('module_file')
                                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                                @enderror
                                                            </div>

                                                            <!-- Install button -->
                                                            <div class="flex-shrink-0">
                                                                <button type="submit" class="btn btn-outline-success px-5 install h-100">
                                                                    <i class="bx bx-archive-in mr-1"></i>Install
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="form-text mt-2">Select the <strong>.zip</strong> file downloaded from CodeCanyon.</div>
                                                    </div>
                                                </div>
                                            </form>

                                            <!-- Uninstall Form (hidden) -->
                                            <form id="uninstall-partnership" action="{{ route('admin.modules.uninstall-partnership.submit') }}" method="POST" class="d-none">
                                                @csrf
                                                @method('POST')
                                            </form>
                                            <form id="activate-partnership" action="{{ route('admin.modules.activate-partnership.submit') }}" method="POST" class="d-none">
                                                @csrf
                                                @method('POST')
                                            </form>
                                            <form id="deactivate-partnership" action="{{ route('admin.modules.deactivate-partnership.submit') }}" method="POST" class="d-none">
                                                @csrf
                                                @method('POST')
                                            </form>
                                    </div>

                            </div>

                        </div>

                        @if($modules->count() > 0)
                        <div class="card">
                            <div class="card-header px-4 py-3">
                                <h5 class="mb-0">{{ __('app.module_details') }}</h5>
                            </div>
                            <div class='card-body border-top'>

                                <!-- Module Status Table -->
                                <div class="table-responsive mt-4">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>{{ __('app.module_name') }}</th>
                                                <th>{{ __('app.status') }}</th>
                                                <th>{{ __('app.version') }}</th>
                                                <th>{{ __('app.last_updated') }}</th>
                                                <th>{{ __('app.description') }}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($modules as $module)
                                            <tr>
                                                <td>
                                                    <div>
                                                        <span class="fw-semibold text-primary">{{ $module->name }}</span>
                                                    </div>
                                                    <div class="text-muted mt-1">
                                                        <span role="button" class=" text-secondary me-2 uninstall">Uninstall</span>
                                                        <span class="text-secondary">|</span>
                                                        <span role="button" class="text-secondary ms-2 {{ $module->is_active ? 'deactivate' : 'activate' }}">{{ $module->is_active ? 'Deactivate' : 'Activate' }}</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge p-2 text-uppercase px-3 fs-0 bg-{{ $module->is_active ? 'light-success text-success' : 'light-danger text-danger' }}">
                                                        {{ $module->is_active ? 'Active' : 'Deactive' }}
                                                    </span>
                                                </td>
                                                <td>{{ $module->version }}</td>
                                                <td>{{ $formatDate->toUserDateFormat($module->updated_at) }}</td>
                                                <td>{{ $module->description }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                <!--end row-->
            </div>
        </div>
        @endsection

@section('js')
<script src="{{ versionedAsset('custom/js/install-module.js') }}"></script>
@endsection
