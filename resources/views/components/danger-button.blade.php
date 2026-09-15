{{-- Renders the .btn classes so Blade and hand-written markup cannot drift apart. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-danger']) }}>
    {{ $slot }}
</button>
