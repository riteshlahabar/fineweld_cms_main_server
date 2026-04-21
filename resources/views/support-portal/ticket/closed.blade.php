{{-- resources/views/support-portal/ticket/status.blade.php --}}
@extends('layouts.app')
@section('title', 'Ticket Status')

@section('css')
<style>
.ticket-tile {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-top: 4px solid #10b981;
    transition: all 0.3s ease;
    cursor: pointer;
    overflow: hidden;
}
.ticket-tile:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}
.tile-avatar {
    width: 60px;
    height: 60px;
    object-fit: cover;
}
.completed-ticket-card {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-top: 4px solid #10b981;
}
.part-item {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.3s ease;
}
.part-item:hover {
    border-color: #10b981;
    box-shadow: 0 4px 12px rgba(16,185,129,0.15);
}
.part-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 6px;
}
.ticket-image {
    max-height: 200px;
    object-fit: cover;
    border-radius: 12px;
}
.status-timeline {
    position: relative;
    max-width: 800px;
    margin: 0 auto;
}
.status-timeline::before {
    content: '';
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #10b981, #34d399);
    transform: translateX(-50%);
}
.timeline-item {
    position: relative;
    margin: 2rem 0;
}
.timeline-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #10b981;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    z-index: 1;
}
.modal-xl-custom {
    max-width: 1100px;
}
</style>
@endsection

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">
                <h5>Completed Tickets</h5>
            </div>
            <div class="ps-3 mt-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('tickets.list') }}"><i class="bx bx-support"></i> Support Portal</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Completed Tickets</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Compact Tiles Grid -->
        <div class="row g-4">

@forelse($tickets as $ticket)

<div class="col-xl-3 col-lg-4 col-md-6">
    <div class="card h-100 ticket-tile"
         data-bs-toggle="modal"
         data-bs-target="#ticketModal{{ $ticket->id }}">

        <div class="card-body p-4 text-center position-relative">

            <div class="position-absolute top-0 end-0 p-2">
                <span class="badge bg-success rounded-pill">
                    {{ $ticket->status->name ?? 'Closed' }}
                </span>
            </div>

            <div class="mb-3">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($ticket->problem) }}&size=60&background=10b981&color=fff"
                     class="rounded-circle mx-auto mb-2 tile-avatar shadow-sm">
            </div>

            <h6 class="fw-bold mb-2 text-truncate">
                {{ $ticket->ticket_no }}
            </h6>

            <p class="text-muted small mb-2">
                {{ $ticket->problem }}
            </p>

            <div class="fw-bold text-success fs-5 mb-2">
                ₹ {{ $ticket->total_amount ?? '0' }}
            </div>

            <div class="d-flex justify-content-between small text-muted">
                <span>{{ $ticket->engineer->employee_name ?? 'Engineer' }}</span>
                <span>{{ $ticket->created_at->format('d M') }}</span>
            </div>

        </div>
    </div>
</div>

@empty
<div class="col-12 text-center text-muted py-5">
    No Closed Tickets Found
</div>
@endforelse

</div>

<!-- Modal 1: TKT-001 Full Details -->
@foreach($tickets as $ticket)

<div class="modal fade" id="ticketModal{{ $ticket->id }}" tabindex="-1">
<div class="modal-dialog modal-xl-custom modal-dialog-centered modal-dialog-scrollable">

<div class="modal-content completed-ticket-card">

<div class="modal-header bg-success text-white">
<h5 class="mb-0">{{ $ticket->ticket_no }} - {{ $ticket->problem }}</h5>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body p-4">

<div class="row mb-3">

<div class="col-md-6">
<b>Customer</b><br>
{{ $ticket->party->company_name ?? '' }} <br>
{{ $ticket->party->primary_mobile ?? '' }}
</div>

<div class="col-md-6">
<b>Engineer</b><br>
{{ $ticket->engineer->employee_name ?? 'Not Assigned' }}
</div>

</div>

<div class="mb-3">
<b>Problem</b>
<div class="bg-light p-2 rounded">
{{ $ticket->problem }}
</div>
</div>

{{-- service timeline --}}

<h6 class="fw-bold text-muted mt-4 mb-3">Service Timeline</h6>

<div class="status-timeline">

@foreach($ticket->timelines as $timeline)

<div class="row timeline-item">

<div class="col-md-6">
<div class="text-end mb-3">
<div class="timeline-icon">
{{ $loop->iteration }}
</div>
</div>
</div>

<div class="col-md-6">
<div class="p-3 bg-light rounded ms-4">

<div class="fw-bold text-primary mb-1">
{{ $timeline->title }}
</div>

@if($timeline->description)
<div class="small text-muted">
{{ $timeline->description }}
</div>
@endif

<small class="text-muted">
{{ \Carbon\Carbon::parse($timeline->created_at)->format('d M Y h:i A') }}
</small>

</div>
</div>

</div>

@endforeach

</div>

{{--service timeline end--}}

@if($ticket->images->count())
<div class="mb-3">
<b>Images</b><br>
@foreach($ticket->images as $img)
<img src="{{ asset($img->image_path) }}"
     width="100"
     class="rounded border me-2 mb-2">
@endforeach
</div>
@endif

<div class="row mt-4 pt-3 border-top">

<div class="col-md-6">
<b>Closed Time</b><br>
{{ $ticket->updated_at->format('d M Y h:i A') }}
</div>

<div class="col-md-6 text-end">
<span class="badge bg-success">
Service Completed
</span>
</div>

</div>

</div>
</div>
</div>
</div>

@endforeach
@endsection
