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
                <h5 class="mb-0 text-uppercase">Tally Integration</h5>
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