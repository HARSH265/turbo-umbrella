<?php

/**
 * app/Http/Requests/UpdateNoticeRequest.php
 */

namespace App\Http\Requests;

use App\Enums\NoticeCategory;
use App\Enums\NoticePriority;
use App\Enums\NoticeStatus;
use App\Enums\NoticeVisibility;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasPermission('notices.update');
    }

    public function rules(): array
    {
        $maxFileSize = \App\Services\SystemConfigService::get('max_file_size', 5) * 1024;
        /** @var Notice|null $notice */
        $notice = $this->route('notice');
        $isPublished = $notice?->isPublished() ?? false;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
            'category' => ['sometimes', 'required', Rule::enum(NoticeCategory::class)],
            'priority' => ['sometimes', 'required', Rule::enum(NoticePriority::class)],
            'status' => ['sometimes', 'required', Rule::enum(NoticeStatus::class)],
            'visibility' => [
                'sometimes',
                'required',
                Rule::enum(NoticeVisibility::class),
                Rule::prohibitedIf($isPublished),
            ],
            'target_user_id' => [
                'nullable',
                'required_if:visibility,' . NoticeVisibility::PERSONAL->value,
                Rule::prohibitedIf($isPublished),
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
                Rule::prohibitedIf($isPublished),
                Rule::in(['resident', 'staff', 'society-admin']),
            ],
            'society_id' => ['sometimes', 'nullable', 'exists:societies,id'],
            'is_pinned' => ['sometimes', 'boolean'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
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
            'visibility.prohibited' => 'Visibility cannot be changed after a notice is published.',
            'target_user_id.required_if' => 'Please select a target user for personal notices.',
            'target_user_id.prohibited' => 'Target user cannot be changed after a notice is published.',
            'target_role.required_if' => 'Please select a target role for role-based notices.',
            'target_role.prohibited' => 'Target role cannot be changed after a notice is published.',
            'expires_at.after' => 'Expiry date must be in the future.',
            'attachments.max' => 'You may upload at most 5 attachments.',
        ];
    }
}
