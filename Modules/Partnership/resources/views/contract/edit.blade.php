@extends('layouts.app')
@section('title', __('partnership::contract.update'))

        @section('content')
        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                <x-breadcrumb :langArray="[
                                            'partnership::partner.partners',
                                            'partnership::contract.list',
                                            'partnership::contract.update',
                                        ]"/>
                <div class="row">
                    <form class="g-3 needs-validation" id="contractForm" action="{{ route('partnership.contract.update') }}" enctype="multipart/form-data">
                        {{-- CSRF Protection --}}
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="contract_id" value="{{ $contract->id }}">
                        <input type="hidden" name="row_count" value="0">
                        <input type="hidden" id="base_url" value="{{ url('/') }}">
                        <input type="hidden" id="operation" name="operation" value="update">

                        <div class="row">
                            <div class="col-12 col-lg-12">
                                <div class="card">
                                    <div class="card-header px-4 py-3">
                                        <h5 class="mb-0">{{ __('partnership::contract.details') }}</h5>
                                    </div>
                                    <div class="card-body p-4 row g-3">
                                            <div class="col-md-4">
                                                <x-label for="contract_date" name="{{ __('app.date') }}" />
                                                <div class="input-group mb-3">
                                                    <x-input type="text" additionalClasses="datepicker-edit" name="contract_date" :required="true" value="{{ $contract->formatted_contract_date }}"/>
                                                    <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <x-label for="quotation_code" name="{{ __('partnership::contract.code') }}" />
                                                <div class="input-group mb-3">
                                                    <x-input type="text" name="prefix_code" :required="true" placeholder="Prefix Code" value="{{ $contract->prefix_code }}"/>
                                                    <span class="input-group-text">#</span>
                                                    <x-input type="text" name="count_id" :required="true" placeholder="Serial Number" value="{{ $contract->count_id }}"/>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <x-label for="reference_no" name="{{ __('app.reference_no') }}" />
                                                <x-input type="text" name="reference_no" :required="false" placeholder="(Optional)" value="{{ $contract->reference_no }}"/>
                                            </div>
                                    </div>
                                    <div class="card-header px-4 py-3">
                                        <h5 class="mb-0">{{ __('item.items') }}</h5>
                                    </div>
                                    <div class="card-body p-4 row g-3">
                                            <div class="col-md-9 col-sm-12 col-lg-7">
                                                <x-label for="search_item" name="{{ __('item.enter_item_name') }}" />
                                                <div class="input-group">
                                                    <span class="input-group-text" id="basic-addon1"><i class="fadeIn animated bx bx-barcode-reader text-primary"></i></span>
                                                    <input type="text" id="search_item" value="" class="form-control" required placeholder="Scan Barcode/Search Item/Brand Name">
                                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#itemModal"><i class="bx bx-plus-circle me-0"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-md-12 table-responsive">
                                                <table class="table mb-0 table-striped table-bordered" id="contractItemsTable">
                                                    <thead>
                                                        <tr class="text-uppercase">
                                                            <th scope="col">{{ __('app.action') }}</th>
                                                            <th scope="col">{{ __('item.item') }}</th>
                                                            <th scope="col">{{ __('partnership::contract.share_holders') }}</th>
                                                            <th scope="col">{{ __('partnership::contract.share_type') }}</th>
                                                            <th scope="col">{{ __('partnership::contract.share_value') }}</th>
                                                            <th scope="col">{{ __('partnership::partner.partner') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td colspan="8" class="text-center fw-light fst-italic default-row">
                                                                No items are added yet!!
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="col-md-8">
                                                <x-label for="remarks" name="{{ __('app.remarks') }}" />
                                                <x-textarea name='remarks' value='{{ $contract->remarks }}'/>
                                            </div>
                                    </div>
                                    <div class="card-header px-4 py-3"></div>
                                    <div class="card-body p-4 row g-3">
                                            <div class="col-md-12">
                                                <div class="d-md-flex d-grid align-items-center gap-3">
                                                    <x-button type="button" class="primary px-4" buttonId="submit_form" text="{{ __('app.submit') }}" />
                                                    <x-anchor-tag href="{{ route('dashboard') }}" text="{{ __('app.close') }}" class="btn btn-light px-4" />
                                                </div>
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
        @include("modals.item.create")

        @endsection

@section('js')
<script type="text/javascript">
        const itemsTableRecords = @json($contractItemsJson);
</script>

<script src="{{ versionedAsset('custom/js/modules/modules-autocomplete-item.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modules/partnership/contract/contract.js') }}"></script>
<script src="{{ versionedAsset('custom/js/common/common.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modals/item/item.js') }}"></script>
@endsection
