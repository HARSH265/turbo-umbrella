<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreVisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasPermission('visitors.create');
    }

    public function rules(): array
    {
        return [
            'flat_id' => ['required', 'exists:flats,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'purpose' => ['required', 'string', 'max:255'],
            'entry_time' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
