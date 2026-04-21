@extends('partnership::reports.layout')

@section('title', __('partnership::contract.items_report'))

@section('allocation-content')

<div class="tab-pane fade show active" id="partyOpeningBalanceTab" role="tabpanel">


    <div class="row g-3">
        <form class="row g-3 needs-validation" id="reportForm" action="{{ route('partnership.report.contract-item.ajax') }}" enctype="multipart/form-data">
                        {{-- CSRF Protection --}}
                        @csrf
                        @method('POST')

                        <input type="hidden" name="row_count" value="0">
                        <input type="hidden" name="total_amount" value="0">
                        <input type="hidden" id="base_url" value="{{ url('/') }}">
                        <div class="col-12 col-lg-12">
                            <div class="">
                                <div class=" row g-3">

                                        <div class="col-md-6 mb-3">
                                            <x-label for="from_date" name="{{ __('app.from_date') }}" />
                                            <div class="input-group">
                                                <x-input type="text" additionalClasses="datepicker" name="from_date" :required="true" value=""/>
                                                <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <x-label for="to_date" name="{{ __('app.to_date') }}" />
                                            <div class="input-group">
                                                <x-input type="text" additionalClasses="datepicker" name="to_date" :required="true" value=""/>
                                                <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <x-label for="partner_id" name="{{ __('partnership::partner.partner') }}" />
                                            <select class="form-select partner-ajax" data-placeholder="Select Partner" id="partner_id" name="partner_id"></select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <x-label for="item_id" name="{{ __('item.item_name') }}" />
                                            <select class="item-ajax form-select" data-placeholder="Select Item" id="item_id" name="item_id"></select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <x-label for="brand_id" name="{{ __('item.brand.brand') }}" />
                                            <select class="brand-ajax form-select" data-placeholder="Select Brand" id="brand_id" name="brand_id"></select>
                                        </div>
                                </div>

                                <div class=" pt-4 pb-4 row g-3">
                                        <div class="col-md-12">
                                            <div class="d-md-flex d-grid align-items-center gap-3">
                                                <x-button type="submit" class="primary px-4" text="{{ __('app.submit') }}" />
                                                <x-anchor-tag href="{{ route('dashboard') }}" text="{{ __('app.close') }}" class="btn btn-light px-4" />
                                            </div>
                                        </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-12">
                            <div class="card">
                                <div class="card-header px-4 py-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <h5 class="mb-0">{{ __('app.records') }}</h5>
                                        </div>
                                        <div class="col-6 text-end">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-outline-success">{{ __('app.export') }}</button>
                                                <button type="button" class="btn btn-outline-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"> <span class="visually-hidden">Toggle Dropdown</span>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><button type='button' class="dropdown-item" id="generate_excel"><i class="bx bx-spreadsheet mr-1"></i>{{ __('app.excel') }}</button></li>
                                                    <li><button type='button' class="dropdown-item" id="generate_pdf"><i class="bx bx-file mr-1"></i>{{ __('app.pdf') }}</button></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-4 row g-3">
                                        <div class="col-md-12 table-responsive">
                                            <table class="table table-bordered" id="recordsTable">
                                                <thead>
                                                    <tr class="text-uppercase">
                                                        <th>#</th>
                                                        <th>{{ __('app.date') }}</th>
                                                        <th>{{ __('partnership::contract.code') }}</th>
                                                        <th>{{ __('item.item') }}</th>
                                                        <th>{{ __('item.brand.brand') }}</th>
                                                        <th>{{ __('partnership::contract.share_type') }}</th>
                                                        <th>{{ __('partnership::contract.share_value') }}</th>
                                                        <th>{{ __('partnership::partner.partner') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                </div>
                            </div>
                        </div>
                    </form>
    </div>
</div>

@endsection

@push('scripts')
@include("plugin.export-table")
<script src="{{ versionedAsset('custom/js/common/common.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modules/partnership/common/module-partnership-common.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modules/partnership/reports/contract-items.js') }}"></script>
@endpush
