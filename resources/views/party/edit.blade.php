@extends('layouts.app')
@section('title', $lang['party_update'])

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumb :langArray="[
            'party.contacts',
            $lang['party_list'],
            $lang['party_update'],
        ]"/>

        <div class="row">
            <div class="col-12 col-lg-12">
                <div class="card">
                    <div class="card-header px-4 py-3">
                        <h5 class="mb-0">{{ $lang['party_details'] }}</h5>
                    </div>

                    <div class="card-body p-4">
                        <form class="row g-3 needs-validation"
                              id="partyForm"
                              action="{{ route('party.update') }}"
                              method="POST"
                              enctype="multipart/form-data">

                            @csrf
                            @method('PUT')

                            {{-- HIDDEN FIELDS --}}
                            <input type="hidden" name="party_id" value="{{ $party->id }}">
                            <input type="hidden" name="party_type" value="{{ $party->party_type }}">
                            <input type="hidden" name="operation" value="update">
                            <input type="hidden" id="base_url" value="{{ url('/') }}">

                            {{-- ================= COMPANY DETAILS ================= --}}
                            <div class="col-md-6">
                                <x-label for="company_name" name="Company Name" />
                                <x-input type="text"
                                         name="company_name"
                                         :required="true"
                                         value="{{ old('company_name', $party->company_name) }}" />
                            </div>

                            <div class="col-md-6">
                                <x-label for="company_type" name="Company Type" />
                                <select class="form-select" name="company_type" required>
                                    <option value="">Select Company Type</option>
                                    @foreach([
                                        'proprietor'=>'Proprietor',
                                        'partnership'=>'Partnership',
                                        'private_limited'=>'Private Limited',
                                        'public_limited'=>'Public Limited',
                                        'one_person_company'=>'One Person Company',
                                        'limited_liability_partnership'=>'Limited Liability Partnership'
                                    ] as $key=>$label)
                                        <option value="{{ $key }}"
                                            {{ old('company_type', $party->company_type) === $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <x-label for="vendor_type" name="Vendor Type" />
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    @foreach(['customer'=>'Customer','supplier'=>'Supplier','both'=>'Both'] as $key=>$label)
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="radio"
                                                   name="vendor_type"
                                                   id="vendor_{{ $key }}"
                                                   value="{{ $key }}"
                                                   {{ old('vendor_type', $party->vendor_type) === $key ? 'checked' : '' }}
                                                   required>
                                            <label class="form-check-label fw-bold" for="vendor_{{ $key }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-md-6">
                                <x-label for="company_pan" name="Company PAN" />
                                <x-input type="text" name="company_pan" value="{{ old('company_pan', $party->company_pan) }}" />
                            </div>

                            <div class="col-md-6">
                                <x-label for="company_gst" name="Company GST" />
                                <x-input type="text" name="company_gst" value="{{ old('company_gst', $party->company_gst) }}" />
                            </div>

                            <div class="col-md-6">
                                <x-label for="company_tan" name="Company TAN" />
                                <x-input type="text" name="company_tan" value="{{ old('company_tan', $party->company_tan) }}" />
                            </div>

                            <div class="col-md-6">
                                <x-label for="company_msme" name="Company MSME No" />
                                <x-input type="text" name="company_msme" value="{{ old('company_msme', $party->company_msme) }}" />
                            </div>

                            <div class="col-md-6">
                                <x-label for="date_of_incorporation" name="Date of Incorporation" />
                                <div class="input-group">
                                    <x-input type="date" 
                                             additionalClasses="datepicker form-control" 
                                             name="date_of_incorporation" 
                                             value="{{ old('date_of_incorporation', $party->date_of_incorporation) }}" />
                                    <span class="input-group-text"><i class="bx bx-calendar-alt"></i></span>
                                </div>
                            </div>

                            {{-- ================= CONTACT TABS ================= --}}
                            <div class="col-12">
                                <ul class="nav nav-tabs nav-success mb-3" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#primaryContact" role="tab">
                                            <i class="bx bx-user-check me-1"></i>Primary Contact Person
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" data-bs-toggle="tab" href="#secondaryContact" role="tab">
                                            <i class="bx bx-user-plus me-1"></i>Secondary Contact Person
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" data-bs-toggle="tab" href="#companyAddress" role="tab">
                                            <div class="d-flex align-items-center">
                                                <div class="tab-icon"><i class='bx bx-map font-18 me-1'></i></div>
                                                <div class="tab-title">Company Address</div>
                                            </div>
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" data-bs-toggle="tab" href="#bankDetails" role="tab">
                                            <div class="d-flex align-items-center">
                                                <div class="tab-icon"><i class='bx bxs-bank-account font-18 me-1'></i></div>
                                                <div class="tab-title">Bank Details</div>
                                            </div>
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link" data-bs-toggle="tab" href="#documents" role="tab">
                                            <div class="d-flex align-items-center">
                                                <div class="tab-icon"><i class='bx bx-file font-18 me-1'></i></div>
                                                <div class="tab-title">Company Documents</div>
                                            </div>
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content py-3">
                                    {{-- PRIMARY CONTACT --}}
                                    <div class="tab-pane fade show active" id="primaryContact" role="tabpanel">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <x-label for="primary_name" name="Name" />
                                                <x-input type="text" name="primary_name" value="{{ old('primary_name', $party->primary_name) }}" />
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="primary_email" name="Email Id" />
                                                <x-input type="email" name="primary_email" value="{{ old('primary_email', $party->primary_email) }}" />
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="primary_mobile" name="Mob No" />
                                                <x-input type="tel" name="primary_mobile" value="{{ old('primary_mobile', $party->primary_mobile) }}" />
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="primary_whatsapp" name="What's App No" />
                                                <x-input type="tel" name="primary_whatsapp" value="{{ old('primary_whatsapp', $party->primary_whatsapp) }}" />
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="primary_dob" name="Date of Birth" />
                                                <div class="input-group">
                                                    <x-input type="date" 
                                                             additionalClasses="datepicker form-control" 
                                                             name="primary_dob" 
                                                             value="{{ old('primary_dob', $party->primary_dob) }}" />
                                                    <span class="input-group-text"><i class="bx bx-calendar-alt"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- SECONDARY CONTACT --}}
                                    <div class="tab-pane fade" id="secondaryContact" role="tabpanel">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <x-label for="secondary_name" name="Name" />
                                                <x-input type="text" name="secondary_name" value="{{ old('secondary_name', $party->secondary_name) }}" />
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="secondary_email" name="Email Id" />
                                                <x-input type="email" name="secondary_email" value="{{ old('secondary_email', $party->secondary_email) }}" />
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="secondary_mobile" name="Mob No" />
                                                <x-input type="tel" name="secondary_mobile" value="{{ old('secondary_mobile', $party->secondary_mobile) }}" />
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="secondary_whatsapp" name="What's App No" />
                                                <x-input type="tel" name="secondary_whatsapp" value="{{ old('secondary_whatsapp', $party->secondary_whatsapp) }}" />
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="secondary_dob" name="Date of Birth" />
                                                <div class="input-group">
                                                    <x-input type="date" 
                                                             additionalClasses="datepicker form-control" 
                                                             name="secondary_dob" 
                                                             value="{{ old('secondary_dob', $party->secondary_dob) }}" />
                                                    <span class="input-group-text"><i class="bx bx-calendar-alt"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- COMPANY ADDRESS --}}
<div class="tab-pane fade" id="companyAddress" role="tabpanel">
    <div class="row g-3">
        <div class="col-md-6">
            <x-label for="billing_address" name="Billing Address" />
            <x-textarea 
                name="billing_address" 
                :value="old('billing_address', $party->billing_address ?? '')"
                rows="4" />
        </div>
        <div class="col-md-6">
            <x-label for="shipping_address" name="Shipping Address" />
            <x-textarea 
                name="shipping_address"
                :value="old('shipping_address', $party->shipping_address ?? '')"
                rows="4" />
        </div>
    </div>
</div>


                                    {{-- BANK DETAILS --}}
                                    <div class="tab-pane fade" id="bankDetails" role="tabpanel">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <x-label for="bank_name" name="Bank Name" />
                                                <x-input type="text" name="bank_name" value="{{ old('bank_name', $party->bank_name) }}" />
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="bank_branch" name="Bank Branch" />
                                                <x-input type="text" name="bank_branch" value="{{ old('bank_branch', $party->bank_branch) }}" />
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="bank_account_no" name="Bank A/c No" />
                                                <x-input type="text" name="bank_account_no" value="{{ old('bank_account_no', $party->bank_account_no) }}" />
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="ifsc_code" name="IFSC Code" />
                                                <x-input type="text" name="ifsc_code" value="{{ old('ifsc_code', $party->ifsc_code) }}" />
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="micr_code" name="MICR Code" />
                                                <x-input type="text" name="micr_code" value="{{ old('micr_code', $party->micr_code) }}" />
                                            </div>
                                        </div>
                                    </div>

                                    {{-- DOCUMENTS WITH IMAGE PREVIEW --}}
                                    <div class="tab-pane fade" id="documents" role="tabpanel">
                                        <div class="row g-3">
                                            @php
                                                $documentFields = [
                                                    'pan_document' => 'PAN',
                                                    'tan_document' => 'TAN', 
                                                    'gst_document' => 'GST',
                                                    'msme_document' => 'MSME',
                                                    'cancelled_cheque' => 'Cancelled Cheque'
                                                ];
                                            @endphp
                                            
                                            @foreach($documentFields as $key => $label)
                                                <div class="col-md-6">
                                                    <x-label for="{{ $key }}" name="{{ $label }} Document" />
                                                    <input type="file" class="form-control" name="{{ $key }}" accept="image/*,application/pdf">
                                                    
                                                    @if($party->$key)
                                                        <div class="mt-2">
                                                            @if(str_contains($party->$key, '.pdf'))
                                                                <a href="{{ asset($party->$key) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                                                    <i class="bx bx-file-pdf me-1"></i>View {{ $label }} PDF
                                                                </a>
                                                            @else
                                                                <div class="document-preview">
                                                                    <a href="{{ asset($party->$key) }}" target="_blank">
                                                                        <img src="{{ asset($party->$key) }}" 
                                                                             alt="{{ $label }} Document"
                                                                             class="img-thumbnail document-thumb"
                                                                             style="max-width: 100px; max-height: 100px; cursor: pointer;"
                                                                             data-bs-toggle="modal" 
                                                                             data-bs-target="#documentModal"
                                                                             onclick="openDocumentModal('{{ asset($party->$key) }}', '{{ $label }}')">
                                                                    </a>
                                                                    <small class="text-muted d-block mt-1">{{ $label }} Document</small>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <small class="text-muted">No document uploaded</small>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ACTION BUTTONS --}}
                            <div class="col-md-12">
                                <div class="d-md-flex d-grid align-items-center gap-3">
                                    <x-button type="submit" class="primary px-4" text="{{ __('app.update') }}" />
                                    <x-anchor-tag href="{{ route('dashboard') }}" text="{{ __('app.close') }}" class="btn btn-light px-4" />
                                </div>
                            </div>

                            {{-- HIDDEN FIELDS FOR BACKWARD COMPATIBILITY --}}
                            <div class="col-md-6 d-none">
                                <x-input type="text" name="first_name" value="{{ old('first_name') }}" />
                            </div>
                            <div class="col-md-6 d-none">
                                <x-input type="text" name="last_name" value="{{ old('last_name') }}" />
                            </div>
                            <div class="col-md-6 d-none">
                                <x-input type="email" name="email" value="{{ old('email') }}" />
                            </div>
                            <div class="col-md-6 d-none">
                                <x-input type="number" name="phone" value="{{ old('phone') }}" />
                            </div>
                            <div class="col-md-6 d-none">
                                <x-input type="number" name="mobile" value="{{ old('mobile') }}" />
                            </div>
                            <div class="col-md-6 d-none">
                                <x-input type="number" name="whatsapp" value="{{ old('whatsapp') }}" />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DOCUMENT MODAL --}}
<div class="modal fade" id="documentModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentModalLabel">Document Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img id="documentModalImage" src="" alt="Document" class="img-fluid" style="max-height: 70vh;">
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ versionedAsset('custom/js/party/party.js') }}"></script>
<script src="{{ versionedAsset('custom/js/party/party-edit.js') }}"></script>
<script>
    var _opening_balance_type = "{{ $opening_balance_type ?? 'to_pay' }}";

    // Document Modal Function
    function openDocumentModal(imageUrl, documentName) {
        document.getElementById('documentModalImage').src = imageUrl;
        document.getElementById('documentModalLabel').textContent = documentName + ' Document';
    }
</script>
@endsection
