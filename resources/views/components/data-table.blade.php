@props([
    'headers' => [],
    'data' => null,
    'emptyMessage' => 'No records found',
])

@php
    $hasData = $data && $data->count() > 0;

    /**
     * Publish the column headings as CSS custom properties so the stylesheet can echo
     * them back as field labels when the table collapses into cards on phones
     * (see "Responsive Data Table" in resources/css/app.css).
     *
     * Doing it here means no view has to hand-label its cells, and any table added
     * later inherits the behaviour for free. Characters are restricted to a safe set
     * because the result is injected into a style attribute unescaped.
     */
    $columnLabels = collect($headers)
        ->map(fn ($header, $i) => sprintf(
            "--col-%d:'%s'",
            $i + 1,
            preg_replace('/[^A-Za-z0-9 ._\/-]/', '', (string) $header)
        ))
        ->implode(';');
@endphp

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table" style="{!! $columnLabels !!}">
            <thead>
                <tr>
                    @foreach($headers as $header)
                    <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if($hasData)
        <x-pagination :data="$data" />
    @else
    <div class="empty-state">
        <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="empty-state-text">{{ $emptyMessage }}</p>
    </div>
    @endif
</div>
