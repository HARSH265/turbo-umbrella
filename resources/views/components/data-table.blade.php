@props([
    'headers' => [],
    'data' => null,
    'emptyMessage' => 'No records found',
    'route' => null,
    'canCreate' => false,
    'createText' => 'Create New',
])

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    @if($route || $canCreate)
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        @if($route)
        <a href="{{ $route }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ $createText ?? 'Add New' }}
        </a>
        @endif
    </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @foreach($headers as $header)
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                        {{ $header }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @if($data && method_exists($data, 'hasPages') && $data->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-sm text-gray-600">
                Showing <span class="font-medium">{{ $data->firstItem() ?? 0 }}</span> to <span class="font-medium">{{ $data->lastItem() ?? 0 }}</span> of <span class="font-medium">{{ $data->total() }}</span> results
            </div>
            <div class="flex items-center gap-1">
                @if($data->onFirstPage())
                <span class="px-3 py-1.5 text-sm text-gray-400 bg-gray-100 rounded-md">First</span>
                <span class="px-3 py-1.5 text-sm text-gray-400 bg-gray-100 rounded-md">Prev</span>
                @else
                <a href="{{ $data->url(1) }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">First</a>
                <a href="{{ $data->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Prev</a>
                @endif

                @php
                $currentPage = $data->currentPage();
                $lastPage = $data->lastPage();
                $start = max(1, $currentPage - 2);
                $end = min($lastPage, $currentPage + 2);
                @endphp

                @for($i = $start; $i <= $end; $i++)
                @if($i == $currentPage)
                <span class="px-3 py-1.5 text-sm font-medium text-white bg-emerald-600 rounded-md">{{ $i }}</span>
                @else
                <a href="{{ $data->url($i) }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">{{ $i }}</a>
                @endif
                @endfor

                @if($data->hasMorePages())
                <a href="{{ $data->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Next</a>
                <a href="{{ $data->url($lastPage) }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Last</a>
                @else
                <span class="px-3 py-1.5 text-sm text-gray-400 bg-gray-100 rounded-md">Next</span>
                <span class="px-3 py-1.5 text-sm text-gray-400 bg-gray-100 rounded-md">Last</span>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>