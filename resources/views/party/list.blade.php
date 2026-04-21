@extends('layouts.app')
@section('title', $lang['party_list'])

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endsection

@section('content')
<!--start page wrapper -->
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumb :langArray="[
                        'party.contacts',
                        $lang['party_list'],
                    ]"/>

        <div class="card">
            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 text-uppercase">{{ $lang['party_list'] }}</h5>
                </div>
                <div class="d-flex gap-2">
                    <!-- @can('import.party')
                    <x-anchor-tag href="{{ route('import.party') }}" text="{{ __('app.import') }}" class="btn btn-outline-primary px-5" />
                    @endcan -->

                    @can('vendor.create')
                    <x-anchor-tag href="{{ route('party.create', ['partyType' => $lang['party_type']]) }}" text="{{ $lang['party_create'] }}" class="btn btn-primary px-5" />
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <!-- Vendor Type Filter -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <x-label for="vendor_type" name="Vendor Type" />
                        <select class="form-select single-select-clear-field" id="vendor_type" name="vendor_type" data-placeholder="All Vendors">
                            <option value=""></option>
                            <option value="customer">Customer</option>
                            <option value="supplier">Supplier</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <x-label for="company_type" name="Company Type" />
                        <select class="form-select single-select-clear-field" id="company_type" name="company_type" data-placeholder="All Companies">
                            <option value=""></option>
                            <option value="proprietor">Proprietor</option>
                            <option value="partnership">Partnership</option>
                            <option value="private_limited">Private Limited</option>
                            <option value="public_limited">Public Limited</option>
                            <option value="one_person_company">One Person Company</option>
                            <option value="limited_liability_partnership">LLP</option>
                        </select>
                    </div>
                </div>

                <form class="row g-3 needs-validation" id="datatableForm" action="{{ route('party.delete') }}" enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <input type="hidden" id="base_url" value="{{ url('/') }}">
                    <input type="hidden" name="party_type" value="{{ $lang['party_type'] }}">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered border w-100" id="datatable">
                            <thead>
                                <tr>
                                    <th class="d-none"></th>
                                    <th><input class="form-check-input row-select" type="checkbox"></th>
                                    <th>Company Name</th>
                                    <th>Company Address</th>
                                    <th>Vendor Type</th>
                                    <th>Primary Name</th>
                                    <th>Primary Email</th>
                                    <th>Primary Mobile</th>
                                    <th>Primary WhatsApp</th>
                                    <th>{{ __('app.balance') }}</th>
                                    <th>{{ __('app.status') }}</th>
                                    <th>{{ __('app.action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@include("modals.party.payment-history")
@endsection

@section('js')
<script src="{{ versionedAsset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ versionedAsset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ versionedAsset('custom/js/common/common.js') }}"></script>
<script src="{{ versionedAsset('custom/js/party/party-list.js') }}"></script>
@endsection
