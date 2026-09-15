@props(['color' => 'zinc'])

@php
    $colors = [
        'zinc' => 'bg-brand-100 text-brand-700 border-brand-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'rose' => 'bg-rose-50 text-rose-700 border-rose-200',
        'amber' => 'bg-amber-50 text-amber-700 border-amber-200',
        'purple' => 'bg-purple-50 text-purple-700 border-purple-200',
        'orange' => 'bg-orange-50 text-orange-700 border-orange-200',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full border ' . ($colors[$color] ?? $colors['zinc'])]) }}>
    {{ $slot }}
</span>