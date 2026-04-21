{{-- resources/views/support-portal/ticket/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Raise a Ticket')

@section('content')
<!--start page wrapper -->
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumb :langArray="[
                'Support Portal',
                'All Tickets',
            ]"/>

        <div class="card">
            <div class="card-header px-4 py-3 d-flex justify-content-between">
                <div>
                    <h5 class="mb-0 text-uppercase">Raise a Ticket</h5>
                </div>
                <a href="{{ route('tickets.list') }}" class="btn btn-light px-5">Back to List</a>
            </div>
            

        <div class="card">
            <div class="card-body p-4">
                <form class="row g-3 needs-validation" id="ticketForm" action="{{ route('tickets.store') }}" enctype="multipart/form-data" novalidate>
                    @csrf
                    @method('POST')
                    
                    <!-- Customer Details (Left Column) -->
                    <div class="col-md-6">
                        <x-label for="company_name" name="Company Name *" />
                        <input type="text" class="form-control" id="company_name" name="company_name" required>
                    </div>
                    
                    <div class="col-md-6">
                        <x-label for="contact_person" name="Contact Person *" />
                        <input type="text" class="form-control" id="contact_person" name="contact_person" required>
                    </div>
                    
                    <div class="col-md-6">
                        <x-label for="mobile_no" name="Mobile No. *" />
                        <input type="tel" class="form-control" id="mobile_no" name="mobile_no" required>
                    </div>
                    
                    <div class="col-md-6">
                        <x-label for="email" name="Email" />
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    
                    <div class="col-12">
                        <x-label for="address" name="Address *" />
                        <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                    </div>

                    <!-- Technical Details (Right Column) -->
                    <div class="col-md-6">
                        <x-label for="model" name="Machine Model *" />
                        <input type="text" class="form-control" id="model" name="model" required>
                    </div>
                    
                    <div class="col-md-6">
                        <x-label for="service_type" name="Service Type *" />
                        <select class="form-select" id="service_type" name="service_type" required>
                            <option value="">Select Service Type</option>
                            <option value="repair">Repair</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="installation">Installation</option>
                            <option value="inspection">Inspection</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <x-label for="priority" name="Priority *" />
                        <select class="form-select" id="priority" name="priority" required>
                            <option value="">Select Priority</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <!-- Problem Description -->
                    <div class="col-12">
                        <x-label for="problem" name="Problem Description *" />
                        <textarea class="form-control" id="problem" name="problem" rows="5" 
                            placeholder="Describe the issue in detail..." required></textarea>
                    </div>

                    <!-- Image Upload -->
                    <div class="col-md-6">
                        <x-label for="image" name="Machine Image" />
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="form-text">Upload machine image or photo of the problem (optional)</div>
                    </div>

                    <!-- Buttons -->
                    <div class="col-md-12">
                        <div class="d-md-flex d-grid align-items-center gap-3">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bx bx-plus-circle me-1"></i> Raise Ticket
                            </button>
                            <a href="{{ route('tickets.list') }}" class="btn btn-light px-4">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
