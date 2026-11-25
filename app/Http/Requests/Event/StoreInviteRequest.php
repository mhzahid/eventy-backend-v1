<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class StoreInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('organizer');
    }

    public function rules(): array
    {
        return [
            'ticket_id' => 'required|ulid|exists:tickets,id',
            'quantity'  => 'required|integer|min:1|max:4',
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255',
            'phone'     => 'required|string|max:20',
        ];
    }
}