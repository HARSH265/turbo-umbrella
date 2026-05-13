@props([
    'headers' => [],
    'data' => null,
    'emptyMessage' => 'No records found',
])

@php
$hasPagination = $data && is_object($data) && method_exists($data, 'total');
$hasData = $data && $data->count() > 0;
@endphp

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
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

    @if($hasPagination && $data->lastPage() > 1)
    <div class="pagination-wrapper">
        <div class="pagination-info">
            Showing <span class="font-medium">{{ $data->firstItem() ?? 0 }}</span>
            to <span class="font-medium">{{ $data->lastItem() ?? 0 }}</span>
            of <span class="font-medium">{{ $data->total() }}</span> results
        </div>

        <div class="pagination-controls">
            {{-- First --}}
            @if($data->currentPage() === 1)
            <span class="pagination-btn pagination-btn-disabled">First</span>
            @else
            <a href="{{ $data->url(1) }}" class="pagination-btn pagination-btn-secondary">First</a>
            @endif

            {{-- Previous --}}
            @if($data->currentPage() === 1)
            <span class="pagination-btn pagination-btn-disabled">Prev</span>
            @else
            <a href="{{ $data->previousPageUrl() }}" class="pagination-btn pagination-btn-secondary">Prev</a>
            @endif

            {{-- Page Numbers --}}
            @php
            $currentPage = $data->currentPage();
            $lastPage = $data->lastPage();
            $start = max(1, $currentPage - 2);
            $end = min($lastPage, $currentPage + 2);
            @endphp

            @for($i = $start; $i <= $end; $i++)
                @if($i == $currentPage)
                <span class="pagination-btn pagination-btn-current">{{ $i }}</span>
                @else
                <a href="{{ $data->url($i) }}" class="pagination-btn pagination-btn-secondary">{{ $i }}</a>
                @endif
            @endfor

            {{-- Next --}}
            @if($data->hasMorePages())
            <a href="{{ $data->nextPageUrl() }}" class="pagination-btn pagination-btn-secondary">Next</a>
            @else
            <span class="pagination-btn pagination-btn-disabled">Next</span>
            @endif

            {{-- Last --}}
            @if($data->hasMorePages())
            <a href="{{ $data->url($lastPage) }}" class="pagination-btn pagination-btn-secondary">Last</a>
            @else
            <span class="pagination-btn pagination-btn-disabled">Last</span>
            @endif
        </div>
    </div>
    @elseif(!$hasData)
    <div class="empty-state">
        <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="empty-state-text">{{ $emptyMessage }}</p>
    </div>
    @endif
</div>