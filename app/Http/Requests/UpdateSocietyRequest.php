<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;


/**
 * UpdateSocietyRequest
 * 
 * Validates society update data
 * Excludes current record from unique checks
 */
class UpdateSocietyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasPermission('societies.update');
    }

    public function rules(): array
    {
        $societyId = $this->route('society');

        return [
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('societies', 'code')->ignore($societyId),
            ],
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'pincode' => 'required|string|regex:/^[0-9]{6}$/',
            'contact_number' => 'required|string|regex:/^[0-9]{10}$/',
            'email' => [
                'required',
                'email',
                Rule::unique('societies', 'email')->ignore($societyId),
            ],
            'is_active' => 'boolean',
        ];
    }
}