<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('organizer');
    }

    public function rules(): array
    {
        return [
            'organization_name' => 'required|string|max:50',
            'business_type'     => 'required|string|max:100',
            'address'           => 'required|string|max:500',
            'website'           => 'nullable|url|max:255',
            'logo'              => ['nullable', $this->imageOrUrl()],
            'contact_number'    => 'required|string|max:25',
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