@extends('layouts.app')
@section('title', $lang['partner_list'])

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endsection
		@section('content')
		<!--start page wrapper -->
		<div class="page-wrapper">
			<div class="page-content">
					<x-breadcrumb :langArray="[
											'partnership::partner.partners',
											$lang['partner_list'],
										]"/>

                    <div class="card">

					<div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
					    <!-- Other content on the left side -->
					    <div>
					    	<h5 class="mb-0 text-uppercase">{{ $lang['partner_list'] }}</h5>
					    </div>
					    <div class="d-flex gap-2">

						    @can($lang['partner_type'].'.create')
						    <!-- Button pushed to the right side -->
						    <x-anchor-tag href="{{ route('partnership.partner.create') }}" text="{{ $lang['partner_create'] }}" class="btn btn-primary px-5" />
						    @endcan
						</div>
					</div>
					<div class="card-body">

                        <form class="row g-3 needs-validation" id="datatableForm" action="{{ route('partnership.partner.delete') }}" enctype="multipart/form-data">
                            {{-- CSRF Protection --}}
                            @csrf
                            @method('POST')
                            <input type="hidden" id="base_url" value="{{ url('/') }}">
                            <input type="hidden" name="partner_type" value="{{ $lang['partner_type'] }}">
                            <div class="table-responsive">
								<table class="table table-striped table-bordered border w-100" id="datatable">
									<thead>
										<tr>
											<th class="d-none"><!-- Which Stores ID & it is used for sorting --></th>
	                                        <th><input class="form-check-input row-select" type="checkbox"></th>
											<th>{{ __('app.name') }}</th>
											<th>{{ __('app.mobile') }}</th>
											<th>{{ __('app.whatsapp') }}</th>
											<th>{{ __('app.email') }}</th>
											<th>{{ __('app.profit') }}</th>
											<th>{{ __('app.balance_type') }}</th>
											<th>{{ __('app.status') }}</th>
											<th>{{ __('app.created_by') }}</th>
											<th>{{ __('app.created_at') }}</th>
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
				<!--end row-->
			</div>
		</div>

		{{-- @include("modals.partner.payment-history") --}}

		@endsection
@section('js')
<script src="{{ versionedAsset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ versionedAsset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ versionedAsset('custom/js/common/common.js') }}"></script>
<script src="{{ versionedAsset('custom/js/modules/partnership/partner/partner-list.js') }}"></script>
@endsection
