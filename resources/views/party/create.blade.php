@extends('layouts.app')
@section('title', $lang['party_create'])

@section('content')
<!--start page wrapper -->
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumb :langArray="[
            'party.contacts',
            $lang['party_list'],
            $lang['party_create'],
        ]"/>
        <div class="row">
            <div class="col-12 col-lg-12">
                <div class="card">
                    <div class="card-header px-4 py-3">
                        <h5 class="mb-0">{{ $lang['party_details'] }}</h5>
                    </div>
                    <div class="card-body p-4">
                        <form class="row g-3 needs-validation" id="partyForm" action="{{ route('party.store') }}" enctype="multipart/form-data">
                            {{-- CSRF Protection --}}
                            @csrf
                            @method('POST')

                            <input type="hidden" id="operation" name="operation" value="save">
                            <input type="hidden" id="base_url" value="{{ url('/') }}">

                            <div class="col-md-6">
                                <x-label for="company_name" name="Company Name" />
                                <x-input type="text" name="company_name" :required="true" value=""/>
                            </div>

                            <div class="col-md-6">
                                <x-label for="company_type" name="Company Type" />
                                <select class="form-select" name="company_type" id="company_type" required>
                                    <option value="">Select Company Type</option>
                                    <option value="proprietor">Proprietor</option>
                                    <option value="partnership">Partnership</option>
                                    <option value="private_limited">Private Limited</option>
                                    <option value="public_limited">Public Limited</option>
                                    <option value="one_person_company">One Person Company</option>
                                    <option value="limited_liability_partnership">Limited Liability Partnership</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <x-label for="vendor_type" name="Vendor Type" />
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="vendor_type" id="vendor_customer" value="customer" required>
                                        <label class="form-check-label fw-bold" for="vendor_customer">Customer</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="vendor_type" id="vendor_supplier" value="supplier" required>
                                        <label class="form-check-label fw-bold" for="vendor_supplier">Supplier</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="vendor_type" id="vendor_both" value="both" required>
                                        <label class="form-check-label fw-bold" for="vendor_both">Both</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <x-label for="company_pan" name="Company PAN" />
                                <x-input type="text" name="company_pan" :required="false" value=""/>
                            </div>

                            <div class="col-md-6">
                                <x-label for="company_gst" name="Company GST" />
                                <x-input type="text" name="company_gst" :required="false" value=""/>
                            </div>

                            <div class="col-md-6">
                                <x-label for="company_tan" name="Company TAN" />
                                <x-input type="text" name="company_tan" :required="false" value=""/>
                            </div>

                            <div class="col-md-6">
                                <x-label for="company_msme" name="Company MSME No" />
                                <x-input type="text" name="company_msme" :required="false" value=""/>
                            </div>

                            <div class="col-md-6">
                                <x-label for="date_of_incorporation" name="Date of Incorporation" />
                                <div class="input-group">
                                    <x-input type="text" additionalClasses="datepicker" name="date_of_incorporation" :required="false" value=""/>
                                    <span class="input-group-text"><i class="bx bx-calendar-alt"></i></span>
                                </div>
                            </div>

                            {{-- Contact Person Tabs - Horizontal in one row --}}
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
                                    {{-- Primary Contact --}}
                                    <div class="tab-pane fade show active" id="primaryContact" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <x-label for="primary_name" name="Name" />
                                                <x-input type="text" name="primary_name" :required="true" value=""/>
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="primary_email" name="Email Id" />
                                                <x-input type="email" name="primary_email" :required="false" value=""/>
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="primary_mobile" name="Mob No" />
                                                <x-input type="number" name="primary_mobile" :required="false" value=""/>
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="primary_whatsapp" name="What's App No" />
                                                <x-input type="number" name="primary_whatsapp" :required="false" value=""/>
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="primary_dob" name="Date of Birth" />
                                                <div class="input-group">
                                                    <x-input type="text" additionalClasses="datepicker" name="primary_dob" :required="false" value=""/>
                                                    <span class="input-group-text"><i class="bx bx-calendar-alt"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Secondary Contact --}}
                                    <div class="tab-pane fade" id="secondaryContact" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <x-label for="secondary_name" name="Name" />
                                                <x-input type="text" name="secondary_name" :required="false" value=""/>
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="secondary_email" name="Email Id" />
                                                <x-input type="email" name="secondary_email" :required="false" value=""/>
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="secondary_mobile" name="Mob No" />
                                                <x-input type="number" name="secondary_mobile" :required="false" value=""/>
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="secondary_whatsapp" name="What's App No" />
                                                <x-input type="number" name="secondary_whatsapp" :required="false" value=""/>
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="secondary_dob" name="Date of Birth" />
                                                <div class="input-group">
                                                    <x-input type="text" additionalClasses="datepicker" name="secondary_dob" :required="false" value=""/>
                                                    <span class="input-group-text"><i class="bx bx-calendar-alt"></i></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Company Address --}}
                                    <div class="tab-pane fade" id="companyAddress" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <x-label for="billing_address" name="Billing Address" />
                                                <x-textarea name="billing_address" value=""/>
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="shipping_address" name="Shipping Address" />
                                                <x-textarea name="shipping_address" value=""/>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Bank Details --}}
                                    <div class="tab-pane fade" id="bankDetails" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <x-label for="bank_name" name="Bank Name" />
                                                <x-input type="text" name="bank_name" :required="false" value=""/>
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="bank_branch" name="Bank Branch" />
                                                <x-input type="text" name="bank_branch" :required="false" value=""/>
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="bank_account_no" name="Bank A/c No" />
                                                <x-input type="text" name="bank_account_no" :required="false" value=""/>
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="ifsc_code" name="IFSC Code" />
                                                <x-input type="text" name="ifsc_code" :required="false" value=""/>
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="micr_code" name="MICR Code" />
                                                <x-input type="text" name="micr_code" :required="false" value=""/>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Company Documents --}}
                                    <div class="tab-pane fade" id="documents" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <x-label for="pan_document" name="PAN (Optional)" />
                                                <input type="file" class="form-control" name="pan_document" accept="image/*,application/pdf">
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="tan_document" name="TAN (Optional)" />
                                                <input type="file" class="form-control" name="tan_document" accept="image/*,application/pdf">
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="gst_document" name="GST (Optional)" />
                                                <input type="file" class="form-control" name="gst_document" accept="image/*,application/pdf">
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="msme_document" name="MSME (Optional)" />
                                                <input type="file" class="form-control" name="msme_document" accept="image/*,application/pdf">
                                            </div>
                                            <div class="col-md-6">
                                                <x-label for="cancelled_cheque" name="Cancelled Cheque (Optional)" />
                                                <input type="file" class="form-control" name="cancelled_cheque" accept="image/*,application/pdf">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Hidden fields for backward compatibility --}}
                            <div class="col-md-6 d-none">
                                <x-input type="text" name="first_name" value=""/>
                            </div>
                            <div class="col-md-6 d-none">
                                <x-input type="text" name="last_name" value=""/>
                            </div>
                            <div class="col-md-6 d-none">
                                <x-input type="email" name="email" value=""/>
                            </div>
                            <div class="col-md-6 d-none">
                                <x-input type="number" name="phone" value=""/>
                            </div>
                            <div class="col-md-6 d-none">
                                <x-input type="number" name="mobile" value=""/>
                            </div>
                            <div class="col-md-6 d-none">
                                <x-input type="number" name="whatsapp" value=""/>
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
    </div>
</div>
@endsection

@section('js')
<script src="{{ versionedAsset('custom/js/party/party.js') }}"></script>
@endsection
