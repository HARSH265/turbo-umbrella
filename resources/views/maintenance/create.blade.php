@extends('layouts.app')

@section('title', 'Generate Maintenance')
@section('page-title', 'Generate Maintenance')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-xl font-bold text-gray-900">Generate Maintenance</h1>
        <p class="text-sm text-gray-500">Generate bills based on the active maintenance policy.</p>
    </div>

    <div class="bg-white shadow rounded-lg p-6 max-w-2xl">
        <form method="POST" action="{{ route('maintenance.generate') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @if(auth()->user()->isSuperAdmin())
                    <div class="md:col-span-2">
                        <label class="text-sm text-gray-600">Society</label>
                        <select name="society_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">Select Society</option>
                            @foreach($societies as $society)
                                <option value="{{ $society->id }}" {{ old('society_id') == $society->id ? 'selected' : '' }}>
                                    {{ $society->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label class="text-sm text-gray-600">Month</label>
                    <input type="month" name="month" value="{{ old('month') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Active Flats In Scope</label>
                    <div class="mt-1 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                        {{ $flats->count() }} flat(s)
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit"
                        class="px-6 py-2 bg-brand-900 text-white rounded-md hover:bg-brand-800 text-sm">
                    Generate
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
