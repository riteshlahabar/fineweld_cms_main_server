@extends('layouts.app')


@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endsection
        @section('content')

        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content">
                    <x-breadcrumb :langArray="[
                                            'partnership::partner.partners',
                                            'app.reports',
                                            match (true) {
                                                request()->is('partner/report/contract') => 'partnership::contract.report',
                                                request()->is('partner/report/contract-items') => 'partnership::contract.summary_report',
                                                request()->is('partner/report/partner/items') => 'partnership::partner.items',
                                                request()->is('partner/report/partner-profit') => 'partnership::partner.profit_report',
                                                request()->is('partner/report/partner-profit-item-wise') => 'partnership::partner.profit_report_item_wise',
                                                request()->is('partner/report/partner-settlement') => 'partnership::partner.settlement_report',
                                                default => '',
                                            }
                                        ]"/>

                    <div class="card">

                    <div class="card-body">
                        <ul class="nav nav-tabs nav-success" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ request()->is('partner/report/contract') ? 'active' : '' }}" href="{{ route('partnership.report.contract') }}" role="tab" aria-selected="true">
                                    <div class="d-flex align-items-center">
                                        <div class="tab-icon"><i class='bx bx-bar-chart-square font-18 me-1'></i>
                                        </div>
                                        <div class="tab-title">{{ __('partnership::contract.report') }}</div>
                                    </div>
                                </a>
                            </li>

                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ request()->is('partner/report/contract/items') ? 'active' : '' }}"  href="{{ route('partnership.report.contract.item') }}" role="tab" aria-selected="true">
                                    <div class="d-flex align-items-center">
                                        <div class="tab-icon"><i class='bx bx-bar-chart-square font-18 me-1'></i>
                                        </div>
                                        <div class="tab-title">{{ __('partnership::contract.items_report') }}</div>
                                    </div>
                                </a>
                            </li>

                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ request()->is('partner/report/partner/items') ? 'active' : '' }}"  href="{{ route('partnership.report.partner.items') }}" role="tab" aria-selected="true">
                                    <div class="d-flex align-items-center">
                                        <div class="tab-icon"><i class='bx bx-bar-chart-square font-18 me-1'></i>
                                        </div>
                                        <div class="tab-title">{{ __('partnership::partner.items') }}</div>
                                    </div>
                                </a>
                            </li>

                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ request()->is('partner/report/partner-profit') ? 'active' : '' }}"  href="{{ route('partnership.report.profit') }}" role="tab" aria-selected="true">
                                    <div class="d-flex align-items-center">
                                        <div class="tab-icon"><i class='bx bx-bar-chart-square font-18 me-1'></i>
                                        </div>
                                        <div class="tab-title">{{ __('partnership::partner.profit_report') }}</div>
                                    </div>
                                </a>
                            </li>

                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ request()->is('partner/report/partner-profit-item-wise') ? 'active' : '' }}"  href="{{ route('partnership.report.profit.item.wise') }}" role="tab" aria-selected="true">
                                    <div class="d-flex align-items-center">
                                        <div class="tab-icon"><i class='bx bx-bar-chart-square font-18 me-1'></i>
                                        </div>
                                        <div class="tab-title">{{ __('partnership::partner.profit_report_item_wise') }}</div>
                                    </div>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ request()->is('partner/report/partner-settlement') ? 'active' : '' }}"  href="{{ route('partnership.report.settlement') }}" role="tab" aria-selected="true">
                                    <div class="d-flex align-items-center">
                                        <div class="tab-icon"><i class='bx bx-bar-chart-square font-18 me-1'></i>
                                        </div>
                                        <div class="tab-title">{{ __('partnership::partner.settlement_report') }}</div>
                                    </div>
                                </a>
                            </li>

                        </ul>
                        <div class="tab-content py-3 m-3">
                            @yield('allocation-content')
                        </div>
                    </div>
                </div>
                    </div>
                </div>
                <!--end row-->
            </div>
        </div>

        @endsection

