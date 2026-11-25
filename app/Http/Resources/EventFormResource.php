<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventFormResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);

        return [
            'id'     => $this->id,
            'fields' => collect($this->fields ?? [])->map(fn($field) => [
                'label'    => $field['label'],
                'type'     => $field['type'],
                'required' => filter_var($field['required'], FILTER_VALIDATE_BOOLEAN),
                'options'  => !empty($field['options'])
                    ?  $field['options']
                    : null,
            ])->values(),
        ];
    }
}
