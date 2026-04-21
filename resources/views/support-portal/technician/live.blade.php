{{-- resources/views/support-portal/technician/live.blade.php --}}
@extends('layouts.app')

@section('title', 'Live Tracking')

@section('css')
<style>
    /* Make page content full height */
    .page-content {
        padding: 20px;
    }

    /* Map Wrapper */
    .live-map-wrapper {
        height: calc(100vh - 120px); /* full height minus header */
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        background: #f8f9fa;
    }

    /* Google Map */
    #map {
        height: 100%;
        width: 100%;
    }

    /* Floating Technician Panel */
    #technicianList {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 300px;
        max-height: 85%;
        overflow-y: auto;
        background: #ffffff;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        z-index: 10;
    }

    /* Custom Scroll */
    #technicianList::-webkit-scrollbar {
        width: 6px;
    }
    #technicianList::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 4px;
    }

    /* Legend Styling */
    .map-legend {
        position: absolute;
        bottom: 20px;
        left: 20px;
        background: white;
        padding: 10px 15px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        font-size: 13px;
        z-index: 10;
    }

    .legend-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 6px;
    }
</style>
@endsection

@section('content')

<div class="page-wrapper">
    <div class="page-content">

        <x-breadcrumb :langArray="['Support Portal', 'Live Tracking']"/>

        <div class="live-map-wrapper">

            <!-- Google Map -->
            <div id="map"></div>

            <!-- Floating Technician Panel -->
            <div id="technicianList">
                <h6 class="mb-3">Engineers</h6>
                <!-- JS will inject engineers here -->
            </div>

            <!-- Legend -->
            <div class="map-legend">
                <div><span class="legend-dot" style="background:#28a745;"></span> Active</div>
                <div><span class="legend-dot" style="background:#ffc107;"></span> Moving</div>
                <div><span class="legend-dot" style="background:#dc3545;"></span> Open Ticket</div>
            </div>

        </div>

    </div>
</div>

@endsection

@section('js')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initGoogleMap" async defer></script>
<script src="{{ asset('custom/js/support-portal/tickets/live-map.js') }}"></script>
@endsection
