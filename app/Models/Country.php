<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class Country extends Model
{
    protected $casts = [
        'is_active' => 'boolean',
    ];


    public function organizerProfile()
    {
        return $this->hasMany(OrganizerProfile::class);
    }
}
