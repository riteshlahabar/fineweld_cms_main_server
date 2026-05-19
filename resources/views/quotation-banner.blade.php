@extends('layouts.app')

@section('title', 'Quotation Banner')

@section('content')
<div class="page-wrapper">
    <div class="page-content">

        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Settings</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">
                                <i class="bx bx-home-alt"></i>
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Quotation Banner
                        </li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card radius-10">
            <div class="card-header">
                <h5 class="mb-0">Quotation Banner</h5>
            </div>

            <div class="card-body">

                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('settings.quotation.banner.store') }}"
                      method="POST"
                      enctype="multipart/form-data">

                    @csrf

                    <div class="row">

                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">First Page Banner</label>
                            <input type="file"
                                   name="quotation_ad_banner"
                                   class="form-control"
                                   accept="image/*">

                            @php
                                $adBanner = $banner?->quotation_ad_banner ?: 'quotation-ad.jpg';
                            @endphp

                            <div class="mt-3">
                                <img src="{{ asset('quotation_banner/'.$adBanner) }}"
                                     alt="First Page Banner"
                                     style="max-width: 100%; height: auto; border: 1px solid #ddd; padding: 5px;">
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Quotation Header Banner</label>
                            <input type="file"
                                   name="quotation_header_banner"
                                   class="form-control"
                                   accept="image/*">

                            @php
                                $headerBanner = $banner?->quotation_header_banner ?: 'quotation-header.jpg';
                            @endphp

                            <div class="mt-3">
                                <img src="{{ asset('quotation_banner/'.$headerBanner) }}"
                                     alt="Quotation Header Banner"
                                     style="max-width: 100%; height: auto; border: 1px solid #ddd; padding: 5px;">
                            </div>
                        </div>

                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            Save Banner
                        </button>

                        <a href="{{ route('dashboard') }}" class="btn btn-light px-4">
                            Close
                        </a>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>
@endsection