@extends('layouts.app')
@section('title', 'All Tickets')

@section('css')
<link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
<style>
    .ticket-card {
        border-left: 4px solid #dee2e6;
        transition: all 0.3s ease;
        margin-bottom: 1rem;
    }

    .ticket-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }

    .ticket-card.high {
        border-left-color: #dc3545 !important;
    }

    .ticket-card.medium {
        border-left-color: #ffc107 !important;
    }

    .ticket-card.low {
        border-left-color: #28a745 !important;
    }

    .ticket-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="page-content">

        <x-breadcrumb :langArray="['Support Portal','All Tickets']" />


        <div class="card">
            <div class="card-header px-4 py-3 d-flex justify-content-between">
                <h5 class="mb-0 text-uppercase">All Tickets</h5>
                @can('ticket.create')
                <x-anchor-tag href="{{ route('tickets.create') }}" text="Raise a Ticket" class="btn btn-primary px-5" />
                @endcan
            </div>

            <div class="card-body">

                {{-- FILTERS (UNCHANGED) --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-2">
                        <x-label for="status_filter" name="Status" />
                        <select class="form-select" id="status_filter">
<option value="">All Status</option>
@foreach($statuses as $status)
<option value="{{ $status->slug }}">
{{ $status->name }}
</option>
@endforeach
</select>
                    </div>

                    <div class="col-md-2">
                        <x-label for="priority_filter" name="Priority" />
                        <select class="form-select" id="priority_filter">
<option value="">All Priority</option>
@foreach($priorities as $priority)
<option value="{{ $priority->slug }}">
{{ $priority->name }}
</option>
@endforeach
</select>
                    </div>

                    <div class="col-md-2">
                        <x-label for="technician_filter" name="Technician" />
                       <select class="form-select" id="technician_filter">
<option value="">All Technicians</option>
@foreach($engineers as $engineer)
<option value="{{ $engineer->employee_id }}">
{{ $engineer->employee_name }}
</option>
@endforeach
</select>
                    </div>

                    <div class="col-md-2">
                        <x-label for="date_range" name="Date Range" />
                        <input type="date" class="form-control" id="date_range">
                    </div>

                    <div class="col-md-2">
                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-outline-primary">Filter</button>
                            <button type="button" class="btn btn-outline-secondary">Reset</button>
                        </div>
                    </div>
                </div>

                <div class="row g-4">

                    <input type="hidden" id="csrf_token" value="{{ csrf_token() }}">
                    @foreach($tickets as $ticket)

                    @php
                    $product = $ticket->product;
                    $party = $ticket->party;

                    $years = $months = $days = 0;

                    if($product && $product->warranty_end){
                    $end = \Carbon\Carbon::parse($product->warranty_end);
                    if($end->isFuture()){
                    $diff = now()->diff($end);
                    $years = $diff->y;
                    $months = $diff->m;
                    $days = $diff->d;
                    }
                    }
                    @endphp

                    <div class="col-xl-4 col-lg-6 col-md-12">
                        <div class="card ticket-card {{ strtolower($ticket->priority->slug ?? '') }} h-100"
     data-status="{{ $ticket->status->slug }}"
     data-priority="{{ $ticket->priority->slug }}"
     data-technician="{{ $ticket->engineer_id }}"
     data-date="{{ $ticket->created_at->format('Y-m-d') }}">
                            <div class="card-body">

                                {{-- HEADER --}}
                                <div class="ticket-header p-3 rounded mb-3">
                                    <div class="d-flex justify-content-between align-items-start">

                                        <div>
                                            <h6 class="mb-1 fw-bold">
                                                <span class="badge bg-primary me-2">
                                                    {{ $ticket->ticket_no }}
                                                </span>
                                            </h6>
                                            <div class="small text-muted">
                                                {{ $ticket->
                                                created_at->format('d-m-Y h:i A') }}
                                            </div>
                                        </div>

                                        <div>
                                            <span class="badge bg-{{ $ticket->priority->ui_class ?? 'secondary' }}">
                                                {{ $ticket->priority->name ?? '' }}
                                            </span>

                                            <span
                                                class="badge bg-{{ $ticket->status->ui_class ?? 'info' }} status-badge"
                                                data-ticket-id="{{ $ticket->id }}"
                                                data-current-status="{{ $ticket->status->slug }}"
                                                style="cursor:pointer;">
                                                {{ $ticket->status->name ?? '' }}
                                            </span>
                                            @if($ticket->status->slug == 'closed')

@php
$duration = $ticket->created_at->diff($ticket->updated_at);
@endphp

<span class="text-success fw-bold small">
Resolved in {{ $duration->h }}h {{ $duration->i }}m
</span>

@else

<span class="text-danger fw-bold small sla-timer"
      data-created="{{ $ticket->created_at->toIso8601String() }}">
</span>

@endif
                                             {{-- Engineer Name --}}
   @if($ticket->engineer)
    <div class="small mt-1 text-primary fw-semibold">
        <i class="bx bx-user me-1"></i>
        {{ $ticket->engineer->employee_name }}
    </div>
@else
    <div class="small mt-1 text-muted">
        Not Assigned
    </div>
@endif

                                        </div>

                                    </div>
                                </div>

                                {{-- CUSTOMER INFO (2 COLUMN) --}}
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">Company</small>
                                        <div>{{ $party->company_name ?? '' }}</div>
                                    </div>

                                    <div class="col-6">
                                        <small class="text-muted">Contact Person</small>
                                        <div>{{ $party->primary_name ?? '' }}</div>
                                    </div>

                                    <div class="col-6">
                                        <small class="text-muted">Contact Number</small>
                                        <div>{{ $party->primary_mobile ?? '' }}</div>
                                    </div>

                                    <div class="col-6">
                                        <small class="text-muted">Address</small>
                                        <div>{{ $party->billing_address ?? '' }}</div>
                                    </div>
                                </div>

                                {{-- PRODUCT INFO (2 COLUMN) --}}
                                @if($product)
                                <div class="row mb-3 border-top pt-2">

                                    <div class="col-6">
                                        <small class="text-muted">Product Name</small>
                                        <div>{{ $product->product_name }}</div>
                                    </div>

                                    <div class="col-6">
                                        <small class="text-muted">Serial No.</small>
                                        <div>{{ $product->serial_number }}</div>
                                    </div>

                                    <div class="col-6">
                                        <small class="text-muted">PO No.</small>
                                        <div>{{ $product->purchase_order_no }}</div>
                                    </div>

                                    <div class="col-6">
                                        <small class="text-muted">Purchase Date</small>
                                        <div>{{ $product->purchase_order_date ?
                                            \Carbon\Carbon::parse($product->purchase_order_date)->format('d-m-Y') : ''
                                            }}
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <small class="text-muted">Installation</small>
                                        <div>{{ $product->installation_date ?
                                            \Carbon\Carbon::parse($product->installation_date)->format('d-m-Y') : '' }}
                                        </div>
                                    </div>

                                    <div class="col-6">
                                        <small class="text-muted">Warranty</small>
                                        <div>
                                            @if($years || $months || $days)
                                            {{ $years }}y {{ $months }}m {{ $days }}d
                                            @else
                                            <span class="text-danger">No warranty</span>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                                @endif

                                {{-- PROBLEM --}}
                                <div class="mb-2">
                                    <small class="text-muted">Problem</small>
                                    <div class="bg-light p-2 rounded small">
                                        {{ $ticket->problem }}
                                    </div>
                                </div>

                                @if($ticket->problem_description)
                                <div class="mb-3">
                                    <small class="text-muted">Description</small>
                                    <div class="bg-light p-2 rounded small">
                                        {{ $ticket->problem_description }}
                                    </div>
                                </div>
                                @endif

                                {{-- IMAGES --}}
                                @if($ticket->images && $ticket->images->count())
                                <div class="mb-3 d-flex flex-wrap gap-2">
                                    @foreach($ticket->images as $img)
                                    <img src="{{ asset($img->image_path) }}" width="45" height="45"
                                        class="rounded border" style="object-fit:cover;">
                                    @endforeach
                                </div>
                                @endif

                                {{-- STATUS DROPDOWN --}}
                                <div class="mb-3">
                                    <small class="text-muted">Change Status</small>
                                    <select class="form-select form-select-sm status-dropdown"
                                        data-ticket-id="{{ $ticket->id }}">
                                        <option value="">Select Status</option>
                                        @foreach($statuses as $status)
                                        <option value="{{ $status->slug }}">
                                            {{ $status->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- VIEW VISITS BUTTON --}}
                                <div class="mb-3">
                                    <a href="{{ route('tickets.visits', $ticket->id) }}"
                                        class="btn btn-sm btn-info w-100">
                                        View Visits
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

  <!-- Schedule Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">

            <div class="modal-header">
                <h6 class="modal-title" id="modalTitle">Assign Ticket</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="modal_ticket_id">

                <div class="mb-2" id="engineerDropdownWrapper">
                    <label class="form-label">Engineer</label>
                    <select class="form-control engineer-dropdown-modal">
                        <option value="">Select Engineer</option>
                    </select>
                </div>

                <div class="mb-2 d-none" id="engineerNameWrapper">
                    <label class="form-label">Engineer</label>
                    <input type="text"
                           id="modal_engineer_name"
                           class="form-control"
                           readonly>
                </div>

                <div class="mb-2">
                    <label>Date</label>
                    <input type="date" id="schedule_date" class="form-control">
                </div>

                <div class="mb-2">
                    <label>Time</label>
                    <input type="time" id="schedule_time" class="form-control">
                </div>
            </div>

            <div class="modal-footer p-2">
                <button type="button"
                        class="btn btn-secondary btn-sm"
                        data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button"
                        id="modalActionBtn"
                        class="btn btn-primary btn-sm">
                    Assign
                </button>
            </div>

        </div>
    </div>
</div>
    @endsection

    @section('js')
    <script src="{{ versionedAsset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ versionedAsset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ versionedAsset('custom/js/common/common.js') }}"></script>
    <script src="{{ versionedAsset('custom/js/support-portal/tickets/ticket-list.js') }}"></script>
    @endsection