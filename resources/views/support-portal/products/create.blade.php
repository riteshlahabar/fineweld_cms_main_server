@extends('layouts.app')
@section('title', 'Register Product')

@section('content')
<div class="page-wrapper">
    <div class="page-content">

        <x-breadcrumb :langArray="[
            'Support Portal',
            'Products',
            'Register Product',
        ]"/>

        <div class="row">
            <div class="col-12 col-lg-12">
                <div class="card">

                    <div class="card-header px-4 py-3">
                        <h5 class="mb-0">Register Product</h5>
                    </div>

                    <div class="card-body p-4">
                        <form class="row g-3 needs-validation"
      method="POST"
      action="{{ route('products.store') }}"
      enctype="multipart/form-data">

                            @csrf

                            <input type="hidden" id="base_url" value="{{ url('/') }}">

                            {{-- ================= PARTY ================= --}}
                            <div class="col-md-6">
                                <x-label for="party_id" name="Customer / Party" />
                                <select name="party_id" class="form-select" required>
                                    <option value="">Select Party</option>
                                    @foreach($parties as $party)
                                        <option value="{{ $party->id }}">
                                            {{ $party->company_name }} ({{ $party->primary_mobile }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- ================= PURCHASE DETAILS ================= --}}
                            <div class="col-md-6">
                                <x-label for="purchase_order_no" name="Purchase Order No." />
                                <x-input type="text" name="purchase_order_no" />
                            </div>

                            <div class="col-md-6">
                                <x-label for="purchase_order_date" name="Purchase Order Date" />
                                <div class="input-group">
                                    <x-input type="text" name="purchase_order_date" additionalClasses="datepicker"/>
                                    <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <x-label for="tax_invoice_no" name="Tax Invoice No." />
                                <x-input type="text" name="tax_invoice_no" />
                            </div>

                            <div class="col-md-6">
                                <x-label for="tax_invoice_date" name="Tax Invoice Date" />
                                <div class="input-group">
                                    <x-input type="text" name="tax_invoice_date" additionalClasses="datepicker"/>
                                    <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                </div>
                            </div>

                            {{-- ================= PRODUCT ================= --}}
                            <div class="col-md-6">
                                <x-label for="product_name" name="Product Name" />
                                <x-input type="text" name="product_name" required />
                            </div>

                            <div class="col-md-6">
                                <x-label for="model_number" name="Model Number" />
                                <x-input type="text" name="model_number" />
                            </div>

                            <div class="col-md-6">
                                <x-label for="serial_number" name="Serial Number" />
                                <x-input type="text" name="serial_number" required />
                            </div>
                            
                            {{-- ================= PRODUCT IMAGE ================= --}}
<div class="col-md-6">
    <x-label for="product_image" name="Product Image" />
    <input type="file"
           name="product_image"
           class="form-control"
           accept="image/*">
    <small class="text-muted">JPG, PNG, WEBP (Max 2MB)</small>
</div>

                            {{-- ================= INSTALLATION & WARRANTY ================= --}}
                            <div class="col-md-6">
                                <x-label for="installation_date" name="Installation Date" />
                                <div class="input-group">
                                    <x-input type="text" name="installation_date" additionalClasses="datepicker"/>
                                    <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <x-label for="warranty_start" name="Warranty Start Date" />
                                <div class="input-group">
                                    <x-input type="text" name="warranty_start" additionalClasses="datepicker"/>
                                    <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <x-label for="warranty_end" name="Warranty End Date" />
                                <div class="input-group">
                                    <x-input type="text" name="warranty_end" additionalClasses="datepicker"/>
                                    <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <x-label for="installed_by" name="Installed By" />
                                <x-input type="text" name="installed_by" />
                            </div>

                            <div class="col-md-12">
                                <x-label for="remarks" name="Remarks" />
                                <x-textarea name="remarks"/>
                            </div>

                            {{-- ================= ACTIONS ================= --}}
                            <div class="col-md-12">
                                <div class="d-md-flex d-grid align-items-center gap-3">
                                    <x-button type="submit" class="primary px-4" text="Register Product"/>
                                    <x-anchor-tag href="{{ route('products.list') }}"
                                                  text="{{ __('app.close') }}"
                                                  class="btn btn-light px-4"/>
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


