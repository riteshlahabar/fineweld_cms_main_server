@extends('partnership::allocation.party-payment.layout')

@section('title', __('partnership::partner.party_balance_allocation'))

@section('allocation-content')

<div class="tab-pane fade show active" id="partyOpeningBalanceTab" role="tabpanel">


    <div class="row g-3">
        <div class="alert alert-info mb-0">
                <i class="bx bx-info-circle me-2"></i>
            {{ (__('partnership::partner.party_opening_balance_allocation')) }}
        </div>
        <div class="col-md-3">
            <x-label for="from_date" name="{{ __('app.from_date') }}" />
            <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Filter by Payment Out Date"><i class="fadeIn animated bx bx-info-circle"></i></a>
            <div class="input-group mb-3">
                <x-input type="text" additionalClasses="datepicker-edit" name="from_date" :required="true" value=""/>
                <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
            </div>
        </div>
        <div class="col-md-3">
            <x-label for="to_date" name="{{ __('app.to_date') }}" />
            <a tabindex="0" class="text-primary" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-content="Filter by Payment Out Date"><i class="fadeIn animated bx bx-info-circle"></i></a>
            <div class="input-group mb-3">
                <x-input type="text" additionalClasses="datepicker-edit" name="to_date" :required="true" value=""/>
                <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
            </div>
        </div>
        <div class="col-md-3">
            <x-label for="user_id" name="{{ __('user.user') }}" />
            <x-dropdown-user selected="" :showOnlyUsername='true' />
        </div>
    </div>

    <form class="row g-3 needs-validation" id="datatableForm" action="" enctype="multipart/form-data">
        {{-- CSRF Protection --}}
        @csrf
        @method('GET')
        <input type="hidden" id="base_url" value="{{ url('/') }}">
        <div class="table-responsive">
            <table class="table table-striped table-bordered border w-100" id="datatable">
                <thead>
                    <tr>
                        <th class="d-none"><!-- Which Stores ID & it is used for sorting --></th>
                        <th>{{ __('app.date') }}</th>
                        <th>{{ __('party.type') }}</th>
                        <th>{{ __('party.name') }}</th>
                        <th>{{ __('app.mobile') }}</th>
                        <th>{{ __('payment.amount') }}</th>
                        <th>{{ __('partnership::partner.allocated_amount') }}</th>
                        <th>{{ __('partnership::partner.unallocated') }}</th>
                        <th>{{__('payment.payment_direction') }}</th>
                        <th>{{ __('app.created_by') }}</th>
                        <th>{{ __('app.created_at') }}</th>
                        <th>{{ __('app.action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script src="{{ versionedAsset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ versionedAsset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ versionedAsset('custom/js/common/common.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modules/partnership/common/module-partnership-common.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modules/partnership/allocation/party-balance.js') }}"></script>
@endpush
