@extends('layouts.app')
@section('title', 'Ticket Visits')

@section('content')
<div class="page-wrapper">
    <div class="page-content">

        <x-breadcrumb :langArray="['Support Portal','Ticket Visits']" />

        <div class="card">
            <div class="card-header">
                <h5>Visits for Ticket {{ $ticket->ticket_no }}</h5>
            </div>

            <div class="card-body">

                @if($ticket->visits->count())

                    @foreach($ticket->visits as $visit)

                        <div class="border rounded p-3 mb-4">

                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    <strong>Engineer:</strong>
                                    {{ $visit->engineer->employee_name ?? 'N/A' }}
                                </div>
                                <div>
                                    <strong>Date:</strong>
                                    {{ $visit->created_at->format('d-m-Y h:i A') }}
                                </div>
                            </div>

                            <div class="mb-2">
                                <strong>Inspection Type:</strong>
                                {{ ucfirst($visit->inspection_type) }}
                            </div>

                            @if($visit->description)
                                <div class="mb-3">
                                    <strong>Description:</strong>
                                    <div class="bg-light p-2 rounded">
                                        {{ $visit->description }}
                                    </div>
                                </div>
                            @endif

                            {{-- SERVICE IMAGES --}}
                            <div class="mb-2">
                                <strong>Service Images:</strong>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    @foreach($visit->images->where('image_type','service') as $img)
                                        <img src="{{ asset($img->image_path) }}"
     width="80"
     height="80"
     class="rounded border preview-image"
     data-image="{{ asset($img->image_path) }}"
     style="object-fit:cover; cursor:pointer;">
                                    @endforeach
                                </div>
                            </div>

                            {{-- MACHINE IMAGES --}}
                            <div class="mb-2">
                                <strong>Machine Images:</strong>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    @foreach($visit->images->where('image_type','machine') as $img)
                                        <img src="{{ asset($img->image_path) }}"
     width="80"
     height="80"
     class="rounded border preview-image"
     data-image="{{ asset($img->image_path) }}"
     style="object-fit:cover; cursor:pointer;">
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="alert alert-info">
                        No visits found for this ticket.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-body text-center">
                <img id="previewImage" src="" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

@section('js')

<script>
document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll('.preview-image').forEach(img => {

        img.addEventListener('click', function(){

            let src = this.getAttribute('data-image');

            document.getElementById('previewImage').src = src;

            let modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));

            modal.show();
        });

    });

});
</script>

@endsection
@endsection