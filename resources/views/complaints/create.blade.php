@extends('layouts.app')

@section('title', 'Raise Complaint')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-black text-brand-900 uppercase italic">
            Raise New Complaint
        </h1>
        <x-secondary-button onclick="window.location='{{ route('complaints.index') }}'">
            BACK
        </x-secondary-button>
    </div>

    @if($errors->any())
        <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 text-xs font-bold text-rose-700">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-card>
        <form method="POST" action="{{ route('complaints.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <x-input-label value="Category" />
                <select name="category" required class="w-full border-brand-200 rounded-xl text-sm font-bold">
                    <option value="">Select</option>
                    <option value="Plumbing">Plumbing</option>
                    <option value="Electrical">Electrical</option>
                    <option value="Cleaning">Cleaning</option>
                    <option value="Security">Security</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div>
                <x-input-label value="Subject" />
                <x-text-input name="subject" required class="w-full font-bold"/>
            </div>

            <div>
                <x-input-label value="Description" />
                <textarea name="description" rows="4"
                          class="w-full border-brand-200 rounded-xl text-sm font-medium"></textarea>
            </div>

            <div>
                <x-input-label value="Priority" />
                <select name="priority" required class="w-full border-brand-200 rounded-xl text-sm font-bold">
                    @foreach(\App\Enums\ComplaintPriority::cases() as $priority)
                        <option value="{{ $priority->value }}">
                            {{ ucfirst($priority->value) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label value="Attachments (Optional)" />
                <input type="file" name="files[]" multiple
                       class="w-full text-xs file:bg-brand-900 file:text-white file:px-4 file:py-2 file:rounded-xl file:text-[10px] file:font-black">
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-brand-50">
                <x-secondary-button type="button"
                    onclick="window.location='{{ route('complaints.index') }}'">
                    CANCEL
                </x-secondary-button>

                <x-primary-button class="!text-[10px] font-black px-8">
                    SUBMIT
                </x-primary-button>
            </div>
        </form>
    </x-card>
</div>
@endsection