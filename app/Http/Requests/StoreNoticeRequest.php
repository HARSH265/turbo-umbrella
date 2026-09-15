<?php

/**
 * app/Http/Requests/StoreNoticeRequest.php
 */

namespace App\Http\Requests;

use App\Enums\NoticeCategory;
use App\Enums\NoticePriority;
use App\Enums\NoticeStatus;
use App\Enums\NoticeVisibility;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'category' => ['required', Rule::enum(NoticeCategory::class)],
            'priority' => ['required', Rule::enum(NoticePriority::class)],
            'status' => ['required', Rule::enum(NoticeStatus::class)],
            'visibility' => ['required', Rule::enum(NoticeVisibility::class)],
            'target_user_id' => [
                'nullable',
                'required_if:visibility,' . NoticeVisibility::PERSONAL->value,
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if (!$value) {
                        return;
                    }

                    $targetUser = User::with('roles')->find($value);
                    $authUser = $this->user();

                    if (!$targetUser || !$targetUser->hasRole('resident')) {
                        $fail('Selected target user must be a resident.');
                        return;
                    }

                    if ($authUser && !$authUser->isSuperAdmin() && $targetUser->society_id !== $authUser->society_id) {
                        $fail('Selected target user must belong to your society.');
                    }
                },
            ],
            'target_role' => [
                'nullable',
                'required_if:visibility,' . NoticeVisibility::ROLE_BASED->value,
                Rule::in(['resident', 'staff', 'society-admin']),
            ],
            'society_id' => ['nullable', 'exists:societies,id'],
            'is_pinned' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:' . $maxFileSize,
                'mimes:jpg,jpeg,png,pdf,docx',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'target_user_id.required_if' => 'Please select a target user for personal notices.',
            'target_role.required_if' => 'Please select a target role for role-based notices.',
            'expires_at.after' => 'Expiry date must be in the future.',
            'attachments.max' => 'You may upload at most 5 attachments.',
        ];
    }
}
