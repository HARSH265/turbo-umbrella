<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * StoreSocietyRequest
 * 
 * Validates society creation data
 * Ensures unique codes, valid contact details
 */
class StoreSocietyRequest extends FormRequest
{
    public function authorize(): bool
{
    return Auth::check() && Auth::user()->hasPermission('societies.create');
}

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:societies,code',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|regex:/^[0-9]{6}$/',
            'contact_number' => 'required|string|regex:/^[0-9]{10}$/',
            'email' => 'required|email|unique:societies,email',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This society code is already taken.',
            'pincode.regex' => 'Pincode must be 6 digits.',
            'contact_number.regex' => 'Contact number must be 10 digits.',
        ];
    }
}