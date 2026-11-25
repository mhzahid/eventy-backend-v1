<?php

namespace App\Http\Requests\Event;

use App\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('organizer');
    }

    public function rules(): array
    {
        return [
            'name'                   => 'required|string|max:255',
            'slug'                   => 'required|string|unique:events,slug|max:40',
            'tag_line'               => 'required|string|max:70',
            'description'            => 'required|string',
            'status'                 => ['required', Rule::enum(EventStatus::class)],
            'is_physical'            => 'required|boolean',
            'location'               => [Rule::requiredIf($this->boolean('is_physical')), 'nullable', 'string', 'max:350'],
            'map_url'                => [Rule::requiredIf($this->boolean('is_physical')), 'nullable', 'url', 'max:350'],
            'event_date'             => 'required|date_format:Y-m-d|after_or_equal:today',
            'start_time'             => ['required', 'date_format:H:i,H:i:s'],
            'sell_end_date'          => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:event_date'],
            'sell_end_time'          => ['nullable', 'date_format:H:i,H:i:s'],
            'category_id'            => 'required|integer|exists:categories,id',
            'payment_gateway_id'     => 'required|integer|exists:payment_gateways,id',
            'is_age_restricted'      => 'boolean',
            'charge_transaction_fee' => 'boolean',
            'max_tickets_per_order'  => 'integer|min:1|max:5',
            // 'max_tickets_per_user'   => 'integer|min:1|max:20',
            'event_banner'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'venue_map_image'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'brand_color'            => 'required|string|max:7',
            'support_email'          => 'required|email|max:255',
            'support_number'         => 'required|string|max:25',
            'facebook_url'           => 'required|url|max:350',
            'terms_and_conditions'   => 'required|string',

            'sponsors'               => 'nullable|array',
            'sponsors.*'             => 'image|mimes:jpeg,png,jpg,webp|max:2048',

            'highlights'             => 'required|array|min:1',
            'highlights.*.text'      => 'required|string|max:500',
            'highlights.*.time'      => 'required|string|max:50',

            'participants'           => 'required|array|min:1',
            'participants.*.name'    => 'required|string|min:2|max:255',
            'participants.*.title'   => 'required|string|min:2|max:255',
            'participants.*.image'   => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',

            'tickets'                => 'required|array|min:1',
            'tickets.*.name'         => 'required|string|min:2|max:255',
            'tickets.*.price'        => 'required|numeric|min:0',
            'tickets.*.quantity'     => 'required|integer|min:1',
            'tickets.*.includes'     => 'nullable|string|max:285',

            'form_fields'            => 'nullable|array',
            'form_fields.*.label'    => 'required|string|min:2|max:255',
            'form_fields.*.type'     => 'required|string|in:text,phone,number,select,checkbox,textarea',
            'form_fields.*.required' => 'required|boolean',
            'form_fields.*.options'  => 'nullable|string|required_if:form_fields.*.type,select,checkbox',
        ];
    }
}
