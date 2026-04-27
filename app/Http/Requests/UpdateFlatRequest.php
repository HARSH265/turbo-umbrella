<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateFlatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasPermission('flats.update');
    }

    public function rules(): array
    {
        $flatId = $this->route('flat');

        return [
            'tower_id' => 'required|exists:towers,id',
            'flat_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('flats')->where(function ($query) {
                    return $query->where('tower_id', $this->tower_id);
                })->ignore($flatId),
            ],
            'floor_number' => 'required|integer|min:0',
            'type' => 'required|in:1BHK,2BHK,3BHK,4BHK,Penthouse',
            'carpet_area' => 'nullable|numeric|min:0',
            'occupancy_status' => 'required|in:occupied,vacant',
            'is_active' => 'boolean',
        ];
    }
}