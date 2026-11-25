<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Guarded([])]
class OrganizerOwnerInfo extends Model
{
    use HasUlids, HasFactory;

    protected $casts = [
        // 
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}