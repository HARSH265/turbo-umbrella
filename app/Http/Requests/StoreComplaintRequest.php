<?php

namespace App\Http\Requests;

use App\Models\Flat;
use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $maxFileSize = \App\Services\SystemConfigService::get('max_file_size', 5) * 1024; // KB

        return [
            'flat_id' => [
                'required',
                'exists:flats,id',
                function ($attribute, $value, $fail) {
                    $user = auth()->user();

                    if (!$user || !$user->isResident()) {
                        return;
                    }

                    $allowed = $user->activeFlats()
                        ->where('flats.id', $value)
                        ->exists();

                    if (!$allowed) {
                        $fail('You can only raise complaints for your own active flats.');
                    }
                },
            ],
            'category' => 'required|string|max:100',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'priority' => 'required|in:low,medium,high,urgent',
            'files' => 'nullable|array',
            'files.*' => [
                'nullable',
                'file',
                'max:' . $maxFileSize,
                'mimes:jpg,jpeg,png,pdf,docx,doc',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'flat_id.required' => 'Please select a flat.',
            'flat_id.exists' => 'Selected flat is invalid.',
            'files.*.mimes' => 'Only JPG, PNG, PDF, and DOCX files are allowed.',
            'files.*.max' => 'File size must not exceed ' . \App\Services\SystemConfigService::get('max_file_size', 5) . ' MB.',
        ];
    }
}
