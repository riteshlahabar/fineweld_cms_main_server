{{-- resources/views/technician/assign.blade.php --}}
@extends('layouts.app')
@section('title', 'Assign Technicians')

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
@endsection

@section('content')
<!--start page wrapper -->
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumb :langArray="[
                'Support Portal',
                'Assign Technicians',
            ]"/>

        <div class="card">
            <div class="card-header px-4 py-3 d-flex justify-content-between">
                <div>
                    <h5 class="mb-0 text-uppercase">Assign Technicians</h5>
                </div>
            </div>

        <div class="row">
            <!-- Technician Cards - Job Count Overview -->
            <div class="col-xl-3 col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1 overflow-hidden">
                                <p class="text-uppercase mb-0 fs-11 font-weight-bold text-white-50">Total Technicians</p>
                                <h3 class="fw-bolder mb-0">{{ $totalTechnicians }}</h3>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-user-circle fs-24"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1 overflow-hidden">
                                <p class="text-uppercase mb-0 fs-11 font-weight-bold text-white-50">Total Jobs</p>
                                <h3 class="fw-bolder mb-0">{{ $totalJobs }}</h3>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-task fs-24"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1 overflow-hidden">
                                <p class="text-uppercase mb-0 fs-11 font-weight-bold text-white-50">Busy (3+ Jobs)</p>
                                <h3 class="fw-bolder mb-0">{{ $busyTechnicians }}</h3>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-time-five fs-24"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1 overflow-hidden">
                                <p class="text-uppercase mb-0 fs-11 font-weight-bold text-white-50">Available</p>
                                <h3 class="fw-bolder mb-0">{{ $availableTechnicians }}</h3>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="bx bx-check-circle fs-24"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Technician List with Job Details -->
        <div class="card">
            <div class="card-header px-4 py-3">
                <h5 class="mb-0">Technician Availability</h5>
            </div>
            <div class="card-body">
                <!-- Filter Row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Filter by Region</label>
                        <select class="form-select">
                            <option>All Regions</option>
                            <option>Nagpur Central</option>
                            <option>Nagpur South</option>
                            <option>Nagpur North</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Max Jobs</label>
                        <select class="form-select">
                            <option>All</option>
                            <option value="1">1 Job</option>
                            <option value="2">2 Jobs</option>
                            <option value="3">3+ Jobs</option>
                        </select>
                    </div>
                </div>

                <!-- Technicians Table -->
<div class="row g-4" id="technicianCardsContainer">

@foreach($technicians as $tech)

@php
$capacity = 5;
$remaining = $capacity - $tech->active_jobs;
$isBusy = $tech->active_jobs >= 3;
$currentTicket = $tech->current_ticket;
@endphp

<div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">

<div class="card h-100 technician-card {{ $isBusy ? 'busy' : 'available' }}">

<div class="card-header bg-gradient-primary text-white text-center py-3">

<img src="https://ui-avatars.com/api/?name={{ urlencode($tech->employee_name) }}&size=60"
class="rounded-circle border border-4 border-white mb-2">

<h6 class="mb-1 fw-bold">{{ $tech->employee_name }}</h6>

<small class="text-white-50">ID: {{ $tech->employee_id }}</small>

</div>


<div class="card-body p-0">

@if($currentTicket)

<div class="p-3 border-bottom">

<div class="fw-bold text-primary mb-1">
Current Job ({{ $tech->active_jobs }} Active)
</div>

<div class="row g-2 mb-2">

<div class="col-8">
<small class="text-muted d-block">Company</small>
<div class="fw-semibold">{{ $currentTicket->company_name ?? '-' }}</div>
</div>

<div class="col-4">
<small class="text-muted d-block">Ticket</small>
<div class="text-danger fw-bold">
{{ $currentTicket->ticket_no }}
</div>
</div>

</div>

</div>

@else

<div class="p-4 text-center border-bottom bg-success-subtle">

<i class="bx bx-check-circle text-success fs-2 mb-2"></i>

<div class="fw-bold text-success mb-1">
No Active Jobs
</div>

<div class="text-muted">
Ready for assignment
</div>

</div>

@endif


<div class="p-3">

<div class="fw-semibold text-muted mb-2 small">

Remaining Capacity:

<span class="{{ $remaining == 0 ? 'text-danger' : 'text-success' }}">
{{ $remaining }}/5
</span>

</div>

</div>

</div>


<div class="card-footer bg-light text-center py-2">

<span class="badge {{ $isBusy ? 'bg-danger' : 'bg-success' }} fs-6 px-3 py-2">

{{ $isBusy ? 'Busy' : 'Available' }}

</span>

</div>

</div>

</div>

@endforeach

</div>

{{-- Add this CSS --}}
<style>
.bg-gradient-primary { background: linear-gradient(135deg, #4f46e5, #7c3aed); }
.bg-gradient-success { background: linear-gradient(135deg, #10b981, #34d399); }
.bg-gradient-warning { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
.bg-gradient-info { background: linear-gradient(135deg, #0ea5e9, #28c4ea); }
.technician-card { transition: all 0.3s ease; border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.technician-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
</style>

            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ versionedAsset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ versionedAsset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
@endsection
