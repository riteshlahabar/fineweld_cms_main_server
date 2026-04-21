<!-- partnerPartyPaymentAllocationModal: start -->
<div class="modal fade" id="partnerPartyPaymentAllocationModal" >
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title form-heading" ><i class="bx bx-transfer me-2"></i>
                {{ __('partnership::partner.allocate_to_partner') }}
            </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="px-4 pt-3">
                <h6 class="fw-bold">{{ __('payment.details') }}</h6>
                <hr>

                <div class="alert alert-info mb-0">
                        <i class="bx bx-info-circle me-2"></i>
                    {{ (__('partnership::partner.party_manual_payments_and_remaining_balance_allocation')) }}
                </div>

                <div class="card card-body mb-2 mt-3">
                    <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <span class="text-muted">{{ __('payment.amount') }}:</span>
                                    <span class="fw-bold ms-2">{{ $formatNumber->formatWithPrecision($paymentTransaction->amount, comma:true) }}</span>
                                </div>
                                <div class="mb-2">
                                    <span class="text-muted">{{ __('app.date') }}:</span>
                                    <span class="fw-bold ms-2">{{ $formatDate->toUserDateFormat($paymentTransaction->transaction_date) }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <span class="text-muted">{{ __('app.note') }}:</span>
                                    <span class="fw-bold ms-2">{{ $paymentTransaction->note ?? '-' }}</span>
                                </div>
                                <div class="mb-2">
                                    <span class="text-muted">{{ __('payment.remaining_amount') }}:</span>
                                    <span class="fw-bold ms-2">{{ $formatNumber->formatWithPrecision($paymentTransaction->remaining_amount, comma:true) }}</span>
                                </div>
                            </div>
                        </div>
                </div>

            </div>

            <form class=" needs-validation" id="partnerPartyPaymentAllocationForm" action="{{ route('partnership.partner.party-payment.allocation.store') }}" enctype="multipart/form-data">
                {{-- CSRF Protection --}}
                @csrf
                @method('POST')


                <div class="modal-body row g-3 px-4 pt-3">
                    <h6 class="fw-bold">{{ __('partnership::partner.allocated_payment_to_partner') }}</h6>
                <hr>
                <div class="alert alert-info mb-0">
                    <i class="bx bx-info-circle me-2"></i>
                    {{ __('partnership::partner.distribute_amount_to_partner_message') }}
                </div>
                        <div class="col-md-6">
                            <x-label for="partner_id" name="{{ __('partnership::partner.partner') }}" />
                            <select class="form-select partner-ajax" data-placeholder="Select Partner" id="partner_id" name="partner_id"></select>
                        </div>

                        <div class="col-md-6">
                            <x-label for="transaction_date" name="{{ __('app.date') }}" />
                            <div class="input-group mb-3">
                                <x-input type="text" additionalClasses="datepicker-edit" name="transaction_date" :required="true" value="{{ $formatDate->toUserDateFormat(now()) }}"/>
                                <span class="input-group-text" id="input-near-focus" role="button"><i class="fadeIn animated bx bx-calendar-alt"></i></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <x-label for="amount" name="{{ __('payment.amount') }}" />
                            <x-input type="text" additionalClasses="cu_numeric text-end" name="amount" value="{{ $formatNumber->formatWithPrecision($paymentTransaction->remaining_amount, comma:false) }}"/>
                        </div>
                        <div class="col-md-6">
                            <x-label for="note" name="{{ __('app.note') }}" />
                            <x-textarea name="note" value=""/>
                        </div>
                        <!-- Hidden Fields -->
                        <x-input type="hidden" name="payment_transaction_id" value="{{ $paymentTransaction->id }}"/>
                </div>

                <!-- Allocated Transactions -->
                <div class="px-4 pt-3">
                    <h6 class="fw-bold mb-3">{{ __('partnership::partner.allocated_party_transaction') }}</h6>
                    <hr>

                    <div class="card border shadow-sm mb-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="payment-history-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center">{{ __('app.date') }}</th>
                                            <th class="text-center">{{ __('partnership::partner.partner') }}</th>
                                            <th class="text-center">{{ __('payment.amount') }}</th>
                                            <th class="text-center">{{ __('payment.payment_type') }}</th>
                                            <th class="text-center">{{ __('app.status') }}</th>
                                            <th class="text-center">{{ __('app.note') }}</th>
                                            <th class="text-center">{{ __('app.date') }}</th>
                                            <th class="text-center">{{ __('app.action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($paymentTransaction->partnerPartyTransactions->count() > 0)
                                            @foreach($paymentTransaction->partnerPartyTransactions as $partnerPartyTransaction)
                                                <tr>
                                                    <td class="text-center">{{ $formatDate->toUserDateFormat($partnerPartyTransaction->transaction_date) }}</td>
                                                    <td class="text-center">{{ $partnerPartyTransaction->partner->getFullName() }}</td>
                                                    <td class="text-end">{{ $formatNumber->formatWithPrecision($partnerPartyTransaction->amount, comma:true) }}</td>
                                                    <td class="text-center">{{ $partnerPartyTransaction->paymentType->name }}</td>
                                                    <td class="text-center">
                                                        <span class="badge px-3  p-2 text-uppercase {{ $partnerPartyTransaction->unique_code == 'PAID' ? 'text-danger bg-light-danger' : 'text-success bg-light-success' }}">
                                                            {{ $partnerPartyTransaction->unique_code == 'PAID' ? 'Paid' : 'Received' }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">{{ $partnerPartyTransaction->note }}</td>
                                                    <td class="text-center">{{ $formatDate->toUserDateFormat($partnerPartyTransaction->created_at) }}</td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-partner-transaction" data-id="{{ $partnerPartyTransaction->id }}" title="{{ __('app.delete') }}">
                                                            <i class="bx bx-trash me-0"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr class="fw-bold table-active">
                                                <td colspan="2" class="text-end">{{ __('app.total') }}</td>
                                                <td class="text-end">{{ $formatNumber->formatWithPrecision($paymentTransaction->partnerPartyTransactions->sum('amount'), comma:true) }}</td>
                                                <td colspan="5"></td>
                                            </tr>
                                        @else
                                            <tr>
                                                <td colspan="8" class="text-center py-4 text-muted">
                                                    {{ __("app.no_records_found") }}
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <x-button type="submit" class="btn btn-primary" text="{{ __('app.submit') }}" />
                </div>
            </form>


        </div>
    </div>
</div>
<!-- partnerPartyPaymentAllocationModal: end -->
