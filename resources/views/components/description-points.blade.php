@props([
    'description' => '',
])

@php
    $descriptionText = is_string($description) ? $description : '';
    $normalizedDescription = preg_replace("/\r\n|\r/u", "\n", $descriptionText);
    $descriptionLines = collect(explode("\n", $normalizedDescription))
        ->map(fn ($line) => trim($line))
        ->filter(fn ($line) => $line !== '')
        ->values();
@endphp

@if ($descriptionLines->isNotEmpty())
    <ul {{ $attributes->merge(['class' => 'mb-0 ps-3']) }}>
        @foreach ($descriptionLines as $line)
            @php
                $point = preg_replace('/^\s*(?:[-*•]+|\d+[.)])\s*/u', '', $line);
            @endphp
            <li class="mb-0"><small>{{ $point !== '' ? $point : $line }}</small></li>
        @endforeach
    </ul>
@endif
