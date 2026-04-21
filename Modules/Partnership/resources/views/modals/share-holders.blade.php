<!-- Share Holders Modal: start -->
<div class="modal fade" id="shareHoldersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title form-heading" >{{ __('partnership::partner.share_holders') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class=" needs-validation" id="" action="#" enctype="multipart/form-data">
                {{-- CSRF Protection --}}
                @csrf
                @method('POST')
                <div class="modal-body row g-3">
                        <div class="mb-0">
                            <div class="row g-3">
                                <div class="col-md-12 col-lg-12">
                                    <h6 class="fw-bold mb-2">{{ __('item.details') }}</h6>
                                    <div class="card border-0 shadow-sm mb-3">
                                        <div class="card-body p-3">
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <small class="text-muted">{{ __('item.item') }}:</small>
                                                    <span class="fw-semibold">{{ $item->name }}</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <small class="text-muted">{{ __('item.code') }}:</small>
                                                    <span class="fw-semibold">{{ $item->item_code }}</span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <small class="text-muted">{{ __('item.avg_purchase_price') }}:</small>
                                                    <span>
                                                        <span class="fw-semibold">{{ $formatNumber->formatWithPrecision($item->avg_purchase_price, comma:true) }}</span>
                                                        <span class="text-muted">/{{ $item->baseUnit->name }}</span>
                                                    </span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <small class="text-muted">{{ __('item.avg_sale_price') }}:</small>
                                                    <span>
                                                        <span class="fw-semibold">{{ $formatNumber->formatWithPrecision($item->avg_sale_price, comma:true) }}</span>
                                                        <span class="text-muted">/{{ $item->baseUnit->name }}</span>
                                                    </span>
                                                </li>
                                                @if(app('company')['tax_type'] !== 'no-tax')
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <small class="text-muted">{{ __('tax.tax') }}:</small>
                                                    <span class="fw-semibold">{{ $item->tax->name. '('. $item->tax->rate .')' }}</span>
                                                </li>
                                                @endif
                                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                                    <small class="text-muted">{{ __('item.stock_quantity') }}:</small>
                                                    <span>
                                                        <span class="fw-semibold">{{ $formatNumber->formatQuantity($item->current_stock) }}</span>
                                                        <span class="text-muted">/{{ $item->baseUnit->name }}</span>
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <h6 class="fw-bold mb-2">{{ __('partnership::partner.active_partners_and_share_details') }}</h6>
                                    <div class="card border-0 shadow-sm mb-3">
                                        <div class="card-body p-3">
                                            <div class="table-responsive">
                                                <table class="table table-bordered align-middle" id="payment-history-table">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th class="text-center">{{ __('partnership::partner.partner') }}</th>
                                                            <th class="text-center">{{ __('partnership::contract.share_type') }}</th>
                                                            <th class="text-center">{{ __('partnership::contract.share_value') }}</th>
                                                            <th class="text-center">{{ __('partnership::contract.effective_from') }}</th>
                                                            <th class="text-center">{{ __('partnership::contract.effective_to') }}</th>
                                                            <th class="text-center">{{ __('app.status') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @if($contractItems->count() > 0)
                                                            @foreach($contractItems as $contractItem)
                                                                <tr>
                                                                    <td>{{ $contractItem->partner->getFullName() }}</td>
                                                                    <td>
                                                                        {{ ucfirst($contractItem->share_type) }}
                                                                        @if($contractItem->share_type === 'percentage')
                                                                            (%)
                                                                        @else
                                                                            ($)
                                                                        @endif
                                                                    </td>
                                                                    <td>{{ $formatNumber->formatWithPrecision($contractItem->share_value, comma:true) }}</td>
                                                                    <td>{{ $contractItem->formatted_effective_from }}</td>
                                                                    <td>{{ $contractItem->formatted_effective_to }}</td>
                                                                    <td>
                                                                        @if($contractItem->is_active)
                                                                            <span class="badge rounded-pill text-success bg-light-success p-2 text-uppercase px-3">{{ __('app.active') }}</span>
                                                                        @else
                                                                            <span class="badge rounded-pill text-secondary bg-light-secondary p-2 text-uppercase px-3">{{ __('app.inactive') }}</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        @else
                                                            <tr>
                                                                <td colspan="6">
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

                            </div>
                        </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                </div>
            </form>
        </div>
    </div>
</div>
<!-- Share Holders Modal: end -->
