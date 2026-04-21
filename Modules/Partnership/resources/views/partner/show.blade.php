@extends('layouts.app')
@section('title', $lang['partner_details'])

@section('content')
<!--start page wrapper -->
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumb :langArray="[
            'partner.partners',
            $lang['partner_list'],
            $lang['partner_details'],
        ]"/>
        <div class="row">
            <div class="col-12 col-lg-12">
                <div class="card">
                    <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $lang['partner_details'] }}</h5>
                        <div class="d-flex gap-2">
                            <a href="{{ route('partnership.partner.edit', $partner->id) }}" class="btn btn-primary">
                                <i class="bx bx-edit me-1"></i>{{ __('app.edit') }}
                            </a>
                            <a href="{{ route('partnership.partner.index') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i>{{ __('app.back') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <!-- Basic Information -->
                            <div class="col-12">
                                <h6 class="text-primary mb-3">{{ __('partner.basic_information') }}</h6>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('partner.partner_type') }}</label>
                                <p class="form-control-plaintext">{{ ucfirst($partner->partner_type) }}</p>
                            </div>

                            @if($partner->company_name)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('partner.company_name') }}</label>
                                <p class="form-control-plaintext">{{ $partner->company_name }}</p>
                            </div>
                            @endif

                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.name') }}</label>
                                <p class="form-control-plaintext">{{ $partner->display_name }}</p>
                            </div>

                            @if($partner->contact_person)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('partner.contact_person') }}</label>
                                <p class="form-control-plaintext">{{ $partner->contact_person }}</p>
                            </div>
                            @endif

                            @if($partner->designation)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('partner.designation') }}</label>
                                <p class="form-control-plaintext">{{ $partner->designation }}</p>
                            </div>
                            @endif

                            <!-- Contact Information -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary mb-3">{{ __('partner.contact_information') }}</h6>
                            </div>

                            @if($partner->email)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.email') }}</label>
                                <p class="form-control-plaintext">
                                    <a href="mailto:{{ $partner->email }}" class="text-decoration-none">{{ $partner->email }}</a>
                                </p>
                            </div>
                            @endif

                            @if($partner->mobile)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.mobile') }}</label>
                                <p class="form-control-plaintext">
                                    <a href="tel:{{ $partner->mobile }}" class="text-decoration-none">{{ $partner->mobile }}</a>
                                </p>
                            </div>
                            @endif

                            @if($partner->phone)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.phone') }}</label>
                                <p class="form-control-plaintext">
                                    <a href="tel:{{ $partner->phone }}" class="text-decoration-none">{{ $partner->phone }}</a>
                                </p>
                            </div>
                            @endif

                            @if($partner->whatsapp)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.whatsapp') }}</label>
                                <p class="form-control-plaintext">
                                    <a href="https://wa.me/{{ $partner->whatsapp }}" target="_blank" class="text-decoration-none">{{ $partner->whatsapp }}</a>
                                </p>
                            </div>
                            @endif

                            @if($partner->website)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('partner.website') }}</label>
                                <p class="form-control-plaintext">
                                    <a href="{{ $partner->website }}" target="_blank" class="text-decoration-none">{{ $partner->website }}</a>
                                </p>
                            </div>
                            @endif

                            <!-- Tax Information -->
                            @if($partner->tax_number || $partner->tax_type)
                            <div class="col-12 mt-4">
                                <h6 class="text-primary mb-3">{{ __('partner.tax_information') }}</h6>
                            </div>

                            @if($partner->tax_number)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.tax_number') }}</label>
                                <p class="form-control-plaintext">{{ $partner->tax_number }}</p>
                            </div>
                            @endif

                            @if($partner->tax_type)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.tax_type') }}</label>
                                <p class="form-control-plaintext">{{ ucfirst($partner->tax_type) }}</p>
                            </div>
                            @endif

                            @if($partner->state)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.state') }}</label>
                                <p class="form-control-plaintext">{{ $partner->state->name }}</p>
                            </div>
                            @endif
                            @endif

                            <!-- Financial Information -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary mb-3">{{ __('partner.financial_information') }}</h6>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.currency') }}</label>
                                <p class="form-control-plaintext">{{ $partner->currency ? $partner->currency->name . ' (' . $partner->currency->code . ')' : '-' }}</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.exchange_rate') }}</label>
                                <p class="form-control-plaintext">{{ number_format($partner->exchange_rate, 4) }}</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('partner.credit_limit') }}</label>
                                <p class="form-control-plaintext">
                                    @if($partner->is_set_credit_limit)
                                        {{ number_format($partner->credit_limit, 2) }}
                                    @else
                                        {{ __('app.not_set') }}
                                    @endif
                                </p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.balance') }}</label>
                                <p class="form-control-plaintext">
                                    @php
                                        $balance = $partner->to_pay - $partner->to_receive;
                                    @endphp
                                    <span class="fw-bold {{ $balance > 0 ? 'text-danger' : ($balance < 0 ? 'text-success' : 'text-muted') }}">
                                        {{ number_format(abs($balance), 2) }}
                                    </span>
                                    @if($balance > 0)
                                        <span class="text-danger">({{ __('app.to_pay') }})</span>
                                    @elseif($balance < 0)
                                        <span class="text-success">({{ __('app.to_receive') }})</span>
                                    @else
                                        <span class="text-muted">({{ __('app.balanced') }})</span>
                                    @endif
                                </p>
                            </div>

                            <!-- Address Information -->
                            @if($partner->billing_address || $partner->shipping_address)
                            <div class="col-12 mt-4">
                                <h6 class="text-primary mb-3">{{ __('partner.address_information') }}</h6>
                            </div>

                            @if($partner->billing_address)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.billing_address') }}</label>
                                <p class="form-control-plaintext">{{ $partner->billing_address }}</p>
                            </div>
                            @endif

                            @if($partner->shipping_address)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.shipping_address') }}</label>
                                <p class="form-control-plaintext">{{ $partner->shipping_address }}</p>
                            </div>
                            @endif
                            @endif

                            <!-- Additional Information -->
                            @if($partner->notes)
                            <div class="col-12 mt-4">
                                <h6 class="text-primary mb-3">{{ __('partner.additional_information') }}</h6>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">{{ __('app.notes') }}</label>
                                <p class="form-control-plaintext">{{ $partner->notes }}</p>
                            </div>
                            @endif

                            <!-- Status Information -->
                            <div class="col-12 mt-4">
                                <h6 class="text-primary mb-3">{{ __('partner.status_information') }}</h6>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.status') }}</label>
                                <p class="form-control-plaintext">
                                    @if($partner->status)
                                        <span class="badge bg-success">{{ __('app.active') }}</span>
                                    @else
                                        <span class="badge bg-danger">{{ __('app.inactive') }}</span>
                                    @endif
                                </p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('partner.default_partner') }}</label>
                                <p class="form-control-plaintext">
                                    @if($partner->default_partner)
                                        <span class="badge bg-primary">{{ __('app.yes') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('app.no') }}</span>
                                    @endif
                                </p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.created_by') }}</label>
                                <p class="form-control-plaintext">{{ $partner->creator ? $partner->creator->name : '-' }}</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.created_at') }}</label>
                                <p class="form-control-plaintext">{{ $partner->created_at->format('d/m/Y H:i:s') }}</p>
                            </div>

                            @if($partner->updated_at != $partner->created_at)
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.updated_by') }}</label>
                                <p class="form-control-plaintext">{{ $partner->updater ? $partner->updater->name : '-' }}</p>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('app.updated_at') }}</label>
                                <p class="form-control-plaintext">{{ $partner->updated_at->format('d/m/Y H:i:s') }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
