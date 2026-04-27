<?php

namespace App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

/**
 * AssignComplaintRequest
 * 
 * Validates complaint assignment to staff
 */
class AssignComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasPermission('complaints.assign');
    }

    public function rules(): array
    {
        return [
            'assigned_to' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = \App\Models\User::find($value);
                    if (!$user || (!$user->isStaff() && !$user->isSocietyAdmin())) {
                        $fail('Selected user must be a staff member or society admin.');
                    }
                },
            ],
        ];
    }
}
