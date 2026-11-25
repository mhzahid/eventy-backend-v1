<?php

namespace App\Http\Requests\Event;

use App\Enums\EventStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('organizer');
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name'                   => 'sometimes|string|max:255',
            'slug'                   => ['sometimes', 'string', 'max:40', Rule::unique('events', 'slug')->ignore($id)],
            'tag_line'               => 'sometimes|string|max:70',
            'description'            => 'sometimes|string',
            'status'                 => ['sometimes', Rule::enum(EventStatus::class)],
            'is_physical'            => 'sometimes|boolean',
            'location'               => [Rule::requiredIf($this->boolean('is_physical')), 'nullable', 'string', 'max:350'],
            'map_url'                => [Rule::requiredIf($this->boolean('is_physical')), 'nullable', 'url', 'max:350'],
            'event_date'             => 'sometimes|date|after_or_equal:today',
            'start_time'             => ['sometimes', 'date_format:H:i,H:i:s'],
            'sell_end_date'          => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:event_date'],
            'sell_end_time'          => ['sometimes', 'date_format:H:i,H:i:s'],
            'category_id'            => 'sometimes|integer|exists:categories,id',
            'payment_gateway_id'     => 'sometimes|integer|exists:payment_gateways,id',
            'is_age_restricted'      => 'boolean',
            'charge_transaction_fee' => 'boolean',
            'max_tickets_per_order'  => 'integer|min:1|max:5',
            'event_banner'           => ['nullable', $this->imageOrUrl()],
            'venue_map_image'        => ['nullable', $this->imageOrUrl()],
            'brand_color'            => 'sometimes|string|max:7',
            'support_email'          => 'sometimes|email|max:255',
            'support_number'         => 'sometimes|string|max:25',
            'facebook_url'           => 'sometimes|url|max:350',
            'terms_and_conditions'   => 'sometimes|string',

            'sponsors'               => 'nullable|array',
            'sponsors.*'             => ['required', $this->imageOrUrl()],

            'highlights'             => 'sometimes|array|min:1',
            'highlights.*.text'      => 'required|string|max:500',
            'highlights.*.time'      => 'required|string|max:50',

            'participants'           => 'sometimes|array|min:1',
            'participants.*.id'      => 'nullable|ulid|exists:participants,id',
            'participants.*.name'    => 'required|string|min:2|max:255',
            'participants.*.title'   => 'required|string|min:2|max:255',
            'participants.*.image'   => ['required', $this->imageOrUrl()],

            'tickets'                => 'sometimes|array|min:1',
            'tickets.*.id'           => 'nullable|ulid|exists:tickets,id',
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

    private function imageOrUrl(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (is_string($value)) return;
            if (!($value instanceof UploadedFile)) {
                $fail("The {$attribute} must be an image file or an existing image URL.");
                return;
            }
            if (!in_array($value->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])) {
                $fail("The {$attribute} must be a file of type: jpeg, png, jpg, webp.");
            }
            if ($value->getSize() > 2048 * 1024) {
                $fail("The {$attribute} may not be greater than 2048 kilobytes.");
            }
        };
    }
}
