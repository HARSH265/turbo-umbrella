<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;


/**
 * StoreNoticeRequest
 * 
 * Validates notice creation
 */
class StoreNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasPermission('notices.create');
    }

    public function rules(): array
    {
        $maxFileSize = \App\Services\SystemConfigService::get('max_file_size', 5) * 1024;

        return [
            'society_id' => 'required|exists:societies,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:5000',
            'priority' => 'required|in:normal,important,urgent',
            'visibility' => 'required|in:all,specific',
            'recipients' => 'required_if:visibility,specific|array',
            'recipients.*' => 'exists:users,id',
            'publish_date' => 'required|date|after_or_equal:today',
            'expiry_date' => 'nullable|date|after:publish_date',
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
            'recipients.required_if' => 'Please select recipients for specific visibility.',
            'expiry_date.after' => 'Expiry date must be after publish date.',
        ];
    }
}