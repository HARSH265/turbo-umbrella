@props([
    'headers' => [],
    'data' => [],
    'emptyMessage' => 'No records found',
])

<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @foreach($headers as $header)
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ $header }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($data as $row)
                <tr class="hover:bg-gray-50 transition-colors">
                    {{ $row }}
                </tr>
                @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="px-6 py-12 text-center text-gray-500">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p>{{ $emptyMessage }}</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($data) && method_exists($data, 'hasPages') && $data->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Showing {{ $data->firstItem() ?? 0 }} to {{ $data->lastItem() ?? 0 }} of {{ $data->total() }} results
            </div>
            <div class="flex items-center gap-1">
                @if($data->onFirstPage())
                <span class="px-3 py-1.5 text-sm text-gray-400 bg-gray-100 rounded-md cursor-not-allowed">First</span>
                <span class="px-3 py-1.5 text-sm text-gray-400 bg-gray-100 rounded-md cursor-not-allowed">Previous</span>
                @else
                <a href="{{ $data->url(1) }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">First</a>
                <a href="{{ $data->previousPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Previous</a>
                @endif

                @foreach(range(1, min(5, $data->lastPage())) as $page)
                @if($page == $data->currentPage())
                <span class="px-3 py-1.5 text-sm text-white bg-emerald-600 rounded-md">{{ $page }}</span>
                @else
                <a href="{{ $data->url($page) }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">{{ $page }}</a>
                @endif
                @endforeach

                @if($data->hasMorePages())
                <a href="{{ $data->nextPageUrl() }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Next</a>
                <a href="{{ $data->url($data->lastPage()) }}" class="px-3 py-1.5 text-sm text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Last</a>
                @else
                <span class="px-3 py-1.5 text-sm text-gray-400 bg-gray-100 rounded-md cursor-not-allowed">Next</span>
                <span class="px-3 py-1.5 text-sm text-gray-400 bg-gray-100 rounded-md cursor-not-allowed">Last</span>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>