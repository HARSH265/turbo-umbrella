<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Auth;


/**
 * StoreUserRequest
 * 
 * Validates user creation
 * Enforces strong password rules
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasPermission('users.create');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|regex:/^[0-9]{10}$/|unique:users,phone',
            'society_id' => 'nullable|exists:societies,id',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'role_id' => 'required|exists:roles,id',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone number must be 10 digits.',
            'password.min' => 'Password must be at least 8 characters with mixed case, numbers, and symbols.',
        ];
    }
}
