<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankingInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('organizer');
    }

    public function rules(): array
    {
        return [
            'bank_name'            => 'required|string|max:150',
            'account_holder_name'  => 'required|string|max:50',
            'account_number'       => 'required|string|max:40',
            'routing_number'       => 'required|string|max:30',
            // 'swift_code'           => 'nullable|string|max:20',
        ];
    }
}