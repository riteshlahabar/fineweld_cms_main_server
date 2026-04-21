<!-- Alert : Start-->
@php
    $session = session('record');
@endphp

@if(isset($session['type']))
<div class="alert alert-{{ $session['type'] }} alert-dismissible fade show" role="alert">
    <strong>
        @if($session['type'] == 'success')
            <i class='bx bxs-check-circle me-1'></i> {{ $session['status'] ?? '' }}!
        @elseif($session['type'] == 'danger')
            <i class='bx bxs-message-square-x me-1'></i> {{ $session['status'] ?? '' }}!
        @elseif($session['type'] == 'info')
            <i class='bx bxs-info-square me-1'></i> {{ $session['status'] ?? '' }}!
        @endif
    </strong>


    @if(isset($session['sms']) && $session['sms'])
        <div class="mt-1 small">{{ __('message.sms_status') }}: {{ $session['sms'] }}</div>
    @endif

    @if(isset($session['email']) && $session['email'])
        <div class="mt-1 small">{{ __('message.email_status') }}: {{ $session['email'] }}</div>
    @endif

    @if(isset($session['message']) && $session['message'])
        <div class="mt-1">{!! $session['message'] !!}</div>
    @endif

    {{ session()->forget('record') }}

    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif
<!-- Alert : End -->
