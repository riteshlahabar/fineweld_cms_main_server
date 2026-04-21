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
                                            'partnership::partner.allocations',
                                            request()->is('partner/party-payment/allocation') ? 'partnership::partner.party_payment_allocation' : 'partnership::partner.party_balance_allocation',
                                        ]"/>

                    <div class="card">

                    <div class="card-body">
                        <ul class="nav nav-tabs nav-success" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ request()->is('partner/party-payment/allocation') ? 'active' : '' }}" href="{{ route('partnership.partner.party-payment.list') }}" role="tab" aria-selected="true">
                                    <div class="d-flex align-items-center">
                                        <div class="tab-icon"><i class='bx bx-wallet-alt font-18 me-1'></i>
                                        </div>
                                        <div class="tab-title">{{ __('partnership::partner.party_payment_allocation') }}</div>
                                    </div>
                                </a>
                            </li>

                            <li class="nav-item" role="presentation">
                                <a class="nav-link {{ request()->is('partner/party-balance/allocation') ? 'active' : '' }}"  href="{{ route('partnership.partner.party-payment.opening-balance.list') }}" role="tab" aria-selected="true">
                                    <div class="d-flex align-items-center">
                                        <div class="tab-icon"><i class='bx bx-wallet-alt font-18 me-1'></i>
                                        </div>
                                        <div class="tab-title">{{ __('partnership::partner.party_balance_allocation') }}</div>
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

