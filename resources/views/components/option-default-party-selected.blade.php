@if($defaultParty && $defaultParty->id)
    <option value="{{ $defaultParty->id }}">{{ $defaultParty->company_name }}</option>
@endif