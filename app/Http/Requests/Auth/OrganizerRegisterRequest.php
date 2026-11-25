<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class OrganizerRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:50'],
            'organization_name' => ['required', 'string', 'max:50'],
            'country_id'        => ['required', 'integer', 'exists:countries,id'],
            'phone'             => ['required', 'string', 'max:25'],
            'email'             => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'          => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}