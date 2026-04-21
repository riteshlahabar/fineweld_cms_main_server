@extends('layouts.app')
@section('title', __('partnership::partner.create_settlement'))

        @section('content')
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                <x-breadcrumb :langArray="[
                                            'partnership::partner.partners',
                                            'partnership::partner.partner_settlement_list',
                                            'partnership::partner.create_settlement',
                                        ]"/>
                <div class="row">
                    <form class="row g-3 needs-validation" id="settlementForm" action="{{ route('partnership.partner.settlement.store') }}" enctype="multipart/form-data">
                        {{-- CSRF Protection --}}
                        @csrf
                        @method('POST')

                        <input type="hidden" name="row_count" value="0">
                        <input type="hidden" id="base_url" value="{{ url('/') }}">
                        <input type="hidden" name="operation" value="save">

                        <div class="col-12 col-lg-12">
                            <div class="card">
                                <div class="card-body p-4 row g-3">
                                        <div class="col-md-6 ">
                                            <x-label for="settlement_date" name="{{ __('app.date') }}" />
                                            <div class="input-group">
                                                <x-input type="text" additionalClasses="datepicker" name="settlement_date" :required="true" value=""/>
                                                <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <x-label for="settlement_code" name="{{ __('partnership::partner.settlement_code') }}" />
                                            <div class="input-group mb-3">
                                                <x-input type="text" name="prefix_code" :required="true" placeholder="Prefix Code" value="{{ $data['prefix_code'] }}"/>
                                                <span class="input-group-text">#</span>
                                                <x-input type="text" name="count_id" :required="true" placeholder="Serial Number" value="{{ $data['count_id'] }}"/>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <x-label for="reference_no" name="{{ __('app.reference_no') }}" />
                                            <x-input type="text" name="reference_no" value=""/>
                                        </div>
                                        <div class="col-md-6">
                                            <x-label for="partner_id" name="{{ __('partnership::partner.partner') }}" />
                                            <select class="form-select partner-ajax" data-placeholder="Select Partner" id="partner_id" name="partner_id"></select>
                                        </div>
                                        <div class="col-md-6">
                                            <x-label for="payment_type_id" name="{{ __('payment.payment_type') }}" />
                                            <select class="form-select select2 payment-type-ajax" name="payment_type_id" data-placeholder="Choose one thing"></select>
                                        </div>
                                        <div class="col-md-6">
                                            <x-label for="amount" name="{{ __('payment.amount') }}" />
                                            <div class="input-group">
                                                <select class="form-select cu-flex-30" name="payment_direction">
                                                    <option value="paid">Paid</option>
                                                    <option value="received">Received</option>
                                                </select>

                                                <x-input type="text" additionalClasses="cu_numeric" name="amount" value=""/>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <x-label for="note" name="{{ __('payment.note') }}" />
                                            <x-textarea name="note" value=""/>
                                        </div>
                                </div>
                                <div class="card-body p-4 row g-3">
                                        <div class="col-md-12">
                                            <div class="d-md-flex d-grid align-items-center gap-3">
                                                <x-button type="submit" class="primary px-4" text="{{ __('app.submit') }}" />
                                                <x-anchor-tag href="{{ route('dashboard') }}" text="{{ __('app.close') }}" class="btn btn-light px-4" />
                                            </div>
                                        </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!--end row-->
            </div>
        </div>
        <!-- Import Modals -->

        @endsection

@section('js')
    <script src="{{ versionedAsset('custom/js/common/common.js') }}"></script>
    <script src="{{ versionedAsset('custom/js/modules/partnership/common/module-partnership-common.js') }}"></script>
    <script src="{{ versionedAsset('custom/js/modules/partnership/settlement/settlement.js') }}"></script>
@endsection
