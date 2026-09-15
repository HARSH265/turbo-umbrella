@props(['data' => null])

@php
    $isPaginator = $data instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp

@if($isPaginator && $data->total() > 0)
    <div class="pagination-wrapper">
        {{-- Always shown, even on a single page, so the record count is visible. --}}
        <div class="pagination-info">
            Showing <span class="font-medium">{{ $data->firstItem() ?? 0 }}</span>
            to <span class="font-medium">{{ $data->lastItem() ?? 0 }}</span>
            of <span class="font-medium">{{ $data->total() }}</span>
            {{ Str::plural('result', $data->total()) }}
        </div>

        @if($data->lastPage() > 1)
            @php
                $currentPage = $data->currentPage();
                $lastPage = $data->lastPage();
                $start = max(1, $currentPage - 2);
                $end = min($lastPage, $currentPage + 2);
            @endphp

            <div class="pagination-controls">
                {{-- First --}}
                @if($currentPage === 1)
                    <span class="pagination-btn pagination-btn-disabled">First</span>
                @else
                    <a href="{{ $data->url(1) }}" class="pagination-btn pagination-btn-secondary">First</a>
                @endif

                {{-- Previous --}}
                @if($currentPage === 1)
                    <span class="pagination-btn pagination-btn-disabled">Prev</span>
                @else
                    <a href="{{ $data->previousPageUrl() }}" class="pagination-btn pagination-btn-secondary">Prev</a>
                @endif

                {{-- Page numbers --}}
                @for($i = $start; $i <= $end; $i++)
                    @if($i === $currentPage)
                        <span class="pagination-btn pagination-btn-current" aria-current="page">{{ $i }}</span>
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
        @endif
    </div>
@endif
