<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * StoreFlatRequest
 * 
 * Validates flat creation
 * Ensures unique flat numbers within tower
 */
class StoreFlatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasPermission('flats.create');
    }

    public function rules(): array
    {
        return [
            'tower_id' => 'required|exists:towers,id',
            'flat_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('flats')->where('tower_id', $this->tower_id),
            ],
            'floor_number' => 'required|integer|min:0',
            'type' => 'required|in:1BHK,2BHK,3BHK,4BHK,Penthouse',
            'carpet_area' => 'nullable|numeric|min:0',
            'occupancy_status' => 'required|in:occupied,vacant',
        ];
    }

    public function messages(): array
    {
        return [
            'flat_number.unique' => 'This flat number already exists in the selected tower.',
            'tower_id.exists' => 'Selected tower does not exist.',
        ];
    }
}