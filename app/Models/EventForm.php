<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class EventForm extends Model
{
    use HasUlids, HasFactory;

    protected $casts = [
        'fields' => 'array',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
