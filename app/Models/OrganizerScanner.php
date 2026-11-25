<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class OrganizerScanner extends Model
{
    use HasUlids;

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function scanner()
    {
        return $this->belongsTo(User::class, 'scanner_id');
    }
}
