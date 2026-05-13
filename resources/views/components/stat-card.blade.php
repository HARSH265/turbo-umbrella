@props([
    'title' => '',
    'value' => '',
    'icon' => null,
    'trend' => null,
    'trendLabel' => '',
    'color' => 'emerald',
])

@php
$colors = [
    'emerald' => 'bg-emerald-50 text-emerald-600',
    'blue' => 'bg-blue-50 text-blue-600',
    'amber' => 'bg-amber-50 text-amber-600',
    'rose' => 'bg-rose-50 text-rose-600',
    'purple' => 'bg-purple-50 text-purple-600',
];
$bgColor = $colors[$color] ?? $colors['emerald'];
@endphp

<div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500 mb-1">{{ $title }}</p>
            <p class="text-3xl font-bold text-gray-900">{{ $value }}</p>
            @if($trend)
            <p class="text-sm mt-2">
                <span class="{{ $trend >= 0 ? 'text-emerald-600' : 'text-rose-600' }} font-medium">
                    {{ $trend >= 0 ? '+' : '' }}{{ $trend }}%
                </span>
                <span class="text-gray-400">{{ $trendLabel }}</span>
            </p>
            @endif
        </div>
        @if($icon)
        <div class="w-14 h-14 rounded-xl {{ $bgColor }} flex items-center justify-center">
            {!! $icon !!}
        </div>
        @endif
    </div>
</div>