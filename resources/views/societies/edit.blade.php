@extends('layouts.app')

@section('title', 'Edit Society')
@section('page-title', 'Edit Society')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Society</h1>

        <form method="POST" action="{{ route('societies.update', $society) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Society Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $society->name) }}" required class="w-full border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Society Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code', $society->code) }}" required class="w-full border-gray-300 rounded-lg">
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Address <span class="text-red-500">*</span></label>
                <textarea name="address" rows="3" required class="w-full border-gray-300 rounded-lg">{{ old('address', $society->address) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">City <span class="text-red-500">*</span></label>
                    <input type="text" name="city" value="{{ old('city', $society->city) }}" required class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">State <span class="text-red-500">*</span></label>
                    <input type="text" name="state" value="{{ old('state', $society->state) }}" required class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pincode <span class="text-red-500">*</span></label>
                    <input type="text" name="pincode" value="{{ old('pincode', $society->pincode) }}" required class="w-full border-gray-300 rounded-lg">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number <span class="text-red-500">*</span></label>
                    <input type="text" name="contact_number" value="{{ old('contact_number', $society->contact_number) }}" required class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $society->email) }}" required class="w-full border-gray-300 rounded-lg">
                </div>
            </div>

            <div class="mt-6 flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $society->is_active) ? 'checked' : '' }} class="rounded border-gray-300">
                <label for="is_active" class="ml-2 text-sm text-gray-700">Society is active</label>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('societies.show', $society) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                    Update Society
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
