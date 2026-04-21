@extends('layouts.app')
@section('title', __('partnership::contract.details'))

		@section('content')
		<!--start page wrapper -->
		<div class="page-wrapper">
			<div class="page-content">
				<x-breadcrumb :langArray="[
                                            'partnership::partner.partner',
                                            'partnership::contract.list',
                                            'partnership::contract.details',
										]"/>
				<div class="row">
					<div class="col-12 col-lg-12">

                        @include('layouts.session')

                        <input type="hidden" id="base_url" value="{{ url('/') }}">

                        <div class="card">
                    <div class="card-body">
                        <div class="toolbar hidden-print">
                                <div class="text-end">
                                    @can(['partner.contract.edit'])
                                    <a href="{{ route('partnership.contract.edit', ['id' => $contract->id]) }}" class="btn btn-outline-primary"><i class="bx bx-edit"></i>{{ __('app.edit') }}</a>
                                    @endcan
                                </div>
                                <hr/>
                            </div>
                        <div id="printForm">
                            <div class="invoice overflow-auto">
                                <div class="min-width-600">
                                    <header>
                                        <div class="row">
                                            <div class="col">
                                                <a href="javascript:;">
                                                    <img src={{ "/company/getimage/" . app('company')['colored_logo'] }} width="80" alt="" />
                                                </a>
                                            </div>
                                            <div class="col company-details">
                                                <h2 class="name">
                                                    <a href="javascript:;">
                                                    {{ app('company')['name'] }}
                                                    </a>
                                                </h2>
                                                <div>{{ app('company')['address'] }}</div>
                                            </div>
                                        </div>
                                    </header>
                                    <main>
                                        <div class="row contacts">


                                            <div class="col invoice-details">
                                                <h1 class="invoice-id">{{ __('partnership::contract.contract') }} #{{ $contract->contract_code }}</h1>
                                                <div class="date">{{ __('app.date') }}: {{ $contract->formatted_contract_date  }}</div>

                                            </div>
                                        </div>

                                        <table id="printInvoice">
                                            <thead>
                                                <tr class="text-uppercase">
                                                    <th>#</th>
                                                    <th class="text-left">{{ __('item.item') }}</th>
                                                    <th scope="col">{{ __('partnership::contract.share_type') }}</th>
                                                    <th scope="col">{{ __('partnership::contract.share_value') }}</th>
                                                    <th scope="col">{{ __('partnership::partner.partner') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $i=1;
                                                @endphp

                                                @foreach($contract->contractItems as $contractItem)
                                                <tr>
                                                    <td class="no">{{ $i++ }}</td>
                                                    <td class="text-left">
                                                        <h3>
                                                            <!-- Service Name -->
                                                            {{ $contractItem->item->name }}
                                                        </h3>
                                                        <!-- Description -->
                                                        <small>{{ $contractItem->description }}</small>

                                                   </td>
                                                    <td>
                                                         {{ ucfirst($contractItem->share_type) }}
                                                    </td>
                                                    <td>
                                                         {{ $formatNumber->formatWithPrecision($contractItem->share_value) }}
                                                    </td>
                                                    <td>
                                                         {{ $contractItem->partner->getFullName() }}
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </main>

                                </div>
                                <!--DO NOT DELETE THIS div. IT is responsible for showing footer always at the bottom-->
                                <div></div>
                            </div>
                        </div>
                    </div>
                </div>
					</div>
				</div>
				<!--end row-->
			</div>
		</div>

		@endsection
