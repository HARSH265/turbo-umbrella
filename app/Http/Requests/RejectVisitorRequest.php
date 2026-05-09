<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class RejectVisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasPermission('visitors.update');
    }

    public function rules(): array
    {
        return [
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
