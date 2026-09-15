@props(['header' => null])

<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-brand-100 shadow-sm p-6']) }}>
    @if($header)
        <div class="mb-6">
            <h2 class="text-[10px] font-black text-brand-400 uppercase tracking-widest">
                {{ $header }}
            </h2>
        </div>
    @endif

    {{ $slot }}
</div>