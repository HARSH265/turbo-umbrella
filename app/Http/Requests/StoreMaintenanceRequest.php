<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasPermission('maintenance.create');
    }

    public function rules(): array
    {
        return [
            'society_id' => [
                'nullable',
                'exists:societies,id',
            ],
            'month' => [
                'required',
                'date_format:Y-m',
                'after_or_equal:' . now()->subMonths(3)->format('Y-m'),
                'before_or_equal:' . now()->addMonth()->format('Y-m'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'month.after_or_equal'  => 'Cannot generate maintenance for months older than 3 months.',
            'month.before_or_equal' => 'Cannot generate maintenance for more than 1 month ahead.',
        ];
    }
}
