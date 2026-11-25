<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class SaveOwnerInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('organizer');
    }

    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:50',
            // 'id_type'      => 'required|in:national_id,passport',
            'id_image'     => ['nullable', $this->imageOrUrl()],
            'phone_number' => 'required|string|max:25',
            'mail'         => 'required|email|max:150',
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