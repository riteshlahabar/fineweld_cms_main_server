@extends('layouts.app')
@section('title', $lang['partner_create'])

		@section('content')
		<!--start page wrapper -->
		<div class="page-wrapper">
			<div class="page-content">
				<x-breadcrumb :langArray="[
											'partnership::partner.partners',
											$lang['partner_list'],
											$lang['partner_create'],
										]"/>
				<div class="row">
					<div class="col-12 col-lg-12">
                        <div class="card">
                            <div class="card-header px-4 py-3">
                                <h5 class="mb-0">{{ $lang['partner_details'] }}</h5>
                            </div>
                            <div class="card-body p-4">
                                <form class="row g-3 needs-validation" id="partnerForm" action="{{ route('partnership.partner.store') }}" enctype="multipart/form-data">
                                    {{-- CSRF Protection --}}
                                    @csrf
                                    @method('POST')

                                    <input type="hidden" id="operation" name="operation" value="save">
                                    <input type="hidden" id="base_url" value="{{ url('/') }}">

                                    <div class="col-md-6">
                                        <x-label for="first_name" name="{{ __('app.first_name') }}" />
                                        <x-input type="text" name="first_name" :required="true" value=""/>
                                    </div>
                                    <div class="col-md-6">
                                        <x-label for="last_name" name="{{ __('app.last_name') }}" />
                                        <x-input type="text" name="last_name" :required="false" value=""/>
                                    </div>
                                    <div class="col-md-6">
                                        <x-label for="email" name="{{ __('app.email') }}" />
                                        <x-input type="email" name="email" :required="false" value=""/>
                                    </div>
                                    <div class="col-md-6">
                                        <x-label for="phone" name="{{ __('app.phone') }}" />
                                        <x-input type="number" name="phone" :required="false" value=""/>
                                    </div>
                                    <div class="col-md-6">
                                        <x-label for="mobile" name="{{ __('app.mobile') }}" />
                                        <x-input type="number" name="mobile" :required="false" value=""/>
                                    </div>
                                    <div class="col-md-6">
                                        <x-label for="whatsapp" name="{{ __('app.whatsapp_number') }}" />
                                        <x-input type="number" name="whatsapp" :required="false" value=""/>
                                    </div>
                                    <div class="col-md-6">
                                        <x-label for="tax_number" name="{{ __('tax.tax_number') }}" />
                                        <x-input type="text" name="tax_number" :required="false" value=""/>
                                    </div>



                                    <div class="col-md-6">
                                        <x-label for="status" name="{{ __('app.status') }}" />
                                        <x-dropdown-status selected="" dropdownName='status'/>
                                    </div>

                                    <ul class="nav nav-tabs nav-success" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link active" data-bs-toggle="tab" href="#successhome" role="tab" aria-selected="true">
                                                <div class="d-flex align-items-center">
                                                    <div class="tab-icon"><i class='bx bx-map font-18 me-1'></i>
                                                    </div>
                                                    <div class="tab-title">{{ __('app.address') }}</div>
                                                </div>
                                            </a>
                                        </li>
                                        <li class="nav-item item-type-product d-none" role="presentation">
                                            <a class="nav-link" data-bs-toggle="tab" href="#successprofile" role="tab" aria-selected="false">
                                                <div class="d-flex align-items-center">
                                                    <div class="tab-icon"><i class='bx bx-dollar font-18 me-1'></i>
                                                    </div>
                                                    <div class="tab-title">{{ __('app.balance') }}</div>
                                                </div>
                                            </a>
                                        </li>
                                    </ul>
                                    <div class="tab-content py-3">
                                        <div class="tab-pane fade show active" id="successhome" role="tabpanel">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <x-label for="address" name="{{ __('app.address') }}" />
                                                    <x-textarea name="address" value=""/>
                                                </div>

                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="successprofile" role="tabpanel">

                                           <div class="row">
                                                <div class="col-md-4">
                                                    <x-label for="opening_balance" name="{{ __('app.opening_balance') }}" />
                                                    <div class="input-group mb-3">
                                                        <x-input type="text" additionalClasses="cu_numeric" name="opening_balance" :required="false" value=""/>

                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <x-label for="transaction_date" name="{{ __('app.as_of_date') }}" />
                                                    <div class="input-group mb-3">
                                                        <x-input type="text" additionalClasses="datepicker" name="transaction_date" :required="true" value=""/>
                                                        <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                                                    </div>
                                                </div>

                                           </div>
                                           <div class="row mb-3">
                                                <div class="col-md-4 mb-3 item-type-product">
                                                    <x-label for="" name="{{ __('app.opening_balance_is') }}" />
                                                    <div class="d-flex align-items-center gap-3">

                                                        <x-radio-block id="to_pay" boxName="opening_balance_type" text="{{ __('party.to_pay') }}" value="to_pay" boxType="radio" parentDivClass="fw-bold" :checked=true />

                                                        <x-radio-block id="to_receive" boxName="opening_balance_type" text="{{ __('party.to_receive') }}" value="to_receive" boxType="radio" parentDivClass="fw-bold"/>
                                                    </div>
                                                </div>
                                           </div>

                                        </div>

                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-md-flex d-grid align-items-center gap-3">
                                            <x-button type="submit" class="primary px-4" text="{{ __('app.submit') }}" />
                                            <x-anchor-tag href="{{ route('dashboard') }}" text="{{ __('app.close') }}" class="btn btn-light px-4" />
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
					</div>
				</div>
				<!--end row-->
			</div>
		</div>
		@endsection

@section('js')
<script src="{{ versionedAsset('custom/js/modules/partnership/partner/partner-form.js') }}"></script>
@endsection
