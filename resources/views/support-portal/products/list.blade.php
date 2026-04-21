@extends('layouts.app')
@section('title', $lang['products_list'] ?? 'Products List')

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumb :langArray="[
            'Support Portal',
            'Products List',
        ]" />

        <div class="card">
            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 text-uppercase">{{ $lang['products_list'] ?? 'Products List' }}</h5>
                </div>
                <div class="d-flex gap-2">
                    @can('product.create')
                    <x-anchor-tag href="{{ route('products.create') }}" text="Register Product"
                        class="btn btn-primary px-5" />
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <form id="datatableForm" action="{{ route('products.delete') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <input type="hidden" id="base_url" value="{{ url('/') }}">
                    <div class="datatable-wrapper">

    <div class="table-responsive">
        <table class="table table-striped table-bordered border w-100" id="datatable">
            <thead>
                <tr>
                    <th class="d-none"></th>
                    <th><input class="form-check-input row-select" type="checkbox"></th>
                    <th>Company Name</th>
                    <th>Contact Person</th>
                    <th>Mobile No.</th>
                    <th>Purchase Order No.</th>
                    <th>Purchase Order Date</th>
                    <th>Tax Invoice No.</th>
                    <th>Tax Invoice Date</th>
                    <th>Product Name</th>
                    <th>Product Image</th>
                    <th>Model Number</th>
                    <th>Serial Number</th>
                    <th>Installation Date</th>
                    <th>Warranty Start</th>
                    <th>Warranty End</th>
                    <th>Warranty Remaining (Days)</th>
                    <th>Installed By</th>
                    <th>Remarks</th>
                    <th>{{ __('app.action') }}</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="imagePreviewModal">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Product Image</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center">
                <img id="previewImage" style="max-width:100%;border-radius:6px;">
            </div>

        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ versionedAsset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ versionedAsset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ versionedAsset('custom/js/common/common.js') }}"></script>
<script src="{{ versionedAsset('custom/js/support-portal/product-list.js') }}"></script>
@endsection